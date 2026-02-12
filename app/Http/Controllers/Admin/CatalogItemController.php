<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogAiIntake;
use App\Models\CatalogItem;
use App\Services\MeliSyncService;
use App\Services\AmazonSpApiListingService;
use App\Services\MeliHttp;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CatalogItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    /* =========================================================
     |  Helpers: Normaliza campos y fuerza guardado
     ========================================================= */

    /**
     * ✅ Acepta category o category_key desde la vista,
     * pero SOLO guardamos category_key (evita error SQL por columna category inexistente).
     *
     * Nota: Esto asume que ya crearás la migración para agregar category_key.
     */
    private function normalizeCategoryFields(array $data): array
    {
        $incoming = $data['category_key'] ?? ($data['category'] ?? null);
        $incoming = is_string($incoming) ? trim($incoming) : $incoming;

        $validKeys = array_keys(config('catalog.product_categories', []));
        if ($incoming !== null && $incoming !== '' && !in_array($incoming, $validKeys, true)) {
            Log::warning('CatalogItem@normalizeCategoryFields: categoría inválida, se limpia', [
                'incoming' => $incoming,
            ]);
            $incoming = null;
        }

        // Guardamos solo category_key (tu DB no tiene column "category")
        $data['category_key'] = $incoming;

        // Evitar que el update intente setear "category"
        unset($data['category']);

        return $data;
    }

    /**
     * ✅ Fuerza a guardar campos sensibles aunque el Model no tenga fillable
     */
    private function forcePersistImportantFields(CatalogItem $item, array $data): void
    {
        $force = [];

        foreach ([
            'brand_name','model_name','meli_gtin',
            'excerpt','description',
            'amazon_sku','amazon_asin','amazon_product_type',
            'category_key',
            'sku','name','slug','price','sale_price','stock','status','published_at','is_featured',
        ] as $k) {
            if (array_key_exists($k, $data)) $force[$k] = $data[$k];
        }

        try {
            $item->forceFill($force)->save();
        } catch (\Throwable $e) {
            Log::warning('CatalogItem@forcePersistImportantFields: no se pudo forceFill (no crítico)', [
                'item_id' => $item->id,
                'err'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * ✅ Si ML responde con id/status/permalink, persiste (si existen columns)
     */
    private function persistMeliResponse(CatalogItem $item, array $res): void
    {
        try {
            $json = $res['json'] ?? null;
            if (!is_array($json)) return;

            $id        = $json['id'] ?? null;
            $status    = $json['status'] ?? null;
            $permalink = $json['permalink'] ?? null;

            $dirty = false;

            if ($id && isset($item->meli_item_id) && $item->meli_item_id !== $id) {
                $item->meli_item_id = $id;
                $dirty = true;
            }

            if ($status && isset($item->meli_status) && $item->meli_status !== $status) {
                $item->meli_status = $status;
                $dirty = true;
            }

            // Solo si en tu tabla existe meli_permalink
            if ($permalink && property_exists($item, 'meli_permalink') && isset($item->meli_permalink) && $item->meli_permalink !== $permalink) {
                $item->meli_permalink = $permalink;
                $dirty = true;
            }

            if ($dirty) $item->save();
        } catch (\Throwable $e) {
            Log::warning('CatalogItem@persistMeliResponse: no se pudo guardar respuesta ML', [
                'item_id' => $item->id,
                'err'     => $e->getMessage(),
            ]);
        }
    }

    /* =========================================================
     |  INDEX
     ========================================================= */
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

    /* =========================
     |  EXPORTS
     ==========================*/

    public function exportExcel(Request $request)
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

        $items = $q->orderBy('id')->get();

        $rows = [];
        $rows[] = ['Inventario interno de Jureto'];
        $rows[] = [''];

        $rows[] = [
            'ID',
            'SKU',
            'Nombre',
            'Precio',
            'Precio oferta',
            'Stock',
            'Estado',
            'Destacado',
            'Slug',
            'Publicado en',
            'ML ID',
        ];

        foreach ($items as $it) {
            $statusText = match ((int) $it->status) {
                1       => 'Publicado',
                2       => 'Oculto',
                default => 'Borrador',
            };

            $featuredText = $it->is_featured ? 'Sí' : 'No';

            $rows[] = [
                $it->id,
                $it->sku,
                $it->name,
                (float) $it->price,
                $it->sale_price !== null ? (float) $it->sale_price : '',
                $it->stock,
                $statusText,
                $featuredText,
                $it->slug,
                $it->published_at ? $it->published_at->format('Y-m-d H:i') : '',
                $it->meli_item_id ?? '',
            ];
        }

        $export = new class($rows) implements FromArray, ShouldAutoSize, WithEvents {
            private array $rows;

            public function __construct(array $rows)
            {
                $this->rows = array_values($rows);
            }

            public function array(): array
            {
                return $this->rows;
            }

            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet->getDelegate();

                        // Header row index (0-based in PHP array): A1 is rows[0]
                        $headerRowExcel = 3; // Row 3 is headings
                        $headerIndex    = 2; // rows[2]

                        $headerColumnCount = count($this->rows[$headerIndex]);
                        $lastColumnLetter  = Coordinate::stringFromColumnIndex($headerColumnCount);

                        $sheet->mergeCells("A1:{$lastColumnLetter}1");
                        $sheet->getStyle("A1")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 16,
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_LEFT,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                            ],
                        ]);

                        $sheet->getStyle("A{$headerRowExcel}:{$lastColumnLetter}{$headerRowExcel}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                            ],
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFE5E7EB'],
                            ],
                            'borders' => [
                                'bottom' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['argb' => 'FF9CA3AF'],
                                ],
                            ],
                        ]);

                        $lastRow = count($this->rows);
                        $sheet->getStyle("A{$headerRowExcel}:{$lastColumnLetter}{$lastRow}")->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_HAIR,
                                    'color'       => ['argb' => 'FFD1D5DB'],
                                ],
                            ],
                        ]);
                    },
                ];
            }
        };

        return Excel::download($export, 'inventario-jureto.xlsx');
    }

    public function exportPdf(Request $request)
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

        $items = $q->orderBy('id')->get();

        $logoBase64 = null;
        $logoPath   = public_path('images/logo-mail.png');
        if (is_file($logoPath)) {
            $logoData   = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,'.base64_encode($logoData);
        }

        $html  = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">';
        $html .= '<style>
          *{ box-sizing:border-box; }
          body{
            font-family: DejaVu Sans, sans-serif;
            font-size:11px;
            color:#111827;
            margin:20px;
          }
          .logo-wrap{ text-align:left; margin-bottom:8px; }
          .logo{ height:40px; }
          .title-main{ font-size:16px; font-weight:800; margin:0 0 2px; }
          .top-sub{ font-size:11px; color:#6b7280; margin:0 0 12px; }
          table{ width:100%; border-collapse:collapse; margin-top:4px; }
          th,td{ padding:6px 5px; border:1px solid #d1d5db; }
          th{ background:#f3f4f6; font-weight:700; font-size:11px; }
          td{ font-size:10px; }
          .muted{ color:#6b7280; }
        </style></head><body>';

        if ($logoBase64) {
            $html .= '<div class="logo-wrap"><img class="logo" src="'.$logoBase64.'" alt="Logo Jureto"></div>';
        }

        $html .= '<h1 class="title-main">Inventario interno de Jureto</h1>';
        $html .= '<p class="top-sub">Listado de productos con filtros actuales</p>';

        $html .= '<table><thead><tr>';
        $html .= '<th>ID</th><th>SKU</th><th>Nombre</th><th>Precio</th><th>Oferta</th><th>Stock</th><th>Estado</th><th>Destacado</th><th>Slug</th><th>Publicado</th><th>ML ID</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($items as $it) {
            $statusText = match ((int) $it->status) {
                1       => 'Publicado',
                2       => 'Oculto',
                default => 'Borrador',
            };
            $featuredText = $it->is_featured ? 'Sí' : 'No';

            $html .= '<tr>';
            $html .= '<td>'.htmlspecialchars((string) $it->id).'</td>';
            $html .= '<td>'.htmlspecialchars((string) ($it->sku ?? '')).'</td>';
            $html .= '<td>'.htmlspecialchars((string) $it->name).'</td>';
            $html .= '<td>$'.number_format((float) $it->price, 2).'</td>';
            $html .= '<td>'.($it->sale_price !== null ? '$'.number_format((float) $it->sale_price, 2) : '—').'</td>';
            $html .= '<td>'.htmlspecialchars((string) ($it->stock ?? 0)).'</td>';
            $html .= '<td>'.htmlspecialchars($statusText).'</td>';
            $html .= '<td>'.htmlspecialchars($featuredText).'</td>';
            $html .= '<td>'.htmlspecialchars((string) $it->slug).'</td>';
            $html .= '<td>'.($it->published_at ? htmlspecialchars($it->published_at->format('Y-m-d H:i')) : '—').'</td>';
            $html .= '<td>'.htmlspecialchars((string) ($it->meli_item_id ?? '')).'</td>';
            $html .= '</tr>';
        }

        if ($items->isEmpty()) {
            $html .= '<tr><td colspan="11" class="muted" style="text-align:center;padding:14px 6px;">';
            $html .= 'No hay productos que coincidan con el filtro.';
            $html .= '</td></tr>';
        }

        $html .= '</tbody></table></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

        return $pdf->download('inventario-jureto.pdf');
    }

    /* =========================
     |  CRUD
     ==========================*/

    public function create()
    {
        $categories = config('catalog.product_categories', []);

        return view('admin.catalog.create', [
            'categories' => $categories,
            'item'       => null,
        ]);
    }

    public function store(Request $request)
    {
        Log::info('CatalogItem@store: inicio', ['input' => $request->all()]);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255'],
            'sku'         => ['nullable', 'string', 'max:120'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0'],
            'stock'       => ['nullable', 'integer', 'min:0'],
            'status'      => ['required', 'integer', 'in:0,1,2'],
            'is_featured' => ['nullable', 'boolean'],

            // ✅ Acepta ambos, pero guardaremos category_key
            'category'     => ['nullable', 'string', 'max:190'],
            'category_key' => ['nullable', 'string', 'max:190'],

            'use_internal'=> ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],

            'brand_name'  => ['nullable', 'string', 'max:120'],
            'model_name'  => ['nullable', 'string', 'max:120'],
            'meli_gtin'   => ['nullable', 'string', 'max:50'],

            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],

            // ✅ AMAZON
            'amazon_sku'          => ['nullable', 'string', 'max:120'],
            'amazon_asin'         => ['nullable', 'string', 'max:40'],
            'amazon_product_type' => ['nullable', 'string', 'max:80'],
        ]);

        $data = $this->normalizeCategoryFields($data);

        // ✅ Slug único
        $baseSlug = isset($data['slug']) && trim($data['slug']) !== ''
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        if ($baseSlug === '') $baseSlug = Str::slug(Str::random(8));

        $slug = $baseSlug;
        $i = 1;
        while (CatalogItem::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.($i++);
        }
        $data['slug'] = $slug;

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['stock']       = $data['stock'] ?? 0;

        if (!$request->boolean('use_internal')) {
            $data['brand_id']    = null;
            $data['category_id'] = null;
        }

        try {
            $item = CatalogItem::create($data);

            $this->forcePersistImportantFields($item, $data);

            Log::info('CatalogItem@store: item creado', [
                'item_id'     => $item->id,
                'slug'        => $item->slug,
                'brand_name'  => $item->brand_name ?? null,
                'model_name'  => $item->model_name ?? null,
                'meli_gtin'   => $item->meli_gtin ?? null,
                'category_key'=> $item->category_key ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('CatalogItem@store: ERROR al crear item', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'data'      => $data,
            ]);

            return back()
                ->withInput()
                ->withErrors(['general' => 'No se pudo guardar el producto. Revisa el log.']);
        }

        $this->saveOrReplacePhoto($request, $item, 'photo_1', 'photo_1_file');
        $this->saveOrReplacePhoto($request, $item, 'photo_2', 'photo_2_file');
        $this->saveOrReplacePhoto($request, $item, 'photo_3', 'photo_3_file');

        $this->ensureThreePhotos($item);

        $this->dispatchMeliSync($item->fresh());

        if ($request->wantsJson()) {
            return response()->json([
                'ok'   => true,
                'item' => $item->fresh(),
                'msg'  => 'Producto web creado.',
            ]);
        }

        return redirect()
            ->route('admin.catalog.create')
            ->with('ok', 'Producto web creado. Puedes seguir capturando más productos.');
    }

    public function edit(CatalogItem $catalogItem)
    {
        $categories = config('catalog.product_categories', []);

        return view('admin.catalog.edit', [
            'item'       => $catalogItem,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        Log::info('CatalogItem@update: inicio', [
            'item_id' => $catalogItem->id,
            'input'   => $request->all(),
        ]);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255'],
            'sku'         => ['nullable', 'string', 'max:120'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0'],
            'stock'       => ['nullable', 'integer', 'min:0'],
            'status'      => ['required', 'integer', 'in:0,1,2'],
            'is_featured' => ['nullable', 'boolean'],

            // ✅ Acepta ambos, pero guardaremos category_key
            'category'     => ['nullable', 'string', 'max:190'],
            'category_key' => ['nullable', 'string', 'max:190'],

            'use_internal'=> ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],

            'brand_name'  => ['nullable', 'string', 'max:120'],
            'model_name'  => ['nullable', 'string', 'max:120'],
            'meli_gtin'   => ['nullable', 'string', 'max:50'],
            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],

            // ✅ AMAZON
            'amazon_sku'          => ['nullable', 'string', 'max:120'],
            'amazon_asin'         => ['nullable', 'string', 'max:40'],
            'amazon_product_type' => ['nullable', 'string', 'max:80'],
        ]);

        $data = $this->normalizeCategoryFields($data);

        // ✅ Slug único (no colisionar con el mismo item)
        $baseSlug = isset($data['slug']) && trim($data['slug']) !== ''
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        if ($baseSlug === '') $baseSlug = Str::slug(Str::random(8));

        $slug = $baseSlug;
        $i = 1;
        while (
            CatalogItem::where('slug', $slug)
                ->where('id', '!=', $catalogItem->id)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.($i++);
        }

        $data['slug']        = $slug;
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['stock']       = $data['stock'] ?? 0;

        if (!$request->boolean('use_internal')) {
            $data['brand_id']    = null;
            $data['category_id'] = null;
        }

        try {
            $catalogItem->update($data);

            $this->forcePersistImportantFields($catalogItem, $data);

            Log::info('CatalogItem@update: item actualizado', [
                'item_id'      => $catalogItem->id,
                'brand_name'   => $catalogItem->brand_name ?? null,
                'model_name'   => $catalogItem->model_name ?? null,
                'meli_gtin'    => $catalogItem->meli_gtin ?? null,
                'category_key' => $catalogItem->category_key ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('CatalogItem@update: ERROR al actualizar item', [
                'item_id'   => $catalogItem->id,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'data'      => $data,
            ]);

            return back()
                ->withInput()
                ->withErrors(['general' => 'No se pudo actualizar el producto. Revisa el log.']);
        }

        $this->saveOrReplacePhoto($request, $catalogItem, 'photo_1', 'photo_1_file');
        $this->saveOrReplacePhoto($request, $catalogItem, 'photo_2', 'photo_2_file');
        $this->saveOrReplacePhoto($request, $catalogItem, 'photo_3', 'photo_3_file');

        $this->ensureThreePhotos($catalogItem);

        $this->dispatchMeliSync($catalogItem->fresh());

        return back()->with('ok', 'Producto web actualizado. Sincronización con Mercado Libre encolada.');
    }

    public function destroy(CatalogItem $catalogItem)
    {
        Log::info('CatalogItem@destroy: inicio', ['item_id' => $catalogItem->id]);

        $this->deletePublicFileIfExists($catalogItem->photo_1);
        $this->deletePublicFileIfExists($catalogItem->photo_2);
        $this->deletePublicFileIfExists($catalogItem->photo_3);

        $catalogItem->delete();
        $this->dispatchMeliSync($catalogItem);

        Log::info('CatalogItem@destroy: item eliminado', ['item_id' => $catalogItem->id]);

        return redirect()
            ->route('admin.catalog.index')
            ->with('ok', 'Producto web eliminado.');
    }

    public function toggleStatus(CatalogItem $catalogItem)
    {
        $catalogItem->status = $catalogItem->status == 1 ? 2 : 1;
        if ($catalogItem->status == 1 && !$catalogItem->published_at) {
            $catalogItem->published_at = now();
        }
        $catalogItem->save();

        $this->dispatchMeliSync($catalogItem);

        Log::info('CatalogItem@toggleStatus: estado cambiado', [
            'item_id' => $catalogItem->id,
            'status'  => $catalogItem->status,
        ]);

        return back()->with('ok', 'Estado actualizado. Sincronización con Mercado Libre encolada.');
    }

    /* =========================
     |  ACCIONES MERCADO LIBRE
     ==========================*/

    /**
     * ✅ Soporta fallback a catálogo con ?catalog=1
     */
    public function meliPublish(Request $request, CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $catalogItem = $catalogItem->fresh();

        $allowCatalog = $request->boolean('catalog');

        Log::info('CatalogItem@meliPublish: inicio', [
            'item_id'        => $catalogItem->id,
            'allow_catalog'  => $allowCatalog,
            'brand_name'     => $catalogItem->brand_name ?? null,
            'model_name'     => $catalogItem->model_name ?? null,
            'gtin'           => $catalogItem->meli_gtin ?? null,
            'sku'            => $catalogItem->sku ?? null,
            'name'           => $catalogItem->name ?? null,
            'category_key'   => $catalogItem->category_key ?? null,
        ]);

        try {
            $res = $svc->sync($catalogItem, [
                'activate'              => true,
                'update_description'    => true,
                'ensure_picture'        => true,
                'allow_catalog_fallback'=> $allowCatalog,
            ]);
        } catch (\Throwable $e) {
            Log::error('CatalogItem@meliPublish: exception', [
                'item_id' => $catalogItem->id,
                'err'     => $e->getMessage(),
            ]);
            return back()->withErrors(['general' => 'Error publicando en Mercado Libre: '.$e->getMessage()]);
        }

        if (!empty($res['ok'])) {
            $this->persistMeliResponse($catalogItem, $res);

            $mlId = $res['json']['id'] ?? $catalogItem->meli_item_id ?? '—';
            $mlSt = $res['json']['status'] ?? $catalogItem->meli_status ?? '—';

            $msg = $allowCatalog
                ? "Publicado/actualizado en Mercado Libre (catálogo si fue necesario). ID: {$mlId} · Estado: {$mlSt}"
                : "Publicado/actualizado en Mercado Libre. ID: {$mlId} · Estado: {$mlSt}";

            return back()->with('ok', $msg);
        }

        $friendly = $res['message'] ?? 'No se pudo publicar en Mercado Libre. Revisa el producto.';
        Log::warning('CatalogItem@meliPublish: failed', [
            'item_id'  => $catalogItem->id,
            'message'  => $friendly,
            'res'      => $res,
        ]);

        return back()->withErrors(['general' => $friendly]);
    }

    public function meliPause(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $catalogItem = $catalogItem->fresh();

        try {
            $res = $svc->pause($catalogItem);
        } catch (\Throwable $e) {
            Log::error('CatalogItem@meliPause: exception', [
                'item_id' => $catalogItem->id,
                'err'     => $e->getMessage(),
            ]);
            return back()->withErrors(['general' => 'Error pausando en Mercado Libre: '.$e->getMessage()]);
        }

        if (!empty($res['ok'])) {
            $this->persistMeliResponse($catalogItem, $res);
            return back()->with('ok', 'Publicación pausada en Mercado Libre.');
        }

        $friendly = $res['message'] ?? 'No se pudo pausar en Mercado Libre.';
        Log::warning('CatalogItem@meliPause: failed', ['item_id' => $catalogItem->id, 'res' => $res]);
        return back()->withErrors(['general' => $friendly]);
    }

    public function meliActivate(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        $catalogItem = $catalogItem->fresh();

        try {
            $res = $svc->activate($catalogItem);
        } catch (\Throwable $e) {
            Log::error('CatalogItem@meliActivate: exception', [
                'item_id' => $catalogItem->id,
                'err'     => $e->getMessage(),
            ]);
            return back()->withErrors(['general' => 'Error activando en Mercado Libre: '.$e->getMessage()]);
        }

        if (!empty($res['ok'])) {
            $this->persistMeliResponse($catalogItem, $res);
            return back()->with('ok', 'Publicación activada en Mercado Libre.');
        }

        $friendly = $res['message'] ?? 'No se pudo activar en Mercado Libre.';
        Log::warning('CatalogItem@meliActivate: failed', ['item_id' => $catalogItem->id, 'res' => $res]);
        return back()->withErrors(['general' => $friendly]);
    }

    public function meliView(CatalogItem $catalogItem)
    {
        if (!$catalogItem->meli_item_id) {
            return back()->withErrors(['general' => 'Este producto aún no tiene publicación en ML.']);
        }

        $http = MeliHttp::withFreshToken();
        $resp = $http->get("https://api.mercadolibre.com/items/{$catalogItem->meli_item_id}");
        if ($resp->failed()) {
            return back()->withErrors(['general' => 'No se pudo obtener el permalink desde ML.']);
        }

        $permalink = $resp->json('permalink');
        return $permalink
            ? redirect()->away($permalink)
            : back()->withErrors(['general' => 'Este ítem no tiene permalink disponible.']);
    }

    /* =========================
     |  IA: Captura desde QR
     ==========================*/

    public function aiStart(Request $r)
    {
        $intake = CatalogAiIntake::create([
            'token'       => Str::random(40),
            'created_by'  => $r->user()->id,
            'status'      => 0,
            'source_type' => $r->get('source_type','factura'),
            'notes'       => $r->get('notes'),
        ]);

        return response()->json([
            'ok'         => true,
            'intake_id'  => $intake->id,
            'token'      => $intake->token,
            'mobile_url' => route('intake.mobile', $intake->token),
        ]);
    }

    public function aiStatus(CatalogAiIntake $intake)
    {
        return response()->json([
            'status'    => $intake->status,
            'extracted' => $intake->extracted,
            'meta'      => $intake->meta,
        ]);
    }

    public function aiFromUpload(Request $request)
    {
        // ✅ AQUÍ PEGA TU MÉTODO aiFromUpload COMPLETO TAL CUAL LO TENÍAS (SIN CAMBIOS).
        return response()->json(['error' => 'Pega aquí tu método aiFromUpload completo (sin cambios).'], 500);
    }

    /** Dispara el sync con ML sin romper la UI si algo truena */
    private function dispatchMeliSync(CatalogItem $item): void
    {
        try {
            app(MeliSyncService::class)->sync($item, [
                'activate'           => false,
                'update_description' => false,
                'ensure_picture'     => false,
                'allow_catalog_fallback' => false,
            ]);
        } catch (\Throwable $e) {
            Log::warning('CatalogItem@dispatchMeliSync: error no crítico', [
                'item_id'   => $item->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /* =========================
     |  FOTOS
     ==========================*/

    private function saveOrReplacePhoto(Request $request, CatalogItem $item, string $column, string $input): void
    {
        /** @var UploadedFile|null $file */
        $file = $request->file($input);

        if (!$file instanceof UploadedFile) return;

        if (!$file->isValid()) {
            Log::warning('CatalogItem@saveOrReplacePhoto: archivo no válido', [
                'item_id' => $item->id,
                'input'   => $input,
            ]);

            throw ValidationException::withMessages([
                $input => 'Hubo un problema al subir esta foto. Intenta de nuevo.',
            ]);
        }

        $old = $item->{$column};
        if ($old) $this->deletePublicFileIfExists($old);

        $path = $file->store('catalog/photos', 'public');
        $item->{$column} = $path;
        $item->save();

        Log::info('CatalogItem@saveOrReplacePhoto: foto guardada', [
            'item_id' => $item->id,
            'column'  => $column,
            'path'    => $path,
        ]);
    }

    private function ensureThreePhotos(CatalogItem $item): void
    {
        if (empty($item->photo_1) || empty($item->photo_2) || empty($item->photo_3)) {
            Log::warning('CatalogItem@ensureThreePhotos: faltan fotos', [
                'item_id' => $item->id,
                'photo_1' => $item->photo_1,
                'photo_2' => $item->photo_2,
                'photo_3' => $item->photo_3,
            ]);

            throw ValidationException::withMessages([
                'photo_1_file' => 'Debes subir 3 fotos del producto (Foto 1, Foto 2 y Foto 3).',
            ]);
        }
    }

    private function deletePublicFileIfExists(?string $path): void
    {
        if (!$path) return;

        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            Log::warning('CatalogItem@deletePublicFileIfExists: error al borrar archivo', [
                'path'      => $path,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function updateStock(Request $request, CatalogItem $catalogItem)
    {
        $data = $request->validate([
            'stock' => ['required', 'numeric', 'min:0'],
        ]);

        $catalogItem->stock = $data['stock'];
        $catalogItem->save();

        Log::info('CatalogItem@updateStock: stock actualizado', [
            'item_id' => $catalogItem->id,
            'stock'   => $catalogItem->stock,
        ]);

        return back()->with('ok', 'Stock actualizado correctamente.');
    }

    /* =========================================================
     |  AMAZON - (lo dejas como ya lo tenías)
     ========================================================= */

    private function amazonSkuStrict(CatalogItem $item): ?string
    {
        $sku = isset($item->amazon_sku) && is_string($item->amazon_sku) ? trim($item->amazon_sku) : '';
        return $sku !== '' ? $sku : null;
    }

    private function amazonMarketplaceId(): string
    {
        $mp = config('services.amazon_spapi.marketplace_id') ?: env('SPAPI_MARKETPLACE_ID');
        $mp = is_string($mp) ? trim($mp) : '';
        return $mp !== '' ? $mp : 'A1AM78C64UM0Y8';
    }

    private function amazonPersist(CatalogItem $item, array $res, string $action): void
    {
        try {
            $status = $res['status'] ?? null;
            $ok     = (bool)($res['ok'] ?? false);

            if (isset($item->amazon_synced_at)) $item->amazon_synced_at = now();
            if (isset($item->amazon_status))    $item->amazon_status    = ($ok ? 'ok_' : 'error_') . (string)($status ?? 'unknown');

            if (isset($item->amazon_last_error)) {
                if ($ok) {
                    $item->amazon_last_error = null;
                } else {
                    $msg = $res['message'] ?? 'Amazon error';
                    $amazonErr = null;
                    if (!empty($res['json']) && is_array($res['json'])) {
                        $amazonErr = data_get($res['json'], 'errors.0.message');
                    }
                    $item->amazon_last_error = $amazonErr ? ($msg . ' · ' . $amazonErr) : $msg;
                }
            }

            if (isset($item->amazon_listing_response)) {
                $item->amazon_listing_response = json_encode([
                    'action'  => $action,
                    'ok'      => $res['ok'] ?? null,
                    'status'  => $res['status'] ?? null,
                    'message' => $res['message'] ?? null,
                    'json'    => $res['json'] ?? null,
                    'body'    => $res['body'] ?? null,
                ], JSON_UNESCAPED_UNICODE);
            }

            $asin = null;
            if (!empty($res['json']) && is_array($res['json'])) {
                $asin = data_get($res['json'], 'asin')
                    ?: data_get($res['json'], 'summaries.0.asin')
                    ?: data_get($res['json'], 'payload.asin');
            }
            if ($asin && isset($item->amazon_asin)) {
                $item->amazon_asin = $asin;
            }

            $item->save();
        } catch (\Throwable $e) {
            Log::warning('CatalogItem@amazonPersist: no se pudo guardar tracking', [
                'item_id' => $item->id ?? null,
                'err'     => $e->getMessage(),
            ]);
        }
    }

    public function amazonPublish(CatalogItem $catalogItem, AmazonSpApiListingService $svc)
    {
        $amazonSku = $this->amazonSkuStrict($catalogItem);
        if (!$amazonSku) {
            if (isset($catalogItem->amazon_synced_at)) $catalogItem->amazon_synced_at = now();
            if (isset($catalogItem->amazon_status))    $catalogItem->amazon_status = 'missing_amazon_sku';
            if (isset($catalogItem->amazon_last_error))$catalogItem->amazon_last_error = 'Falta amazon_sku (Seller SKU real de Amazon).';
            $catalogItem->save();

            return back()->withErrors(['general' => 'Falta AMAZON SKU (Seller SKU real). Captúralo y vuelve a intentar.']);
        }

        try {
            try {
                $res = $svc->upsertBySku($catalogItem, [
                    'marketplace_id' => $this->amazonMarketplaceId(),
                ]);
            } catch (\ArgumentCountError $e) {
                $res = $svc->upsertBySku($catalogItem);
            }
        } catch (\Throwable $e) {
            Log::error('CatalogItem@amazonPublish: error en upsertBySku', [
                'item_id' => $catalogItem->id,
                'err'     => $e->getMessage(),
            ]);

            if (isset($catalogItem->amazon_synced_at)) $catalogItem->amazon_synced_at = now();
            if (isset($catalogItem->amazon_status))    $catalogItem->amazon_status = 'error_exception';
            if (isset($catalogItem->amazon_last_error))$catalogItem->amazon_last_error = $e->getMessage();
            $catalogItem->save();

            return back()->withErrors(['general' => 'Error publicando en Amazon: '.$e->getMessage()]);
        }

        $this->amazonPersist($catalogItem->fresh(), is_array($res) ? $res : [
            'ok' => false, 'status' => null, 'message' => 'Respuesta inválida del service', 'json' => null, 'body' => null,
        ], 'publish');

        if (!empty($res['ok'])) {
            return back()->with('ok', 'Solicitud enviada a Amazon (submitted). Puede tardar en reflejarse.');
        }

        if (($res['status'] ?? null) === 404) {
            return back()->withErrors(['general' => 'Amazon respondió 404: SKU no encontrado. Verifica amazon_sku EXACTO en Seller Central (MX).']);
        }

        return back()->withErrors(['general' => $res['message'] ?? 'No se pudo publicar/actualizar en Amazon.']);
    }

    public function amazonView(CatalogItem $catalogItem, AmazonSpApiListingService $svc)
    {
        $amazonSku = $this->amazonSkuStrict($catalogItem);
        if (!$amazonSku) {
            if (isset($catalogItem->amazon_synced_at)) $catalogItem->amazon_synced_at = now();
            if (isset($catalogItem->amazon_status))    $catalogItem->amazon_status = 'missing_amazon_sku';
            if (isset($catalogItem->amazon_last_error))$catalogItem->amazon_last_error = 'Falta amazon_sku (Seller SKU real de Amazon).';
            $catalogItem->save();

            return back()->withErrors(['general' => 'Falta AMAZON SKU (Seller SKU real).']);
        }

        try {
            try {
                $res = $svc->getBySku($catalogItem, [
                    'marketplace_id' => $this->amazonMarketplaceId(),
                ]);
            } catch (\ArgumentCountError $e) {
                $res = $svc->getBySku($catalogItem);
            }
        } catch (\Throwable $e) {
            Log::error('CatalogItem@amazonView: error en getBySku', [
                'item_id' => $catalogItem->id,
                'err'     => $e->getMessage(),
            ]);

            if (isset($catalogItem->amazon_synced_at)) $catalogItem->amazon_synced_at = now();
            if (isset($catalogItem->amazon_status))    $catalogItem->amazon_status = 'error_exception';
            if (isset($catalogItem->amazon_last_error))$catalogItem->amazon_last_error = $e->getMessage();
            $catalogItem->save();

            return back()->withErrors(['general' => 'Error consultando listing: '.$e->getMessage()]);
        }

        $this->amazonPersist($catalogItem->fresh(), is_array($res) ? $res : [
            'ok' => false, 'status' => null, 'message' => 'Respuesta inválida del service', 'json' => null, 'body' => null,
        ], 'view');

        if (empty($res['ok'])) {
            if (($res['status'] ?? null) === 404) {
                return back()->withErrors(['general' => 'Amazon dice 404: ese amazon_sku no existe (o aún está procesando).']);
            }
            return back()->withErrors(['general' => $res['message'] ?? 'Error consultando listing']);
        }

        $asin = is_string($catalogItem->amazon_asin ?? null) ? trim((string)$catalogItem->amazon_asin) : '';
        if ($asin !== '') {
            $asin = urlencode($asin);
            return redirect()->away("https://www.amazon.com.mx/dp/{$asin}");
        }

        $q = urlencode($amazonSku);
        return redirect()->away("https://www.amazon.com.mx/s?k={$q}");
    }

    public function amazonPause(CatalogItem $catalogItem, AmazonSpApiListingService $svc)
    {
        $amazonSku = $this->amazonSkuStrict($catalogItem);
        if (!$amazonSku) {
            return back()->withErrors(['general' => 'Falta AMAZON SKU (Seller SKU real).']);
        }

        try {
            try {
                $res = $svc->upsertBySku($catalogItem, [
                    'marketplace_id' => $this->amazonMarketplaceId(),
                    'status' => 'inactive',
                ]);
            } catch (\ArgumentCountError $e) {
                $res = $svc->upsertBySku($catalogItem);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['general' => 'Error pausando en Amazon: '.$e->getMessage()]);
        }

        $this->amazonPersist($catalogItem->fresh(), is_array($res) ? $res : [
            'ok' => false, 'status' => null, 'message' => 'Respuesta inválida del service', 'json' => null, 'body' => null,
        ], 'pause');

        return back()->with('ok', !empty($res['ok']) ? 'Solicitud enviada a Amazon para pausar.' : ($res['message'] ?? 'No se pudo pausar en Amazon.'));
    }

    public function amazonActivate(CatalogItem $catalogItem, AmazonSpApiListingService $svc)
    {
        $amazonSku = $this->amazonSkuStrict($catalogItem);
        if (!$amazonSku) {
            return back()->withErrors(['general' => 'Falta AMAZON SKU (Seller SKU real).']);
        }

        try {
            try {
                $res = $svc->upsertBySku($catalogItem, [
                    'marketplace_id' => $this->amazonMarketplaceId(),
                    'status' => 'active',
                ]);
            } catch (\ArgumentCountError $e) {
                $res = $svc->upsertBySku($catalogItem);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['general' => 'Error activando en Amazon: '.$e->getMessage()]);
        }

        $this->amazonPersist($catalogItem->fresh(), is_array($res) ? $res : [
            'ok' => false, 'status' => null, 'message' => 'Respuesta inválida del service', 'json' => null, 'body' => null,
        ], 'activate');

        return back()->with('ok', !empty($res['ok']) ? 'Solicitud enviada a Amazon para activar/actualizar.' : ($res['message'] ?? 'No se pudo activar en Amazon.'));
    }
}
