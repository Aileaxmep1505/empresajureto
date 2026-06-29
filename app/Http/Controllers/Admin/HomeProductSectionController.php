<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\CategoryProduct;
use App\Models\HomeProductSection;
use App\Models\HomeProductSectionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeProductSectionController extends Controller
{
    public function index()
    {
        $sections = HomeProductSection::query()
            ->with(['categoryProduct'])
            ->withCount('items')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.home-product-sections.index', compact('sections'));
    }

    public function create()
    {
        $section = new HomeProductSection([
            'source_type' => 'manual',
            'products_limit' => 12,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return view('admin.home-product-sections.form', [
            'section' => $section,
            'categories' => $this->getCategories(),
            'products' => $this->getProducts(),
            'selectedProducts' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSection($request);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $data['is_active'] = $request->boolean('is_active');

        if ($data['source_type'] === 'manual') {
            $data['category_product_id'] = null;
        }

        DB::transaction(function () use ($request, $data) {
            $section = HomeProductSection::create($data);

            if ($section->source_type === 'manual') {
                $this->syncProducts($section, $request->input('products', []));
            }
        });

        return redirect()
            ->route('admin.home-product-sections.index')
            ->with('success', 'Fila de productos creada correctamente.');
    }

    public function edit(HomeProductSection $homeProductSection)
    {
        $homeProductSection->load(['items.product']);

        return view('admin.home-product-sections.form', [
            'section' => $homeProductSection,
            'categories' => $this->getCategories(),
            'products' => $this->getProducts(),
            'selectedProducts' => $homeProductSection->items->pluck('catalog_item_id'),
        ]);
    }

    public function update(Request $request, HomeProductSection $homeProductSection)
    {
        $data = $this->validateSection($request, $homeProductSection->id);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $data['is_active'] = $request->boolean('is_active');

        if ($data['source_type'] === 'manual') {
            $data['category_product_id'] = null;
        }

        DB::transaction(function () use ($request, $homeProductSection, $data) {
            $homeProductSection->update($data);

            if ($homeProductSection->source_type === 'manual') {
                $this->syncProducts($homeProductSection, $request->input('products', []));
            } else {
                $homeProductSection->items()->delete();
            }
        });

        return redirect()
            ->route('admin.home-product-sections.index')
            ->with('success', 'Fila de productos actualizada correctamente.');
    }

    public function destroy(HomeProductSection $homeProductSection)
    {
        $homeProductSection->delete();

        return redirect()
            ->route('admin.home-product-sections.index')
            ->with('success', 'Fila eliminada correctamente.');
    }

    private function validateSection(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = 'unique:home_product_sections,slug';

        if ($ignoreId) {
            $slugRule .= ',' . $ignoreId;
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', $slugRule],
            'subtitle' => ['nullable', 'string', 'max:180'],

            'source_type' => ['required', 'in:manual,category'],
            'category_product_id' => ['nullable', 'integer', 'exists:category_products,id'],

            'products_limit' => ['required', 'integer', 'min:1', 'max:40'],

            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'sort_order' => ['nullable', 'integer', 'min:0'],

            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:catalog_items,id'],
        ], [
            'title.required' => 'El título es obligatorio.',
            'source_type.required' => 'Selecciona cómo se llenará la fila.',
            'category_product_id.exists' => 'La categoría seleccionada no existe.',
            'products.*.exists' => 'Uno de los productos seleccionados no existe.',
            'ends_at.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ]);
    }

    private function syncProducts(HomeProductSection $section, array $products): void
    {
        $section->items()->delete();

        $products = collect($products)
            ->filter()
            ->unique()
            ->values();

        foreach ($products as $index => $productId) {
            HomeProductSectionItem::create([
                'home_product_section_id' => $section->id,
                'catalog_item_id' => $productId,
                'sort_order' => $index,
            ]);
        }
    }

    private function getCategories()
    {
        return CategoryProduct::query()
            ->orderBy('full_path')
            ->get();
    }

    private function getProducts()
    {
        return CatalogItem::query()
            ->with(['categoryProduct'])
            ->where('status', 1)
            ->where('is_sample', false)
            ->orderByDesc('id')
            ->limit(300)
            ->get();
    }
}