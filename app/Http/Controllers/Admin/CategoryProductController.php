<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryProduct;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class CategoryProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function roots()
    {
        $items = CategoryProduct::query()
            ->active()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'ok' => true,
            'items' => $items->map(fn ($item) => $this->mapNode($item))->values(),
        ]);
    }

    public function children(CategoryProduct $category)
    {
        $category->load([
            'parent',
            'children' => fn ($q) => $q->active()->orderBy('sort_order')->orderBy('name'),
        ]);

        return response()->json([
            'ok' => true,
            'current' => $this->mapNode($category, true),
            'items' => $category->children->map(fn ($item) => $this->mapNode($item))->values(),
            'breadcrumb' => $category->breadcrumb_array,
        ]);
    }

    public function show(CategoryProduct $category)
    {
        $category->load('parent', 'children');

        return response()->json([
            'ok' => true,
            'item' => $this->mapNode($category, true),
            'breadcrumb' => $category->breadcrumb_array,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:category_products,id'],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $baseSlug = Str::slug($data['name']);
        if (blank($baseSlug)) {
            $baseSlug = 'categoria';
        }

        $slug = $baseSlug;
        $i = 1;
        while (CategoryProduct::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        $category = CategoryProduct::create([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $slug,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        $category->refresh();

        return response()->json([
            'ok' => true,
            'item' => $this->mapNode($category, true),
            'breadcrumb' => $category->breadcrumb_array,
            'message' => 'Categoría creada correctamente.',
        ]);
    }

    private function mapNode(CategoryProduct $item, bool $withMeta = false): array
    {
        $childrenCount = $item->children()->count();

        $data = [
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'parent_id' => $item->parent_id,
            'full_path' => $item->full_path,
            'has_children' => $childrenCount > 0,
            'children_count' => $childrenCount,
        ];

        if ($withMeta) {
            $data['breadcrumb'] = $item->breadcrumb_array;
            $data['display_path'] = $item->display_path;
        }

        return $data;
    }
}