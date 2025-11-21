<?php

namespace App\Services\Support;

use Illuminate\Support\Str;

trait TextNormalizeTrait
{
    protected function normalize($s): string
    {
        $s = mb_strtolower($s ?? '');
        if (class_exists('\Normalizer')) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
            $s = preg_replace('~\p{Mn}+~u','',$s);
        }
        return preg_replace('~\s+~u', ' ', trim($s));
    }

    protected function tokens(string $s): array
    {
        $s = $this->normalize($s);
        $s = preg_replace('~[^a-z0-9áéíóúñ#\/\.\-\s]~u',' ', $s);
        $parts = preg_split('~\s+~u', $s) ?: [];
        $stop = ['de','del','la','el','y','en','para','con','sin','tipo','tinta','color','pieza','piezas','pza','pz','mm','cm','m','marca'];
        $parts = array_values(array_filter($parts, fn($t)=>mb_strlen($t)>=3 && !in_array($t,$stop,true)));
        return array_slice(array_unique($parts), 0, 20);
    }

    protected function jaccard(array $a, array $b): float
    {
        if (!$a || !$b) return 0.0;
        $ia = array_intersect($a, $b);
        $ua = array_unique(array_merge($a,$b));
        return count($ia)/max(1,count($ua));
    }

    /** "tok1* tok2*" */
    protected function makeBooleanQueryFromTokens(array $tokens): string
    {
        if (!$tokens) return '';
        $out = [];
        foreach ($tokens as $t) {
            $t = str_replace(['+','-','<','>','(',')','~','"','@'], ' ', (string)$t);
            $t = trim($t);
            if ($t === '' || mb_strlen($t) < 2) continue;
            $out[] = $t . '*';
        }
        return implode(' ', $out);
    }

    protected function genFakeEmailCandidate(string $name): string
    {
        $base = Str::slug(mb_substr($name, 0, 40)) ?: 'cliente';
        return $base.'-'.substr(md5(uniqid('', true)), 0, 6).'-'.time().'@example.com';
    }
}
