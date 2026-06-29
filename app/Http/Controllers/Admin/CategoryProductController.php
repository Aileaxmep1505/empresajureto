<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryProduct;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CategoryProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VISTAS ADMIN
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $search = trim((string) $request->get('s', ''));

        /*
        |--------------------------------------------------------------------------
        | Categorías para listado rápido
        |--------------------------------------------------------------------------
        */
        $categories = CategoryProduct::query()
            ->with(['parent'])
            ->withCount(['catalogItems', 'children'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('full_path', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Categorías para mapa visual completo
        |--------------------------------------------------------------------------
        | Aquí NO paginamos, porque el flujo debe mostrar:
        | Categoría > subcategoría > sub-subcategoría > ... hasta el último nivel.
        */
        $treeCategories = CategoryProduct::query()
            ->with(['parent'])
            ->withCount(['catalogItems', 'children'])
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Cuando hay búsqueda, mostramos el camino completo:
        | - coincidencias
        | - sus padres/ancestros
        | - sus hijos/descendientes
        |--------------------------------------------------------------------------
        */
        if ($search !== '') {
            $normalizedSearch = mb_strtolower($search);

            $matchedIds = $treeCategories
                ->filter(function ($category) use ($normalizedSearch) {
                    return str_contains(mb_strtolower((string) $category->name), $normalizedSearch)
                        || str_contains(mb_strtolower((string) $category->slug), $normalizedSearch)
                        || str_contains(mb_strtolower((string) $category->full_path), $normalizedSearch);
                })
                ->pluck('id')
                ->values();

            $visibleIds = collect();

            foreach ($matchedIds as $matchedId) {
                $this->collectCategoryFamilyIds($treeCategories, (int) $matchedId, $visibleIds);
            }

            $treeCategories = $treeCategories
                ->whereIn('id', $visibleIds->unique()->values())
                ->values();
        }

        $totalAllCategories = CategoryProduct::query()->count();
        $activeAllCategories = CategoryProduct::query()->where('is_active', true)->count();
        $rootAllCategories = CategoryProduct::query()->whereNull('parent_id')->count();

        return view('admin.category-products.index', compact(
            'categories',
            'treeCategories',
            'search',
            'totalAllCategories',
            'activeAllCategories',
            'rootAllCategories'
        ));
    }

    public function create()
    {
        $category = new CategoryProduct([
            'parent_id' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $parentCategories = $this->parentCategories();

        return view('admin.category-products.form', compact('category', 'parentCategories'));
    }

    public function edit(CategoryProduct $categoryProduct)
    {
        $category = $categoryProduct;

        $parentCategories = $this->parentCategories($category->id);

        return view('admin.category-products.form', compact('category', 'parentCategories'));
    }

    public function update(Request $request, CategoryProduct $categoryProduct)
    {
        $data = $this->validateCategory($request, $categoryProduct->id);

        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug($data['name']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if (blank($data['slug'])) {
            $data['slug'] = 'categoria';
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['parent_id'] = $data['parent_id'] ?? null;

        $categoryProduct->update($data);
        $categoryProduct->refreshFullPathRecursively();

        if ($request->expectsJson()) {
            $categoryProduct->refresh();

            return response()->json([
                'ok' => true,
                'item' => $this->mapNode($categoryProduct, true),
                'breadcrumb' => $categoryProduct->breadcrumb_array,
                'message' => 'Categoría actualizada correctamente.',
            ]);
        }

        return redirect()
            ->route('admin.category-products.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Request $request, CategoryProduct $categoryProduct)
    {
        if ($categoryProduct->children()->exists()) {
            $message = 'No puedes eliminar esta categoría porque tiene subcategorías.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('admin.category-products.index')
                ->with('error', $message);
        }

        if ($categoryProduct->catalogItems()->exists()) {
            $message = 'No puedes eliminar esta categoría porque tiene productos asignados.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('admin.category-products.index')
                ->with('error', $message);
        }

        $categoryProduct->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Categoría eliminada correctamente.',
            ]);
        }

        return redirect()
            ->route('admin.category-products.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | API JSON EXISTENTE
    |--------------------------------------------------------------------------
    */

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
        $data = $this->validateCategory($request);

        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug($data['name']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if (blank($data['slug'])) {
            $data['slug'] = 'categoria';
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['parent_id'] = $data['parent_id'] ?? null;

        $category = CategoryProduct::create($data);
        $category->refreshFullPath();

        $category->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'item' => $this->mapNode($category, true),
                'breadcrumb' => $category->breadcrumb_array,
                'message' => 'Categoría creada correctamente.',
            ]);
        }

        return redirect()
            ->route('admin.category-products.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDACIONES Y HELPERS
    |--------------------------------------------------------------------------
    */

    private function validateCategory(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                'exists:category_products,id',
                function ($attribute, $value, $fail) use ($ignoreId) {
                    if ($ignoreId && (int) $value === (int) $ignoreId) {
                        $fail('La categoría no puede ser padre de sí misma.');
                    }

                    if ($ignoreId && $value) {
                        $parent = CategoryProduct::find($value);

                        while ($parent) {
                            if ((int) $parent->parent_id === (int) $ignoreId) {
                                $fail('No puedes asignar una subcategoría como categoría padre.');
                                break;
                            }

                            $parent = $parent->parent;
                        }
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('category_products', 'slug')->ignore($ignoreId),
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'slug.unique' => 'Este slug ya está en uso.',
            'parent_id.exists' => 'La categoría padre seleccionada no existe.',
            'sort_order.integer' => 'El orden debe ser un número.',
            'sort_order.min' => 'El orden no puede ser negativo.',
        ]);
    }

    private function parentCategories(?int $excludeId = null)
    {
        return CategoryProduct::query()
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->orderBy('full_path')
            ->orderBy('name')
            ->get();
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
            'sort_order' => $item->sort_order,
            'is_active' => (bool) $item->is_active,
            'has_children' => $childrenCount > 0,
            'children_count' => $childrenCount,
        ];

        if ($withMeta) {
            $data['breadcrumb'] = $item->breadcrumb_array;
            $data['display_path'] = $item->display_path;
        }

        return $data;
    }

    private function collectCategoryFamilyIds($categories, int $categoryId, $visibleIds): void
    {
        $category = $categories->firstWhere('id', $categoryId);

        if (!$category) {
            return;
        }

        $visibleIds->push($category->id);

        /*
        |--------------------------------------------------------------------------
        | Ancestros
        |--------------------------------------------------------------------------
        */
        $parentId = $category->parent_id;

        while ($parentId) {
            $parent = $categories->firstWhere('id', $parentId);

            if (!$parent) {
                break;
            }

            $visibleIds->push($parent->id);
            $parentId = $parent->parent_id;
        }

        /*
        |--------------------------------------------------------------------------
        | Descendientes
        |--------------------------------------------------------------------------
        */
        $children = $categories->where('parent_id', $category->id);

        foreach ($children as $child) {
            $visibleIds->push($child->id);
            $this->collectCategoryFamilyIds($categories, (int) $child->id, $visibleIds);
        }
    }
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'category_id' => [
                'required',
                'integer',
                'exists:category_products,id',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:category_products,id',
            ],
            'order' => [
                'required',
                'array',
            ],
            'order.*' => [
                'required',
                'integer',
                'exists:category_products,id',
            ],
        ], [
            'category_id.required' => 'No se recibió la categoría que quieres mover.',
            'category_id.exists' => 'La categoría que intentas mover ya no existe.',
            'parent_id.exists' => 'La categoría padre seleccionada ya no existe.',
            'order.required' => 'No se recibió el nuevo orden.',
            'order.*.exists' => 'Una de las categorías del orden ya no existe.',
        ]);

        $categoryId = (int) $data['category_id'];
        $parentId = $data['parent_id'] ? (int) $data['parent_id'] : null;
        $orderIds = collect($data['order'])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($parentId && $parentId === $categoryId) {
            return response()->json([
                'ok' => false,
                'message' => 'Una categoría no puede ser padre de sí misma.',
            ], 422);
        }

        if ($parentId && $this->isDescendantOf($parentId, $categoryId)) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes mover una categoría dentro de una de sus propias subcategorías.',
            ], 422);
        }

        DB::transaction(function () use ($categoryId, $parentId, $orderIds) {
            $category = CategoryProduct::query()->findOrFail($categoryId);

            $category->parent_id = $parentId;
            $category->save();

            foreach ($orderIds as $index => $id) {
                CategoryProduct::query()
                    ->where('id', $id)
                    ->update([
                        'parent_id' => $parentId,
                        'sort_order' => $index + 1,
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Recalcular rutas completas
            |--------------------------------------------------------------------------
            | Cuando una categoría cambia de padre, su full_path y el de todos sus hijos
            | debe actualizarse para que el árbol y el buscador queden correctos.
            */
            $category->refresh();
            $category->refreshFullPathRecursively();

            foreach ($orderIds as $id) {
                $sibling = CategoryProduct::query()->find($id);

                if ($sibling) {
                    $sibling->refreshFullPathRecursively();
                }
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Jerarquía actualizada correctamente.',
        ]);
    }

    private function isDescendantOf(int $possibleDescendantId, int $ancestorId): bool
    {
        $current = CategoryProduct::query()->find($possibleDescendantId);

        while ($current) {
            if ((int) $current->parent_id === (int) $ancestorId) {
                return true;
            }

            $current = $current->parent_id
                ? CategoryProduct::query()->find($current->parent_id)
                : null;
        }

        return false;
    }
}
