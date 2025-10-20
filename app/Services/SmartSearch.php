<?php

namespace App\Services;

use App\Models\CatalogItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

/**
 * Búsqueda segura contra schema de `catalog_items`
 * Campos usados: name, slug, sku, excerpt, description, price, sale_price,
 *                status, published_at, deleted_at, created_at, updated_at, image_url
 */
class SmartSearch
{
    /**
     * Si tienes un cliente de IA para expandir términos/sugerencias,
     * decláralo así y será opcional.
     */
    public function __construct(private ?SearchAiClient $ai = null) {}

    /**
     * Busca en CatalogItem con filtros y orden soportados por tu tabla.
     * $filters: ['disponible'=>bool, 'envio'=>bool, 'express'=>bool, 'msi'=>bool, 'club'=>bool]
     * (Los no soportados se ignoran silenciosamente)
     *
     * $order: sugerido | precio_asc | precio_desc | recientes
     */
    public function search(string $query, array $filters = [], string $order = 'sugerido'): LengthAwarePaginator
    {
        $q = trim($query);

        // Expansión de términos (opcional con IA)
        $expanded = $q && $this->ai ? ($this->ai->expand($q) ?? []) : [];
        $terms = collect([$q])->merge($expanded)->filter()->unique()->values();

        $qb = CatalogItem::query()->select([
            'id','name','slug','sku',
            'price','sale_price',
            'image_url','images',
            'status','is_featured','brand_id','category_id',
            'excerpt','description',
            'published_at','deleted_at','created_at','updated_at'
        ]);

        // ===== Texto: LIKE seguro sobre columnas reales
        if ($terms->isNotEmpty()) {
            $qb->where(function(Builder $where) use ($terms) {
                foreach ($terms as $t) {
                    $like = '%' . str_replace(' ', '%', $t) . '%';
                    $where->orWhere('name', 'like', $like)
                          ->orWhere('slug', 'like', $like)
                          ->orWhere('sku',  'like', $like)
                          ->orWhere('excerpt', 'like', $like)
                          ->orWhere('description', 'like', $like);
                }
            });
        }

        // ===== Filtros soportados en tu schema
        // disponible => publicado y no eliminado (heurística)
        if (!empty($filters['disponible'])) {
            $qb->whereNull('deleted_at')
               ->where(function ($q2) {
                   $q2->whereNotNull('published_at')
                      ->where('published_at', '<=', now());
               });
            // Opcional: si usas status para publicar
            $qb->when($this->hasColumn('status'), function ($q3) {
                $q3->whereIn('status', ['published','active','1',1,'enabled']); // ajusta a tus valores reales
            });
        }

        // Estos filtros no existen en tu tabla; se ignoran sin romper:
        // 'envio', 'express', 'msi', 'club'

        // ===== Orden
        switch ($order) {
            case 'precio_asc':
                $qb->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'precio_desc':
                $qb->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'recientes':
                // prioriza publicados más recientes y luego creados
                $qb->orderByRaw('published_at IS NULL') // los NULL al final
                   ->orderBy('published_at', 'desc')
                   ->orderBy('created_at', 'desc');
                break;
            default: // sugerido
                // Pequeño score por coincidencia en name/sku + frescura
                if ($q !== '') {
                    $esc = str_replace(['%','_'], ['\%','\_'], $q);
                    $qb->addSelect([
                        \DB::raw("
                            (
                                (CASE
                                    WHEN name LIKE '{$esc}%' THEN 100
                                    WHEN name LIKE '% {$esc} %' THEN 60
                                    WHEN name LIKE '%{$esc}%' THEN 30
                                    ELSE 0
                                 END)
                                +
                                (CASE
                                    WHEN sku = '{$esc}' THEN 80
                                    WHEN sku LIKE '{$esc}%' THEN 50
                                    WHEN sku LIKE '%{$esc}%' THEN 20
                                    ELSE 0
                                 END)
                            ) as _score
                        ")
                    ])
                    ->orderByDesc('_score');
                }
                // Frescura como desempate
                $qb->orderByRaw('published_at IS NULL')
                   ->orderBy('published_at','desc')
                   ->orderBy('created_at','desc');
                break;
        }

        return $qb->paginate(24);
    }

    public function expandOnly(string $q): Collection
    {
        $q = trim($q);
        if ($q === '') return collect();

        if ($this->ai) {
            return collect($this->ai->expand($q) ?? [])->take(12);
        }

        // Fallback simple: tokens únicos
        return collect(preg_split('/\s+/', mb_strtolower($q)))
            ->filter()
            ->unique()
            ->take(12);
    }

    public function suggest(string $seed): array
    {
        $seed = trim($seed);
        if ($seed === '') return [];

        if ($this->ai) {
            return $this->ai->suggest($seed) ?? [];
        }

        // Fallback simple: devolver prefijos y variantes
        $s = mb_strtolower($seed);
        $out = [$s];
        if (mb_strlen($s) > 3) {
            $out[] = Str::plural($s);
        }
        return array_values(array_unique($out));
    }

    /** Helper para comprobar existencia de columna en runtime (opcional) */
    protected function hasColumn(string $col): bool
    {
        static $cache = null;
        if ($cache === null) {
            $cache = \Schema::getColumnListing((new CatalogItem)->getTable());
        }
        return in_array($col, $cache, true);
    }
}
