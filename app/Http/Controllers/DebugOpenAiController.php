<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class DebugOpenAiController extends Controller
{
    public function models()
    {
        $apiKey  = config('services.openai.api_key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        $project = config('services.openai.project_id'); // opcional

        $headers = [];
        if ($project) $headers['OpenAI-Project'] = $project;

        $res = Http::withToken($apiKey)
            ->withHeaders($headers)
            ->timeout(30)
            ->get($baseUrl . '/v1/models');

        return response()->json([
            'ok' => $res->ok(),
            'status' => $res->status(),
            'project' => $project,
            'body' => $res->json(),
            'raw' => $res->body(),
        ], $res->status() ?: 200);
    }

    public function ticker()
    {
        $apiKey  = config('services.openai.api_key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        $project = config('services.openai.project_id');
        $models  = [
            'gpt-5.2-pro',
            'gpt-5.2-chat-latest',
            'gpt-5.2',
            'gpt-5.1',
        ];

        $headers = [];
        if ($project) $headers['OpenAI-Project'] = $project;

        $out = [];
        foreach ($models as $m) {
            $r = Http::withToken($apiKey)
                ->withHeaders($headers)
                ->timeout(20)
                ->get($baseUrl . '/v1/models/' . $m);

            $out[] = [
                'model' => $m,
                'ok' => $r->ok(),
                'status' => $r->status(),
                'error' => $r->json('error.message') ?? null,
            ];
        }

        return response()->json([
            'project' => $project,
            'results' => $out,
        ]);
    }
}
