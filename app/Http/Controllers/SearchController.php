<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    protected static ?array $catalogColumns = null;

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if ($q === '') {
            return view('search.index', [
                'query'    => '',
                'products' => collect(),
                'related'  => collect(),
                'total'    => 0,
            ]);
        }

        $tokens       = $this->tokens($q);
        $relatedTerms = $this->relatedTerms($tokens);

        $base = $this->baseQuery();

        $products = (clone $base)
            ->where(function ($query) use ($q, $tokens) {
                $this->applyLooseSearch($query, $q, $tokens);
            })
            ->limit(24)
            ->get()
            ->map(function ($item) {
                $item->search_url   = $this->productUrl($item);
                $item->search_image = $this->productImage($item);
                return $item;
            })
            ->values();

        $related = collect();

        if ($products->count() < 12) {
            $excludeIds = $products->pluck('id')->filter()->values();

            $related = (clone $base)
                ->when($excludeIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $excludeIds))
                ->where(function ($query) use ($tokens, $relatedTerms) {
                    foreach (array_unique(array_merge($tokens, $relatedTerms)) as $term) {
                        $this->applySingleTerm($query, $term);
                    }
                })
                ->limit(18)
                ->get()
                ->map(function ($item) {
                    $item->search_url   = $this->productUrl($item);
                    $item->search_image = $this->productImage($item);
                    return $item;
                })
                ->values();
        }

        if ($products->isEmpty() && $related->isEmpty()) {
            $related = (clone $base)
                ->limit(12)
                ->get()
                ->map(function ($item) {
                    $item->search_url   = $this->productUrl($item);
                    $item->search_image = $this->productImage($item);
                    return $item;
                })
                ->values();
        }

        return view('search.index', [
            'query'    => $q,
            'products' => $products,
            'related'  => $related,
            'total'    => $products->count(),
        ]);
    }

    public function suggest(Request $request)
    {
        $term = trim((string) $request->get('term', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([
                'terms'    => [],
                'products' => [],
            ]);
        }

        $tokens       = $this->tokens($term);
        $relatedTerms = $this->relatedTerms($tokens);

        $products = $this->baseQuery()
            ->where(function ($query) use ($term, $tokens, $relatedTerms) {
                $this->applyLooseSearch($query, $term, array_unique(array_merge($tokens, $relatedTerms)));
            })
            ->limit(6)
            ->get()
            ->map(function ($item) {
                return [
                    'id'   => $item->id,
                    'name' => $item->name,
                    'url'  => $this->productUrl($item),
                ];
            })
            ->values();

        $terms = collect([
            $term,
            ...$this->phraseSuggestions($tokens),
        ])->filter()->unique()->take(6)->values();

        return response()->json([
            'terms'    => $terms,
            'products' => $products,
        ]);
    }

    protected function baseQuery()
    {
        $query = CatalogItem::query()->select($this->selectableColumns());

        if (method_exists(CatalogItem::class, 'published')) {
            $query->published();
        } else {
            if ($this->hasColumn('is_active')) {
                $query->where('is_active', 1);
            }

            if ($this->hasColumn('status')) {
                $query->where(function ($q) {
                    $q->where('status', 1)
                      ->orWhere('status', '1')
                      ->orWhere('status', 'active')
                      ->orWhere('status', 'published');
                });
            }

            if ($this->hasColumn('published_at')) {
                $query->whereNotNull('published_at');
            }
        }

        return $query->orderByDesc('id');
    }

    protected function selectableColumns(): array
    {
        $preferred = [
            'id',
            'name',
            'slug',
            'price',
            'photo_1',
            'photo_2',
            'photo_3',
            'photo_4',
            'image',
            'featured_image',
            'thumbnail',
            'foto',
            'imagen',
            'brand_name',
            'model_name',
            'meli_gtin',
            'excerpt',
            'description',
        ];

        return array_values(array_filter($preferred, fn ($column) => $this->hasColumn($column)));
    }

    protected function applyLooseSearch($query, string $fullText, array $terms): void
    {
        $this->applyLike($query, 'name', $fullText);
        $this->applyLike($query, 'brand_name', $fullText);
        $this->applyLike($query, 'model_name', $fullText);
        $this->applyLike($query, 'excerpt', $fullText);
        $this->applyLike($query, 'description', $fullText);
        $this->applyLike($query, 'meli_gtin', $fullText);

        foreach ($terms as $term) {
            $this->applySingleTerm($query, $term);
        }
    }

    protected function applySingleTerm($query, string $term): void
    {
        if ($term === '') {
            return;
        }

        $this->applyLike($query, 'name', $term);
        $this->applyLike($query, 'brand_name', $term);
        $this->applyLike($query, 'model_name', $term);
        $this->applyLike($query, 'excerpt', $term);
        $this->applyLike($query, 'description', $term);
        $this->applyLike($query, 'meli_gtin', $term);
    }

    protected function applyLike($query, string $column, string $value): void
    {
        if ($value === '' || !$this->hasColumn($column)) {
            return;
        }

        $query->orWhere($column, 'like', '%' . $value . '%');
    }

    protected function tokens(string $text): array
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $parts = preg_split('/\s+/', trim($text));

        $stopwords = [
            'de', 'del', 'la', 'las', 'el', 'los', 'y', 'en', 'para', 'por', 'con',
            'un', 'una', 'unos', 'unas', 'a'
        ];

        return collect($parts)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->reject(fn ($v) => in_array($v, $stopwords, true))
            ->filter(fn ($v) => mb_strlen($v) >= 2)
            ->unique()
            ->values()
            ->all();
    }

    protected function relatedTerms(array $tokens): array
    {
        $dictionary = [
            'hojas'    => ['papel', 'bond', 'carta', 'oficio', 'blancas', 'colores'],
            'hoja'     => ['papel', 'bond', 'carta', 'oficio', 'blancas', 'colores'],
            'papel'    => ['hojas', 'bond', 'carta', 'oficio', 'blancas', 'colores'],
            'bond'     => ['papel', 'hojas', 'carta', 'blancas'],
            'carta'    => ['papel', 'hojas', 'bond'],
            'oficio'   => ['papel', 'hojas', 'bond'],
            'colores'  => ['color', 'papel', 'hojas', 'blancas', 'carta'],
            'color'    => ['colores', 'papel', 'hojas'],
            'blancas'  => ['blanca', 'papel', 'hojas', 'bond'],
            'blanca'   => ['blancas', 'papel', 'hojas', 'bond'],
            'libreta'  => ['cuaderno', 'escolar', 'papeleria'],
            'cuaderno' => ['libreta', 'escolar', 'papeleria'],
            'lapiz'    => ['lapices', 'escritura', 'papeleria'],
            'lapices'  => ['lapiz', 'escritura', 'papeleria'],
            'pluma'    => ['boligrafo', 'escritura'],
            'boligrafo'=> ['pluma', 'escritura'],
            'tinta'    => ['cartucho', 'toner', 'impresion'],
            'toner'    => ['tinta', 'cartucho', 'impresion'],
            'cartucho' => ['tinta', 'toner', 'impresion'],
        ];

        $out = [];

        foreach ($tokens as $token) {
            if (isset($dictionary[$token])) {
                $out = array_merge($out, $dictionary[$token]);
            }
        }

        return collect($out)->unique()->values()->all();
    }

    protected function phraseSuggestions(array $tokens): array
    {
        $phrases = [];

        if (in_array('papel', $tokens, true) || in_array('hojas', $tokens, true)) {
            $phrases = array_merge($phrases, [
                'papel bond',
                'hojas carta',
                'hojas blancas',
            ]);
        }

        if (in_array('colores', $tokens, true) || in_array('color', $tokens, true)) {
            $phrases = array_merge($phrases, [
                'hojas de colores',
                'papel de colores',
                'papel bond',
            ]);
        }

        if (in_array('bond', $tokens, true)) {
            $phrases = array_merge($phrases, [
                'papel bond',
                'hojas bond',
                'hojas carta',
            ]);
        }

        return collect($phrases)->unique()->values()->all();
    }

    protected function productUrl($product): string
    {
        if (Route::has('producto.show')) {
            return route('producto.show', $product->id);
        }

        if (Route::has('products.show')) {
            return route('products.show', $product->id);
        }

        if (Route::has('web.product.show')) {
            return route('web.product.show', $product->id);
        }

        if (Route::has('web.producto.show')) {
            return route('web.producto.show', $product->id);
        }

        if (!empty($product->slug)) {
            return url('/producto/' . $product->slug);
        }

        return url('/producto/' . $product->id);
    }

    protected function productImage($product): ?string
    {
        foreach (['photo_1', 'photo_2', 'photo_3', 'photo_4', 'image', 'featured_image', 'thumbnail', 'foto', 'imagen'] as $field) {
            if ($this->hasColumn($field) && !empty($product->{$field})) {
                return $product->{$field};
            }
        }

        return null;
    }

    protected function hasColumn(string $column): bool
    {
        if (self::$catalogColumns === null) {
            self::$catalogColumns = Schema::getColumnListing('catalog_items');
        }

        return in_array($column, self::$catalogColumns, true);
    }
}