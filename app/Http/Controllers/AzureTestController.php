<?php

namespace App\Http\Controllers;

use App\Services\AzureDocumentIntelligenceService;
use Throwable;

class AzureTestController extends Controller
{
    public function test(AzureDocumentIntelligenceService $azure)
    {
        try {
            $fileUrl = 'https://raw.githubusercontent.com/Azure-Samples/document-intelligence-code-samples/main/Data/invoice/invoice-logic-apps-tutorial.pdf';

            $result = $azure->analyzeLayoutFromUrl($fileUrl);

            return response()->json([
                'ok' => true,
                'status' => $result['status'] ?? null,
                'content' => $result['analyzeResult']['content'] ?? null,
                'pages' => $result['analyzeResult']['pages'] ?? [],
                'tables' => $result['analyzeResult']['tables'] ?? [],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}