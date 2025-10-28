<?php

namespace App\Services;

use App\Models\KnowledgeDocument;
use Illuminate\Support\Str;

class RagSearch
{
    /**
     * @param string $query
     * @param int    $k
     * @param array  $opts ['allow'=>['pages','static'], 'deny'=>['products']]
     * @return array<int, array{doc:KnowledgeDocument, score:float}>
     */
    public static function search(string $query, int $k = 6, array $opts = []): array
    {
        $tokens = self::tokens($query);

        $base = KnowledgeDocument::query()->where('is_active', true);

        // Filtros por tipo de fuente
        if (!empty($opts['allow'])) {
            $base->whereIn('source_type', (array)$opts['allow']);
        }
        if (!empty($opts['deny'])) {
            $base->whereNotIn('source_type', (array)$opts['deny']);
        }

        // Prefiltro keyword
        if ($tokens) {
            $base->select('*')->selectRaw(
                '(' . collect($tokens)->map(fn($t)=>"CASE WHEN content LIKE ? THEN 1 ELSE 0 END")->implode(' + ') . ') AS kwscore',
                collect($tokens)->map(fn($t)=>'%'.$t.'%')->all()
            )->orderByDesc('kwscore')->orderByDesc('updated_at');
        } else {
            $base->latest('updated_at');
        }

        $candidates = $base->limit(250)->get();

        // Intent para re-ranqueo
        $intent = QueryIntent::detect($query);
        $domain = $intent['domain'] ?? 'general';

        // Scoring con o sin embeddings
        $qvec = Embeddings::embed($query);
        $scored = [];

        foreach ($candidates as $doc) {
            $dscore = 0.0;

            if (!empty($qvec)) {
                $dvec = $doc->embeddingArray();
                $sim  = $dvec ? Embeddings::cosine($qvec, $dvec) : 0.0;
                $dscore = $sim;
            } else {
                $kw = (float)($doc->kwscore ?? 0);
                $dscore = min(0.35 + 0.18 * $kw, 0.90); // base sin embeddings
            }

            // Re-ranqueo por dominio
            $titleNorm = QueryIntent::norm(($doc->title ?? '').' '.($doc->content ?? ''));
            $boost = 0.0;

            // Para dominios de políticas, BOOST a páginas/estáticos y términos clave
            if (in_array($domain, ['terms','returns','shipping','privacy'])) {
                if (in_array($doc->source_type, ['pages','static'])) $boost += 0.18;
                if (Str::contains($titleNorm, ['terminos','condiciones','devolucion','garantia','envio','privacidad'])) $boost += 0.12;
                if ($doc->source_type === 'products') $boost -= 0.40; // castigo a productos
            }

            // Para productos, boost a 'products'
            if ($domain === 'products' && $doc->source_type === 'products') {
                $boost += 0.18;
            }

            $score = max(0.0, min(1.0, $dscore + $boost));
            $scored[] = ['doc' => $doc, 'score' => $score];
        }

        usort($scored, fn($a,$b)=> $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $k);
    }

    public static function tokens(string $q): array
    {
        $norm = QueryIntent::norm($q);
        $parts = preg_split('/\s+/u', $norm);
        $parts = array_filter($parts, fn($w)=> mb_strlen($w)>=3);
        $parts = array_values(array_unique($parts));
        return $parts;
    }
}
