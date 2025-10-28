<?php

namespace App\Services;

use App\Services\RagSearch;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class AIAssistant
{
    public static function answer(string $question, array $context = []): string
    {
        $hits = RagSearch::search($question, 6);

        // UMBRAL más permisivo para activar respuesta con tus fuentes
        $threshold = 0.45;
        $bestScore = 0.0;
        foreach ($hits as $h) { $bestScore = max($bestScore, (float)$h['score']); }

        // Si hay buenas fuentes o al menos 1 match de keyword => responde con RAG
        if (!empty($hits) && $bestScore >= $threshold) {
            // Si HAY OpenAI, pedimos una respuesta “bonita” citando fuentes
            if (config('services.openai.key')) {
                $system = "Eres agente de soporte de Grupo Medibuy/Jureto. " .
                          "Responde en español, conciso y accionable. " .
                          "Usa EXCLUSIVAMENTE la información de las Fuentes y al final cita [1], [2] con título/URL. " .
                          "Si falta algo, dilo y sugiere escalar a humano.";
                $sourcesText = self::formatSources($hits);
                $prompt = "Pregunta del usuario: {$question}\n\nFuentes:\n{$sourcesText}\n\nInstrucciones: Contesta citando [1], [2].";

                $out = self::complete($system, $prompt);
                if ($out) return $out;
            }

            // Si NO hay OpenAI: fallback local (sin LLM): armamos respuesta con snippets
            return self::localSynthesis($question, $hits);
        }

        // Sin fuentes útiles => fallback general
        $fallback = self::complete(
            "Eres soporte. Si no tienes datos, sugiere escalar. Sé breve.",
            "Usuario: {$question}\nContexto: ".json_encode($context)
        );

        return $fallback ?: "No encontré datos suficientes en nuestra base. Pulsa **“Contactar a un humano”** y te atendemos.";
    }

    protected static function complete(string $system, string $user): ?string
    {
        $key = config('services.openai.key');
        $model = config('services.openai.model','gpt-4o-mini');
        if (!$key) return null;

        $resp = Http::withHeaders([
            'Authorization'=>"Bearer {$key}",
            'Content-Type'=>'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model'=>$model,
            'messages'=>[
                ['role'=>'system','content'=>$system],
                ['role'=>'user','content'=>$user],
            ],
            'temperature'=>0.2,
            'max_tokens'=>600,
        ]);

        if (!$resp->ok()) return null;
        return trim((string)data_get($resp->json(),'choices.0.message.content'));
    }

    protected static function formatSources(array $hits): string
    {
        $i=1; $lines=[];
        foreach ($hits as $h) {
            $d = $h['doc'];
            $tag = $d->title ?: $d->url ?: ($d->source_type.'#'.$d->source_id);
            $snippet = Str::limit($d->content, 900, '…');
            $lines[] = "[{$i}] {$tag}".($d->url ? " ({$d->url})" : "")."\n---\n".$snippet;
            $i++;
        }
        return implode("\n\n", $lines);
    }

    /**
     * Resumen local sin LLM: arma bullets con fragmentos y referencias.
     */
    protected static function localSynthesis(string $question, array $hits): string
    {
        $out = [];
        $out[] = "Esto es lo que encontré en nuestra base sobre “{$question}”:";
        $i=1;
        foreach ($hits as $h) {
            $d = $h['doc'];
            $title = $d->title ?: ucfirst($d->source_type).' '.$d->source_id;
            $url = $d->url ? " — ".$d->url : "";
            // Tomo 300 chars de contexto
            $snippet = Str::limit(trim($d->content), 300, '…');
            $out[] = "• [{$i}] {$title}{$url}\n   {$snippet}";
            $i++;
            if ($i>4) break; // no saturar
        }
        $out[] = "\nSi necesitas más detalle, dime y profundizo o podemos escalar a un humano. 💬";
        return implode("\n", $out);
    }
}
