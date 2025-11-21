<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Support\TextNormalizeTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ProductCatalogService
{
    use TextNormalizeTrait;

    private array $poolCache = [];

    public function coalesceExpr(string $table, array $candidates, string $fallbackExpr="NULL"): string
    {
        $cols = array_values(array_filter($candidates, fn($c)=>Schema::hasColumn($table,$c)));
        return $cols ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$cols)).", $fallbackExpr)" : $fallbackExpr;
    }

    /** Productos para create/edit de cotización */
    public function getProductosForCotizacion(): \Illuminate\Support\Collection
    {
        $prodNameCols = array_values(array_filter(['nombre','name','descripcion','titulo','title'], fn($c)=>Schema::hasColumn('products',$c)));
        $prodNameExpr = $prodNameCols
            ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$prodNameCols)).", CONCAT('ID ', `id`))"
            : "CONCAT('ID ', `id`)";

        $costCols = array_values(array_filter(['cost','costo','precio_costo','precio_compra'], fn($c)=>Schema::hasColumn('products',$c)));
        $costExpr = $costCols ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$costCols)).',0)' : '0';

        $priceCols = array_values(array_filter(['price','precio','precio_unitario'], fn($c)=>Schema::hasColumn('products',$c)));
        $priceExpr = $priceCols ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$priceCols)).',0)' : '0';

        $brandExpr    = $this->coalesceExpr('products',['brand','marca'],"NULL");
        $categoryExpr = $this->coalesceExpr('products',['category','categoria'],"NULL");
        $colorExpr    = $this->coalesceExpr('products',['color','colour'],"NULL");
        $matExpr      = $this->coalesceExpr('products',['material'],"NULL");
        $imgExpr      = $this->coalesceExpr('products',['image','imagen','foto','thumb','thumbnail','image_path'],"NULL");
        $stockExpr    = $this->coalesceExpr('products',['stock','existencia'],"NULL");

        return Product::query()
            ->select([
                'id',
                DB::raw("$prodNameExpr AS display"),
                DB::raw("$costExpr AS cost"),
                DB::raw("$priceExpr AS price"),
                DB::raw("$brandExpr AS brand"),
                DB::raw("$categoryExpr AS category"),
                DB::raw("$colorExpr AS color"),
                DB::raw("$matExpr AS material"),
                DB::raw("$imgExpr AS image"),
                DB::raw("$stockExpr AS stock"),
            ])
            ->orderByRaw($prodNameExpr)
            ->get();
    }

    /** Query base de búsqueda reutilizable */
    public function productSearchQuery(string $queryText)
    {
        $qText = trim($queryText);
        $cols = [];
        foreach ([
            'name','nombre','descripcion','category','categoria','brand','marca',
            'color','material','sku','supplier_sku','tags','unit','unidad'
        ] as $c) {
            if (Schema::hasColumn('products', $c)) $cols[] = $c;
        }
        if (!$cols) $cols = ['id'];

        $tokens = $this->tokens($qText);
        $qb = Product::query()->select('*');

        if ($qText !== '') {
            $ftColsReal = $this->getFulltextColumns('products');
            $ftUsables  = array_values(array_intersect($cols, $ftColsReal));
            if (count($ftUsables) >= 1) {
                $ftCols = implode(',', array_map(fn($c)=>$c, $ftUsables));
                $boolean = $this->makeBooleanQueryFromTokens($tokens);
                if ($boolean !== '') {
                    $qb->whereRaw("MATCH($ftCols) AGAINST (? IN BOOLEAN MODE)", [$boolean]);
                    return $qb;
                }
            }
        }

        if (empty($tokens)) return $qb;

        foreach ($tokens as $t) {
            $qb->where(function($w) use ($cols, $t) {
                foreach ($cols as $c) {
                    $w->orWhere($c, 'LIKE', "%{$t}%");
                }
            });
        }

        return $qb;
    }

    public function getFulltextColumns(string $table): array
    {
        static $cache = [];
        if (isset($cache[$table])) return $cache[$table];
        try {
            $db = DB::getDatabaseName();
            $rows = DB::select("
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_TYPE = 'FULLTEXT'
            ", [$db, $table]);
            $cache[$table] = array_values(array_unique(array_map(fn($r)=>$r->COLUMN_NAME, $rows)));
            return $cache[$table];
        } catch (\Throwable $e) {
            Log::info('FT detect fallback', ['msg'=>$e->getMessage()]);
            return [];
        }
    }

    /** Pool ligero para IA */
    public function getProductPool(): array
    {
        if (isset($this->poolCache['pool'])) return $this->poolCache['pool'];

        $limit = (int) env('AI_PRODUCT_POOL_LIMIT', 25000);
        $cols = ['id'];
        foreach (['name','nombre','descripcion','category','categoria','brand','marca','color','material','sku','supplier_sku','tags','unit','unidad','pieces_per_unit','price','precio'] as $c) {
            if (Schema::hasColumn('products',$c)) $cols[]=$c;
        }

        $rows = Product::query()->select(array_unique($cols))->limit($limit)->get();

        $pool = [];
        foreach ($rows as $p) {
            $display = ($p->nombre ?? $p->name ?? ('ID '.$p->id));
            $price   = (float)($p->price ?? $p->precio ?? 0);
            $blob    = implode(' ', array_map(fn($v)=> (string)$v, array_filter([
                $p->name ?? null, $p->nombre ?? null, $p->descripcion ?? null,
                $p->category ?? null, $p->categoria ?? null, $p->brand ?? null, $p->marca ?? null,
                $p->color ?? null, $p->material ?? null, $p->sku ?? null, $p->supplier_sku ?? null, $p->tags ?? null,
                $p->unit ?? null, $p->unidad ?? null
            ])));
            $blobNorm = $this->normalize($blob);
            $pool[] = [
                'id'      => $p->id,
                'display' => $display,
                'price'   => $price,
                'tokens'  => $this->tokens($blobNorm),
                'blob'    => $blobNorm,
            ];
        }

        return $this->poolCache['pool'] = $pool;
    }

    /** Top candidatos para un item IA */
    public function topCandidatesForRow(array $row, int $limit=3): array
    {
        $queryText = trim(($row['nombre'] ?? '').' '.($row['descripcion'] ?? ''));
        if ($queryText==='') return [];

        $qTokens = $this->tokens($queryText);

        $cols = ['id'];
        foreach (['name','nombre','descripcion','category','categoria','brand','marca','color','material','sku','supplier_sku','tags','unit','unidad','pieces_per_unit','price','precio'] as $c) {
            if (Schema::hasColumn('products',$c)) $cols[]=$c;
        }

        $q = $this->productSearchQuery($queryText)->select(array_unique($cols));
        $cands = $q->take(2000)->get();

        if ($cands->isEmpty()) {
            $cands = Product::query()->select(array_unique($cols))->take(5000)->get();
        }
        if ($cands->isEmpty()) return [];

        $unitPdf = $this->normalize((string)($row['unidad'] ?? ''));
        $scored = [];
        foreach ($cands as $p) {
            $bag=[];
            foreach (['name','nombre','descripcion','category','categoria','brand','marca','color','material','sku','supplier_sku','tags'] as $c) {
                if (!empty($p->{$c})) $bag[] = (string)$p->{$c};
            }
            $pTokens = $this->tokens(implode(' ', $bag));
            $score = $this->jaccard($qTokens,$pTokens);

            foreach (['unit','unidad'] as $uCol) {
                if ($unitPdf && Schema::hasColumn('products',$uCol) && !empty($p->{$uCol})) {
                    if (str_starts_with($this->normalize($p->{$uCol}), $unitPdf)) $score += 0.05;
                }
            }

            $price = (float)($p->price ?? $p->precio ?? 0);
            $display = ($p->nombre ?? $p->name ?? 'ID '.$p->id);

            $scored[] = ['id'=>$p->id, 'display'=>$display, 'price'=>$price, 'score'=>$score];
        }

        usort($scored, function($a,$b){
            if (abs($a['score'] - $b['score']) > 0.0001) return ($a['score'] < $b['score']) ? 1 : -1;
            return $a['price'] <=> $b['price'];
        });

        if (empty($scored)) {
            $fallback = Product::query()
                ->select([
                    'id',
                    DB::raw("COALESCE(nombre,name,CONCAT('ID ',id)) as display"),
                    DB::raw("COALESCE(price,precio,0) as price")
                ])
                ->take(max(3,$limit))->get();

            return $fallback->map(fn($p)=>[
                'id'=>$p->id,'display'=>$p->display,'price'=>(float)$p->price,'score'=>0.0
            ])->all();
        }

        return array_slice($scored, 0, max(1,$limit));
    }
}
