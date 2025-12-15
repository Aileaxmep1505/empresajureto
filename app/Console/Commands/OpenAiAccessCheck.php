<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class OpenAiAccessCheck extends Command
{
    protected $signature = 'openai:check';
    protected $description = 'Lista modelos accesibles y prueba retrieval a GPT-5.x';

    public function handle()
    {
        $apiKey  = config('services.openai.api_key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        $project = config('services.openai.project_id');

        if (!$apiKey) {
            $this->error('Falta OPENAI_API_KEY en config/services.php');
            return self::FAILURE;
        }

        $headers = [];
        if ($project) $headers['OpenAI-Project'] = $project;

        $this->info("Base URL: {$baseUrl}");
        $this->info("Project: " . ($project ?: '(no definido)'));

        // 1) LISTAR MODELOS
        $res = Http::withToken($apiKey)
            ->withHeaders($headers)
            ->timeout(30)
            ->get($baseUrl . '/v1/models');

        if (!$res->ok()) {
            $this->error("No pude listar modelos: {$res->status()}");
            $this->line($res->body());
            return self::FAILURE;
        }

        $data = $res->json('data') ?? [];
        $ids  = collect($data)->pluck('id')->filter()->values();

        $this->info("Modelos visibles: " . $ids->count());

        // Muestra solo gpt-5* para que sea rápido
        $gpt5 = $ids->filter(fn($id) => str_starts_with($id, 'gpt-5'))->values();

        if ($gpt5->isEmpty()) {
            $this->warn("⚠️ No veo ningún modelo gpt-5* desde ESTA key/proyecto.");
            $this->line("Ejemplos de los primeros 25 IDs que sí ves:");
            foreach ($ids->take(25) as $id) {
                $this->line(" - {$id}");
            }
            return self::SUCCESS;
        }

        $this->info("Modelos gpt-5* que SÍ ves:");
        foreach ($gpt5 as $id) {
            $this->line(" - {$id}");
        }

        // 2) PROBAR RETRIEVE a los primeros 5 gpt-5*
        $this->info("\nProbando retrieve de 5 modelos gpt-5*:");
        foreach ($gpt5->take(5) as $m) {
            $r = Http::withToken($apiKey)
                ->withHeaders($headers)
                ->timeout(20)
                ->get($baseUrl . '/v1/models/' . $m);

            $ok = $r->ok() ? 'OK' : 'NO';
            $msg = $r->json('error.message') ?? '';
            $this->line(sprintf("%-35s  %-3s (%d) %s", $m, $ok, $r->status(), $msg));
        }

        return self::SUCCESS;
    }
}
