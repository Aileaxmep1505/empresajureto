<?php

namespace App\Services;

use App\Models\KnowledgeDocument;
use Illuminate\Support\Str;

class RagSearch
{
    /**
     * Busca en knowledge_documents con keyword prefilter + similitud (si hay embeddings).
     * Devuelve top-K con score normalizado [0..1].
     * @return array<int, array{doc:KnowledgeDocument, score:float}>
     */
    public static function search(string $query, int $k = 6): array
    {
        $tokens = self::tokens($query);

        // Prefiltro por keywords; calculamos un "kwscore" (#matches por chunk)
        $base = KnowledgeDocument::query()->where('is_active', true);

        if ($tokens) {
            $base->select('*')->selectRaw(
                '(' . collect($tokens)->map(fn($t)=>"CASE WHEN content LIKE ? THEN 1 ELSE 0 END")->implode(' + ') . ') AS kwscore',
                collect($tokens)->map(fn($t)=>'%'.$t.'%')->all()
            )->orderByDesc('kwscore')->orderByDesc('updated_at');
        } else {
            $base->latest('updated_at');
        }

        $candidates = $base->limit(250)->get();

        // Si NO hay API key o embeddings del query => hacemos scoring SOLO por keywords
        $qvec = Embeddings::embed($query);
        if (empty($qvec)) {
            $scored = [];
            foreach ($candidates as $doc) {
                $kw = (float)($doc->kwscore ?? 0);
                // Convierto kwscore (0..N) a [0.35 .. 0.90] para que si hay al menos 1 match pase el threshold
                $score = min(0.35 + 0.18 * $kw, 0.90);
                $scored[] = ['doc' => $doc, 'score' => $score];
            }
            usort($scored, fn($a,$b)=> $b['score'] <=> $a['score']);
            return array_slice($scored, 0, $k);
        }

        // Sí hay vector de query: mezclamos similitud coseno + pequeño bonus por kw
        $scored = [];
        foreach ($candidates as $doc) {
            $dvec  = $doc->embeddingArray();
            $sim   = $dvec ? Embeddings::cosine($qvec, $dvec) : 0.0; // 0..1 aprox
            $kw    = (float)($doc->kwscore ?? 0);
            $score = $sim + min($kw * 0.03, 0.20); // bonus por kw
            $scored[] = ['doc' => $doc, 'score' => $score];
        }

        usort($scored, fn($a,$b)=> $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $k);
    }

    public static function tokens(string $q): array
    {
        $q = Str::of($q)->lower()->replaceMatches('/[^a-z0-9áéíóúñ ]/u',' ');
        $parts = preg_split('/\s+/u', (string)$q);
        $parts = array_filter($parts, fn($w)=> mb_strlen($w)>=3);
        return array_values(array_unique($parts));
    }
}
