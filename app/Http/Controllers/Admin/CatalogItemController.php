<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogAiIntake;
use App\Models\CatalogItem;
use App\Services\MeliSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Http;
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
use App\Services\AmazonSpApiListingService;


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

    /* =========================
     |  EXPORTS
     ==========================*/

    /**
     * Exportar a Excel (.xlsx) con formato:
     * - Fila 1: "Inventario interno de Jureto" (título grande)
     * - Fila 3: encabezados en negritas, centrados, fondo gris
     */
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

        // Fila 1: título
        $rows[] = ['Inventario interno de Jureto'];

        // Fila 2: vacía
        $rows[] = [''];

        // Fila 3: encabezados
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

                        // Fila de encabezados (fila 3 => índice 2 en $this->rows)
                        $headerIndex       = 2;
                        $headerColumnCount = count($this->rows[$headerIndex]);
                        $lastColumnLetter  = Coordinate::stringFromColumnIndex($headerColumnCount);

                        // 🔹 Título (A1:última_columna1)
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

                        // 🔹 Encabezados (fila 3)
                        $sheet->getStyle("A3:{$lastColumnLetter}3")->applyFromArray([
                            'font' => [
                                'bold' => true,
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                            ],
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFE5E7EB'], // gris claro
                            ],
                            'borders' => [
                                'bottom' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['argb' => 'FF9CA3AF'],
                                ],
                            ],
                        ]);

                        // 🔹 Bordes suaves para todo el rango de datos (desde encabezados hacia abajo)
                        $lastRow = count($this->rows);
                        $sheet->getStyle("A3:{$lastColumnLetter}{$lastRow}")->applyFromArray([
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

    /**
     * Exportar a PDF:
     * - Logo arriba (public/images/logo-mail.png)
     * - Título debajo del logo
     * - Tabla con todos los datos
     */
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

        // Logo como base64 para que DomPDF lo agarre sin broncas
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
          .logo-wrap{
            text-align:left;
            margin-bottom:8px;
          }
          .logo{
            height:40px;
          }
          .title-main{
            font-size:16px;
            font-weight:800;
            margin:0 0 2px;
          }
          .top-sub{
            font-size:11px;
            color:#6b7280;
            margin:0 0 12px;
          }
          table{
            width:100%;
            border-collapse:collapse;
            margin-top:4px;
          }
          th,td{
            padding:6px 5px;
            border:1px solid #d1d5db;
          }
          th{
            background:#f3f4f6;
            font-weight:700;
            font-size:11px;
          }
          td{
            font-size:10px;
          }
          .muted{
            color:#6b7280;
          }
        </style></head><body>';

        // 🔹 Logo arriba de todo
        if ($logoBase64) {
            $html .= '<div class="logo-wrap"><img class="logo" src="'.$logoBase64.'" alt="Logo Jureto"></div>';
        }

        // 🔹 Título y subtítulo
        $html .= '<h1 class="title-main">Inventario interno de Jureto</h1>';
        $html .= '<p class="top-sub">Listado de productos con filtros actuales</p>';

        // Tabla
        $html .= '<table><thead><tr>';
        $html .= '<th>ID</th>';
        $html .= '<th>SKU</th>';
        $html .= '<th>Nombre</th>';
        $html .= '<th>Precio</th>';
        $html .= '<th>Oferta</th>';
        $html .= '<th>Stock</th>';
        $html .= '<th>Estado</th>';
        $html .= '<th>Destacado</th>';
        $html .= '<th>Slug</th>';
        $html .= '<th>Publicado</th>';
        $html .= '<th>ML ID</th>';
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
        Log::info('CatalogItem@store: inicio', [
            'input' => $request->all(),
        ]);

        // Validación SOLO de campos de texto/numéricos (SIN fotos aquí)
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255'],
            'sku'         => ['nullable', 'string', 'max:120'],
            'price'       => ['required', 'numeric', 'min:0'],
            'sale_price'  => ['nullable', 'numeric', 'min:0'],
            'stock'       => ['nullable', 'integer', 'min:0'],
            'status'      => ['required', 'integer', 'in:0,1,2'], // 0=borrador 1=publicado 2=oculto
            'is_featured' => ['nullable', 'boolean'],

            // Categoría string (clave de config/catalog.php)
            'category_key'=> ['nullable', 'string', 'max:190'],

            // Clasificación interna
            'use_internal'=> ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],

            // Mercado Libre texto
            'brand_name'  => ['nullable', 'string', 'max:120'],
            'model_name'  => ['nullable', 'string', 'max:120'],
            'meli_gtin'   => ['nullable', 'string', 'max:50'],

            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],
        ]);

        Log::info('CatalogItem@store: datos validados', [
            'data' => $data,
        ]);

        // Validar que la category_key exista (si viene)
        if (!empty($data['category_key'])) {
            $validKeys = array_keys(config('catalog.product_categories', []));
            if (!in_array($data['category_key'], $validKeys, true)) {
                Log::warning('CatalogItem@store: category_key no es válida, se limpia', [
                    'category_key' => $data['category_key'],
                ]);
                $data['category_key'] = null;
            }
        }

        // Slug base: slug enviado o nombre
        $baseSlug = isset($data['slug']) && trim($data['slug']) !== ''
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        if ($baseSlug === '') {
            $baseSlug = Str::slug(Str::random(8));
        }

        // Slug único (slug, slug-1, slug-2, ...)
        $slug = $baseSlug;
        $i = 1;
        while (CatalogItem::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.($i++);
        }
        $data['slug'] = $slug;

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['stock']       = $data['stock'] ?? 0;

        // Si no usan clasificación interna, limpiar ids
        if (!$request->boolean('use_internal')) {
            $data['brand_id']    = null;
            $data['category_id'] = null;
        }

        try {
            $item = CatalogItem::create($data);

            Log::info('CatalogItem@store: item creado en BD', [
                'item_id'      => $item->id,
                'slug'         => $item->slug,
                'category_key' => $item->category_key ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('CatalogItem@store: ERROR al crear item', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'data'      => $data,
            ]);

            return back()
                ->withInput()
                ->withErrors(['general' => 'No se pudo guardar el producto en la base de datos. Revisa el log para más detalles.']);
        }

        // Guardar 3 fotos
        $this->saveOrReplacePhoto($request, $item, 'photo_1', 'photo_1_file');
        $this->saveOrReplacePhoto($request, $item, 'photo_2', 'photo_2_file');
        $this->saveOrReplacePhoto($request, $item, 'photo_3', 'photo_3_file');

        // Validación final de fotos
        try {
            $this->ensureThreePhotos($item);
        } catch (ValidationException $e) {
            Log::warning('CatalogItem@store: faltan fotos después de crear item', [
                'item_id' => $item->id,
                'errors'  => $e->errors(),
            ]);
            throw $e;
        }

        // Sync ML (no rompe UI si falla)
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

            'category_key'=> ['nullable', 'string', 'max:190'],
            'use_internal'=> ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],

            'brand_name'  => ['nullable', 'string', 'max:120'],
            'model_name'  => ['nullable', 'string', 'max:120'],
            'meli_gtin'   => ['nullable', 'string', 'max:50'],
            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],
        ]);

        Log::info('CatalogItem@update: datos validados', [
            'item_id' => $catalogItem->id,
            'data'    => $data,
        ]);

        if (!empty($data['category_key'])) {
            $validKeys = array_keys(config('catalog.product_categories', []));
            if (!in_array($data['category_key'], $validKeys, true)) {
                Log::warning('CatalogItem@update: category_key no es válida, se limpia', [
                    'item_id'      => $catalogItem->id,
                    'category_key' => $data['category_key'],
                ]);
                $data['category_key'] = null;
            }
        }

        $baseSlug = isset($data['slug']) && trim($data['slug']) !== ''
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        if ($baseSlug === '') {
            $baseSlug = Str::slug(Str::random(8));
        }

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

            Log::info('CatalogItem@update: item actualizado en BD', [
                'item_id'      => $catalogItem->id,
                'slug'         => $catalogItem->slug,
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
                ->withErrors(['general' => 'No se pudo actualizar el producto en la base de datos. Revisa el log para más detalles.']);
        }

        $this->saveOrReplacePhoto($request, $catalogItem, 'photo_1', 'photo_1_file');
        $this->saveOrReplacePhoto($request, $catalogItem, 'photo_2', 'photo_2_file');
        $this->saveOrReplacePhoto($request, $catalogItem, 'photo_3', 'photo_3_file');

        try {
            $this->ensureThreePhotos($catalogItem);
        } catch (ValidationException $e) {
            Log::warning('CatalogItem@update: faltan fotos después de update', [
                'item_id' => $catalogItem->id,
                'errors'  => $e->errors(),
            ]);
            throw $e;
        }

        $this->dispatchMeliSync($catalogItem->fresh());

        return back()->with('ok', 'Producto web actualizado. Sincronización con Mercado Libre encolada.');
    }

    public function destroy(CatalogItem $catalogItem)
    {
        Log::info('CatalogItem@destroy: inicio', [
            'item_id' => $catalogItem->id,
        ]);

        $this->deletePublicFileIfExists($catalogItem->photo_1);
        $this->deletePublicFileIfExists($catalogItem->photo_2);
        $this->deletePublicFileIfExists($catalogItem->photo_3);

        $catalogItem->delete();
        $this->dispatchMeliSync($catalogItem);

        Log::info('CatalogItem@destroy: item eliminado', [
            'item_id' => $catalogItem->id,
        ]);

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

        Log::info('CatalogItem@toggleStatus: estado cambiado', [
            'item_id' => $catalogItem->id,
            'status'  => $catalogItem->status,
        ]);

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

    /* ===============================================
     |  IA: Captura desde archivos / imágenes / PDF
     |  POST /admin/catalog/ai-from-upload
     |  => soporta múltiples productos (items[])
     ================================================*/
    public function aiFromUpload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:8192|mimes:jpg,jpeg,png,webp,pdf',
        ]);

        $files = $request->file('files', []);

        if (empty($files)) {
            return response()->json([
                'error' => 'No se recibieron archivos.',
            ], 422);
        }

        // Config OpenAI
        $apiKey  = config('services.openai.api_key') ?: config('services.openai.key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');

        $modelId = 'gpt-4.1-mini';

        if (!$apiKey) {
            Log::warning('AI catalog error: missing OpenAI API key');
            return response()->json([
                'error' => 'Falta configurar la API key de OpenAI en el servidor.',
            ], 500);
        }

        // 1) Subir archivos a /v1/files
        $fileInputs = [];

        foreach ($files as $file) {
            if (!$file) continue;

            try {
                $uploadResponse = Http::withToken($apiKey)
                    ->attach(
                        'file',
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName()
                    )
                    ->post($baseUrl.'/v1/files', [
                        'purpose' => 'user_data',
                    ]);

                if (!$uploadResponse->ok()) {
                    Log::warning('AI catalog file upload error', [
                        'status' => $uploadResponse->status(),
                        'body'   => $uploadResponse->body(),
                    ]);

                    return response()->json([
                        'error' => 'Error subiendo archivo(s) a OpenAI.',
                    ], 500);
                }

                $fileId = $uploadResponse->json('id');
                if (!$fileId) {
                    Log::warning('AI catalog file upload without id', [
                        'body' => $uploadResponse->json(),
                    ]);

                    return response()->json([
                        'error' => 'OpenAI no regresó un ID de archivo.',
                    ], 500);
                }

                $fileInputs[] = [
                    'type'    => 'input_file',
                    'file_id' => $fileId,
                ];
            } catch (\Throwable $e) {
                Log::error('AI catalog error uploading file', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => 'Error al subir archivo(s) a OpenAI.',
                ], 500);
            }
        }

        if (empty($fileInputs)) {
            return response()->json([
                'error' => 'No se pudieron preparar los archivos para la IA.',
            ], 500);
        }

        // 2) Llamar a /v1/responses
        $systemPrompt = <<<TXT
