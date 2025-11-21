<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\CatalogAiIntake;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use App\Services\MeliSyncService;

class CatalogItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    public function index(Request $request)
    {
        $q = CatalogItem::query();

        $s = trim((string) $request->get('s', ''));
        if ($s !== '') {
            $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $q->where('status', (int) $request->integer('status'));
        }

        if ($request->boolean('featured_only')) {
            $q->where('is_featured', true);
        }

        $items = $q->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.catalog.index', [
            'items'   => $items,
            'filters' => [
                's'             => $s,
                'status'        => $request->get('status'),
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
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:catalog_items,slug'],
            'sku'         => ['nullable', 'string', 'max:120'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0'],
            'status'      => ['required', 'integer', 'in:0,1,2'], // 0=borrador 1=publicado 2=oculto
            'image_url'   => ['nullable', 'string', 'max:2048'],
            'images'      => ['nullable', 'array'],
            'images.*'    => ['nullable', 'url'],
            'is_featured' => ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'brand_name'  => ['nullable', 'string', 'max:120'], // usados por ML
            'model_name'  => ['nullable', 'string', 'max:120'],
            'meli_gtin'   => ['nullable', 'string', 'max:50'],  // GTIN / código de barras para ML
            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        if (CatalogItem::where('slug', $data['slug'])->exists()) {
            $data['slug'] = Str::slug($data['name'] . '-' . Str::random(4));
        }

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);

        $item = CatalogItem::create($data);

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
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:catalog_items,slug,' . $catalogItem->id],
            'sku'         => ['nullable', 'string', 'max:120'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0'],
            'status'      => ['required', 'integer', 'in:0,1,2'],
            'image_url'   => ['nullable', 'string', 'max:2048'],
            'images'      => ['nullable', 'array'],
            'images.*'    => ['nullable', 'url'],
            'is_featured' => ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'brand_name'  => ['nullable', 'string', 'max:120'],
            'model_name'  => ['nullable', 'string', 'max:120'],
            'meli_gtin'   => ['nullable', 'string', 'max:50'], // GTIN / código de barras para ML
            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],
        ]);

        $data['slug']        = $data['slug'] ?: Str::slug($data['name']);
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);

        $catalogItem->update($data);

        $this->dispatchMeliSync($catalogItem);

        return back()->with('ok', 'Producto web actualizado. Sincronización con Mercado Libre encolada.');
    }

    public function destroy(CatalogItem $catalogItem)
    {
        $catalogItem->delete();
        $this->dispatchMeliSync($catalogItem);

        return redirect()
            ->route('admin.catalog.index')
            ->with('ok', 'Producto web eliminado.');
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

    public function meliPublish(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $res = $svc->sync($catalogItem, [
            'activate'           => true,
            'update_description' => true,
            'ensure_picture'     => true,
        ]);

        if ($res['ok']) {
            $msg = 'Publicado/actualizado en Mercado Libre.';

            if ($catalogItem->meli_item_id || !empty($res['json']['id'] ?? null)) {
                $mlId = $res['json']['id'] ?? $catalogItem->meli_item_id;
                $mlSt = $res['json']['status'] ?? $catalogItem->meli_status ?? '—';
                $msg .= " Mercado Libre: ID: {$mlId} · Estado: {$mlSt}";
            }

            return back()->with('ok', $msg);
        }

        $friendly = $res['message'] ?? 'No se pudo publicar en Mercado Libre. Revisa los datos del producto.';
        return back()->with('ok', $friendly);
    }

    public function meliPause(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $res = $svc->pause($catalogItem);

        if ($res['ok']) {
            return back()->with('ok', 'Publicación pausada en Mercado Libre.');
        }

        $friendly = $res['message'] ?? 'No se pudo pausar la publicación en Mercado Libre.';
        return back()->with('ok', $friendly);
    }

    public function meliActivate(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $res = $svc->activate($catalogItem);

        if ($res['ok']) {
            return back()->with('ok', 'Publicación activada en Mercado Libre.');
        }

        $friendly = $res['message'] ?? 'No se pudo activar la publicación en Mercado Libre.';
        return back()->with('ok', $friendly);
    }

    public function meliView(CatalogItem $catalogItem)
    {
        if (!$catalogItem->meli_item_id) {
            return back()->with('ok', 'Este producto aún no tiene publicación en ML.');
        }

        $http = \App\Services\MeliHttp::withFreshToken();
        $resp = $http->get("https://api.mercadolibre.com/items/{$catalogItem->meli_item_id}");
        if ($resp->failed()) {
            return back()->with('ok', 'No se pudo obtener el permalink desde ML.');
        }

        $permalink = $resp->json('permalink');
        return $permalink
            ? redirect()->away($permalink)
            : back()->with('ok', 'Este ítem no tiene permalink disponible.');
    }

    /* =========================
     |  IA: Captura desde QR
     ==========================*/

    // POST /admin/catalog/ai/start
    public function aiStart(Request $r)
    {
        $intake = CatalogAiIntake::create([
            'token'      => Str::random(40),
            'created_by' => $r->user()->id,
            'status'     => 0,
            'source_type'=> $r->get('source_type','factura'),
            'notes'      => $r->get('notes'),
        ]);

        return response()->json([
            'ok'         => true,
            'intake_id'  => $intake->id,
            'token'      => $intake->token,
            'mobile_url' => route('intake.mobile', $intake->token),
        ]);
    }

    // GET /admin/catalog/ai/{intake}/status
    public function aiStatus(CatalogAiIntake $intake)
    {
        return response()->json([
            'status'    => $intake->status,
            'extracted' => $intake->extracted,
            'meta'      => $intake->meta,
        ]);
    }

    /** Dispara el sync con ML sin romper la UI si algo truena */
    private function dispatchMeliSync(CatalogItem $item): void
    {
        try {
            app(MeliSyncService::class)->sync($item, [
                'activate'           => false,
                'update_description' => false,
                'ensure_picture'     => false,
            ]);
        } catch (\Throwable $e) {
            // No romper flujo de interfaz
        }
    }
}
