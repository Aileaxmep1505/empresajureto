<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

// NEW: servicios para Mercado Libre
use App\Services\MeliSyncService;
use App\Services\MeliHttp;

class CatalogItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    public function index(Request $request)
    {
        $q = CatalogItem::query();

        $s = trim((string)$request->get('s', ''));
        if ($s !== '') {
            $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                   ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $q->where('status', (int)$request->integer('status'));
        }

        if ($request->boolean('featured_only')) {
            $q->where('is_featured', true);
        }

        $items = $q->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.catalog.index', [
            'items' => $items,
            'filters' => [
                's' => $s,
                'status' => $request->get('status'),
                'featured_only' => $request->boolean('featured_only'),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.catalog.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:255'],
            'slug'        => ['nullable','string','max:255','unique:catalog_items,slug'],
            'sku'         => ['nullable','string','max:120'],
            'price'       => ['required','numeric','min:0'],
            'sale_price'  => ['nullable','numeric','min:0'],
            'status'      => ['required','integer','in:0,1,2'], // 0=borrador 1=publicado 2=oculto
            'image_url'   => ['nullable','string','max:2048'],
            'images'      => ['nullable','array'],
            'images.*'    => ['nullable','url'],
            'is_featured' => ['nullable','boolean'],
            'brand_id'    => ['nullable','integer'],
            'category_id' => ['nullable','integer'],
            'brand_name'  => ['nullable','string','max:120'], // usados por ML
            'model_name'  => ['nullable','string','max:120'],
            'excerpt'     => ['nullable','string'],
            'description' => ['nullable','string'],
            'published_at'=> ['nullable','date'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        if (CatalogItem::where('slug', $data['slug'])->exists()) {
            $data['slug'] = Str::slug($data['name'].'-'.Str::random(4));
        }
        $data['is_featured'] = (bool)($data['is_featured'] ?? false);

        $item = CatalogItem::create($data);

        // Encolar sync a ML
        $this->dispatchMeliSync($item);

        return redirect()
            ->route('admin.catalog.edit', $item->id)
            ->with('ok', 'Producto web creado. Sincronización con Mercado Libre encolada.');
    }

    public function edit(CatalogItem $catalogItem)
    {
        return view('admin.catalog.edit', ['item' => $catalogItem]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:255'],
            'slug'        => ['nullable','string','max:255','unique:catalog_items,slug,'.$catalogItem->id],
            'sku'         => ['nullable','string','max:120'],
            'price'       => ['required','numeric','min:0'],
            'sale_price'  => ['nullable','numeric','min:0'],
            'status'      => ['required','integer','in:0,1,2'],
            'image_url'   => ['nullable','string','max:2048'],
            'images'      => ['nullable','array'],
            'images.*'    => ['nullable','url'],
            'is_featured' => ['nullable','boolean'],
            'brand_id'    => ['nullable','integer'],
            'category_id' => ['nullable','integer'],
            'brand_name'  => ['nullable','string','max:120'],
            'model_name'  => ['nullable','string','max:120'],
            'excerpt'     => ['nullable','string'],
            'description' => ['nullable','string'],
            'published_at'=> ['nullable','date'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_featured'] = (bool)($data['is_featured'] ?? false);

        $catalogItem->update($data);

        // Encolar sync a ML
        $this->dispatchMeliSync($catalogItem);

        return back()->with('ok', 'Producto web actualizado. Sincronización con Mercado Libre encolada.');
    }

    public function destroy(CatalogItem $catalogItem)
    {
        $catalogItem->delete();
        // Si quieres pausar/eliminar en ML puedes decidirlo en el Job
        $this->dispatchMeliSync($catalogItem);

        return redirect()->route('admin.catalog.index')->with('ok', 'Producto web eliminado.');
    }

    /** Publicar/Ocultar rápido */
    public function toggleStatus(CatalogItem $catalogItem)
    {
        $catalogItem->status = $catalogItem->status == 1 ? 2 : 1;
        if ($catalogItem->status == 1 && !$catalogItem->published_at) {
            $catalogItem->published_at = now();
        }
        $catalogItem->save();

        $this->dispatchMeliSync($catalogItem);

        return back()->with('ok', 'Estado actualizado. Sincronización con Mercado Libre encolada.');
    }

    /* =========================
     |  ACCIONES MERCADO LIBRE
     ==========================*/

    // POST admin/catalog/{catalogItem}/meli/publish
    public function meliPublish(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $res = $svc->sync($catalogItem);
        return back()->with('ok', $res['ok']
            ? 'Publicado/actualizado en Mercado Libre.'
            : ('No se pudo publicar en ML: '.substr(json_encode($res['json'],JSON_UNESCAPED_UNICODE),0,300)));
    }

    // POST admin/catalog/{catalogItem}/meli/pause
    public function meliPause(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $res = $svc->pause($catalogItem);
        return back()->with('ok', $res['ok']
            ? 'Publicación pausada en Mercado Libre.'
            : ('No se pudo pausar en ML: '.substr(json_encode($res['json'],JSON_UNESCAPED_UNICODE),0,300)));
    }

    // POST admin/catalog/{catalogItem}/meli/activate
    public function meliActivate(CatalogItem $catalogItem)
    {
        if (!$catalogItem->meli_item_id) {
            return back()->with('ok', 'Este producto aún no tiene meli_item_id. Primero publícalo.');
        }

        $http = MeliHttp::withFreshToken();
        $resp = $http->put("https://api.mercadolibre.com/items/{$catalogItem->meli_item_id}", ['status' => 'active']);
        if ($resp->failed()) {
            $catalogItem->update([
                'meli_status'     => 'error',
                'meli_last_error' => substr($resp->body(),0,2000),
            ]);
            return back()->with('ok', 'No se pudo activar en ML: '.substr($resp->body(),0,300));
        }

        $catalogItem->update([
            'meli_status' => 'active',
            'meli_last_error' => null,
            'meli_synced_at' => now(),
        ]);

        return back()->with('ok', 'Publicación activada en Mercado Libre.');
    }

    // GET admin/catalog/{catalogItem}/meli/view
    public function meliView(CatalogItem $catalogItem)
    {
        if (!$catalogItem->meli_item_id) {
            return back()->with('ok', 'Este producto aún no tiene publicación en ML.');
        }

        $http = MeliHttp::withFreshToken();
        $resp = $http->get("https://api.mercadolibre.com/items/{$catalogItem->meli_item_id}");
        if ($resp->failed()) {
            return back()->with('ok', 'No se pudo obtener el permalink desde ML.');
        }

        $permalink = $resp->json('permalink');
        return $permalink ? redirect()->away($permalink)
                          : back()->with('ok','Este ítem no tiene permalink disponible.');
    }

    /** Encola el Job que publica/actualiza/pausa en Mercado Libre */
    private function dispatchMeliSync(CatalogItem $item): void
    {
        // Si ya creaste el Job PublishCatalogItemToMeli, usa ese.
        // Si aún no, puedes llamar al servicio directamente aquí.
        try {
            // \App\Jobs\PublishCatalogItemToMeli::dispatch($item->id);
            app(MeliSyncService::class)->sync($item); // fallback inmediato
        } catch (\Throwable $e) {
            // no rompas el flujo de la UI
        }
    }
}
