<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AzureDocumentIntelligenceService
{
    protected string $endpoint;
    protected string $key;
    protected string $apiVersion;

    public function __construct()
    {
        $this->endpoint = rtrim(env('AZURE_DOCUMENT_INTELLIGENCE_ENDPOINT'), '/');
        $this->key = env('AZURE_DOCUMENT_INTELLIGENCE_KEY');
        $this->apiVersion = env('AZURE_DOCUMENT_INTELLIGENCE_API_VERSION', '2024-11-30');
    }

    public function analyzeLayoutFromUrl(string $fileUrl): array
    {
        $url = $this->endpoint . "/documentintelligence/documentModels/prebuilt-layout:analyze?api-version={$this->apiVersion}";

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->key,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'urlSource' => $fileUrl,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Error al iniciar análisis: ' . $response->body());
        }

        $operationLocation = $response->header('operation-location');

        if (!$operationLocation) {
            throw new \Exception('Azure no devolvió operation-location.');
        }

        do {
            sleep(2);

            $resultResponse = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->key,
            ])->get($operationLocation);

            if (!$resultResponse->successful()) {
                throw new \Exception('Error consultando resultado: ' . $resultResponse->body());
            }

            $result = $resultResponse->json();
            $status = $result['status'] ?? null;
        } while (in_array($status, ['notStarted', 'running']));

        return $result;
    }
}