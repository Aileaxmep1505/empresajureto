<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PythonAiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.python_ai.url'), '/');
    }

    public function analyzePdfAsync(string $storagePath, string $filename, int $pagesPerChunk = 5): array
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($storagePath)) {
            throw new \RuntimeException("No existe el archivo en disk public: {$storagePath}");
        }

        $fullPath = $disk->path($storagePath);

        $response = Http::timeout(120)
            ->attach(
                'file',
                file_get_contents($fullPath),
                $filename
            )
            ->post($this->baseUrl . '/documents/analyze-async', [
                'pages_per_chunk' => $pagesPerChunk,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Python AI error: ' . $response->body());
        }

        return $response->json();
    }

    public function getJobStatus(string $pythonJobId): array
    {
        $response = Http::timeout(60)->get($this->baseUrl . '/documents/jobs/' . $pythonJobId);

        if (!$response->successful()) {
            throw new \RuntimeException('Python AI status error: ' . $response->body());
        }

        return $response->json();
    }

    public function getJobResult(string $pythonJobId): array
    {
        $response = Http::timeout(120)->get($this->baseUrl . '/documents/jobs/' . $pythonJobId . '/result');

        if (!$response->successful()) {
            throw new \RuntimeException('Python AI result error: ' . $response->body());
        }

        return $response->json();
    }

    public function getStructuredResult(string $pythonJobId): array
    {
        $response = Http::timeout(180)->get($this->baseUrl . '/documents/jobs/' . $pythonJobId . '/structured');

        if (!$response->successful()) {
            throw new \RuntimeException('Python AI structured error: ' . $response->body());
        }

        return $response->json();
    }

    public function getItemsResult(string $pythonJobId): array
    {
        $response = Http::timeout(300)->get($this->baseUrl . '/documents/jobs/' . $pythonJobId . '/items');

        if (!$response->successful()) {
            throw new \RuntimeException('Python AI items error: ' . $response->body());
        }

        return $response->json();
    }
}