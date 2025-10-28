<?php

namespace App\Services;

use Illuminate\Support\Str;

class QueryIntent
{
    public static function detect(string $q, array $ctx = []): array
    {
        $norm = self::norm($q.' '.($ctx['ticket_subject'] ?? '').' '.($ctx['category'] ?? ''));
        $has  = fn(array $keys) => collect($keys)->contains(fn($k)=> Str::contains($norm, self::norm($k)));

        // Grupos de intención
        if ($has(['términos', 'terminos', 'condiciones', 't&c', 'tyc'])) {
            return ['domain'=>'terms', 'confidence'=>0.9];
        }
        if ($has(['devolucion', 'devolución', 'garantia', 'garantía', 'reembolso'])) {
            return ['domain'=>'returns', 'confidence'=>0.85];
        }
        if ($has(['envios', 'envíos', 'forma de envío', 'skydropx', 'paquetería'])) {
            return ['domain'=>'shipping', 'confidence'=>0.85];
        }
        if ($has(['privacidad', 'datos personales', 'aviso de privacidad', 'arco'])) {
            return ['domain'=>'privacy', 'confidence'=>0.85];
        }
        if ($has(['factura', 'facturación', 'rfc', 'cfdi', 'pago', 'msi'])) {
            return ['domain'=>'payments', 'confidence'=>0.75];
        }
        if ($has(['pedido', 'orden', 'carrito', 'entrega', 'seguimiento'])) {
            return ['domain'=>'orders', 'confidence'=>0.6];
        }
        if ($has(['producto', 'sku', 'marca', 'precio', 'existencia'])) {
            return ['domain'=>'products', 'confidence'=>0.6];
        }

        return ['domain'=>'general', 'confidence'=>0.3];
    }

    public static function norm(string $s): string
    {
        $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N'];
        $s = strtr($s, $map);
        $s = Str::of($s)->lower()->replaceMatches('/[^a-z0-9 ]/u',' ');
        $s = preg_replace('/\s+/u',' ', (string)$s);
        return trim($s);
    }
}