Eres un asistente experto en catálogo de productos, papelería, equipo médico y comercio electrónico (México).

A partir de los archivos (PDF o imágenes) que te envío (facturas, remisiones, listados):
- Ignora datos de la tienda, RFC, direcciones, totales, impuestos y notas generales.
- Identifica TODOS los renglones que describan productos (conceptos de venta).
- Para cada producto, genera un objeto con esta estructura EXACTA:

{
  "name": "Nombre completo del producto",
  "slug": "slug-sugerido-en-kebab-case",
  "description": "Descripción larga en español, ordenada y con frases cortas.",
  "excerpt": "Resumen corto en una o dos frases.",
  "price": 0,
  "brand_name": "",
  "model_name": "",
  "meli_gtin": "",
  "quantity": 0
}

La RESPUESTA FINAL debe ser EXCLUSIVAMENTE un JSON con esta forma:

{
  "items": [
    { ...producto_1... },
    { ...producto_2... },
    { ...producto_3... }
  ]
}

Reglas:
- Responde ÚNICAMENTE ese JSON y nada más (sin texto adicional).
- "name": debe ser claro: tipo de producto + marca + modelo + medida o presentación si aplica.
- "slug": en kebab-case, basado en el nombre (sin tildes, sin símbolos, solo letras, números y guiones).
- "price": en MXN, numérico (sin símbolo $). Usa el precio unitario si aparece; si no hay, usa 0.
- "brand_name": marca comercial que ve el cliente. Si no aparece, cadena vacía.
- "model_name": modelo o referencia. Si no aparece, cadena vacía.
- "meli_gtin": EAN/UPC si lo detectas completo (solo dígitos); si no, cadena vacía.
- "quantity": número de piezas/unidades compradas según el renglón. Si no se ve claro, usa 1.
- Si solo se ve un producto, devuelve un array con un solo elemento en "items".
- No inventes datos que claramente no aparezcan.
TXT;

        $userText = "Analiza los archivos adjuntos (PDFs/imágenes) y genera SOLO el JSON con items[], uno por producto encontrado.";

        try {
            $response = Http::withToken($apiKey)
                ->timeout(config('services.openai.timeout', 60))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($baseUrl.'/v1/responses', [
                    'model'        => $modelId,
                    'instructions' => $systemPrompt,
                    'input'        => [
                        [
                            'role'    => 'user',
                            'content' => array_merge(
                                [
                                    [
                                        'type' => 'input_text',
                                        'text' => $userText,
                                    ],
                                ],
                                $fileInputs
                            ),
                        ],
                    ],
                    'max_output_tokens' => 2048,
                    'temperature'       => 0.1,
                ]);

            if (!$response->ok()) {
                Log::warning('AI catalog error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return response()->json([
                    'error' => 'La IA respondió con un error.',
                ], 500);
            }

            $json = $response->json();

            // Extraer texto
            $rawText = null;

            if (isset($json['output']) && is_array($json['output'])) {
                foreach ($json['output'] as $outItem) {
                    if (($outItem['type'] ?? null) === 'message' && isset($outItem['content'])) {
                        foreach ($outItem['content'] as $c) {
                            if (($c['type'] ?? null) === 'output_text' && isset($c['text'])) {
                                $rawText .= $c['text'];
                            }
                        }
                    }
                }
            }

            if (!$rawText && isset($json['output'][0]['content'][0]['text'])) {
                $rawText = $json['output'][0]['content'][0]['text'];
            }

            if (!$rawText) {
                Log::warning('AI catalog: no se pudo encontrar texto en la respuesta', ['json' => $json]);

                return response()->json([
                    'error' => 'No se pudo interpretar la respuesta de la IA.',
                ], 500);
            }

            $data = json_decode($rawText, true);
            if (!is_array($data)) {
                Log::warning('AI catalog: JSON inválido en salida de IA', [
                    'raw' => $rawText,
                ]);

                return response()->json([
                    'error' => 'La IA no devolvió un JSON válido.',
                ], 500);
            }

            // Normalizar a lista de items
            $items = [];

            if (isset($data['items']) && is_array($data['items'])) {
                $items = $data['items'];
            } elseif (is_array($data) && array_is_list($data)) {
                $items = $data;
            } else {
                $items = [$data];
            }

            $normalizedItems = [];

            foreach ($items as $row) {
                if (!is_array($row)) continue;

                // Precio
                $price = $row['price'] ?? null;
                if (is_string($price)) {
                    $clean = preg_replace('/[^0-9.,]/', '', $price);
                    $clean = str_replace(',', '.', $clean);
                    $price = is_numeric($clean) ? (float) $clean : null;
                }

                // Cantidad -> stock sugerido
                $qty = $row['quantity'] ?? ($row['qty'] ?? ($row['cantidad'] ?? ($row['stock'] ?? null)));
                if (is_string($qty)) {
                    $cleanQty = preg_replace('/[^0-9]/', '', $qty);
                    $qty = is_numeric($cleanQty) ? (int) $cleanQty : null;
                }
                if ($qty !== null) $qty = max(0, (int) $qty);

                $normalizedItems[] = [
                    'name'        => $row['name']        ?? null,
                    'slug'        => $row['slug']        ?? null,
                    'description' => $row['description'] ?? null,
                    'excerpt'     => $row['excerpt']     ?? null,
                    'price'       => $price,
                    'brand_name'  => $row['brand_name']  ?? null,
                    'model_name'  => $row['model_name']  ?? null,
                    'meli_gtin'   => $row['meli_gtin']   ?? null,
                    'stock'       => $qty,
                ];
            }

            if (empty($normalizedItems)) {
                return response()->json([
                    'error' => 'La IA no devolvió productos reconocibles.',
                ], 500);
            }

            $first = $normalizedItems[0];

            return response()->json([
                'suggestions' => $first,
                'items'       => $normalizedItems,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error llamando a OpenAI (Files + Responses) para catálogo', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Ocurrió un error al contactar la IA.',
            ], 500);
        }
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

        if (!$file instanceof UploadedFile) {
            return;
        }

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
        if ($old) {
            $this->deletePublicFileIfExists($old);
        }

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
    public function amazonPublish(CatalogItem $catalogItem, AmazonSpApiListingService $svc)
{
    $res = $svc->upsertBySku($catalogItem, [
        // aquí luego pondremos productType real (por categoría)
        // 'productType' => 'OFFICE_PRODUCTS',
    ]);

    if ($res['ok']) {
        return back()->with('ok', 'Solicitud enviada a Amazon. Revisa en Seller Central el estado del listing.');
    }

    $friendly = $res['message'] ?? 'No se pudo publicar/actualizar en Amazon.';
    return back()->with('ok', $friendly);
}

public function amazonView(CatalogItem $catalogItem, AmazonSpApiListingService $svc)
{
    if (!$catalogItem->sku) {
        return back()->with('ok', 'Este producto no tiene SKU. Amazon requiere SKU.');
    }

    $res = $svc->getBySku($catalogItem);

    if (!$res['ok']) {
        return back()->with('ok', $res['message'] ?? 'No se pudo consultar el listing en Amazon.');
    }

    // “Ver” en web: lo más práctico es abrir búsqueda por SKU en Amazon MX.
    // (Amazon no devuelve un permalink simple como ML)
    $sku = urlencode($catalogItem->sku);
    return redirect()->away("https://www.amazon.com.mx/s?k={$sku}");
}

public function amazonPause(CatalogItem $catalogItem, AmazonSpApiListingService $svc)
{
    // En Amazon “pausar” no es igual que ML. Aquí solo mandamos upsert y luego
    // ajustaremos atributos/quantity para dejarlo no disponible según tu caso.
    $res = $svc->upsertBySku($catalogItem, [
        'status' => 'inactive',
    ]);

    if ($res['ok']) {
        return back()->with('ok', 'Solicitud enviada a Amazon para pausar (según configuración de listing).');
    }

    return back()->with('ok', $res['message'] ?? 'No se pudo pausar en Amazon.');
}

public function amazonActivate(CatalogItem $catalogItem, AmazonSpApiListingService $svc)
{
    $res = $svc->upsertBySku($catalogItem, [
        'status' => 'active',
    ]);

    if ($res['ok']) {
        return back()->with('ok', 'Solicitud enviada a Amazon para activar/actualizar.');
    }

    return back()->with('ok', $res['message'] ?? 'No se pudo activar en Amazon.');
}

}
