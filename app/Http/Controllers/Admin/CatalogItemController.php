<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogAiIntake;
use App\Models\CatalogItem;
use App\Services\AmazonSpApiListingService;
use App\Services\MeliHttp;
use App\Services\MeliSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
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
     |  Helpers: Categorías internas + ML category_id
     ========================================================= */

    /**
     * ✅ Mapa interno (category_key) -> Mercado Libre category_id (MLMxxxx)
     * Lo toma desde config:
     *   config('catalog.meli_category_map', [])
     *
     * Ejemplo en config:
     *  'meli_category_map' => [
     *     'papeleria' => 'MLM1672',
     *     'oficina'   => 'MLM1574',
     *  ]
     */
    private function resolveMeliCategoryIdFromCategoryKey(?string $categoryKey): ?string
    {
        $categoryKey = is_string($categoryKey) ? trim($categoryKey) : '';
        if ($categoryKey === '') return null;

        $map = config('catalog.meli_category_map', []);
        if (!is_array($map) || empty($map)) return null;

        $val = $map[$categoryKey] ?? null;
        $val = is_string($val) ? trim($val) : null;

        // Validación básica: categorías ML suelen iniciar con MLM
        if ($val && str_starts_with($val, 'MLM')) return $val;

        return null;
    }

    /**
     * ✅ Acepta category o category_key desde la vista,
     * pero SOLO guardamos category_key.
     * Además: si hay mapping, setea meli_category_id automáticamente.
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

        $data['category_key'] = $incoming ?: null;
        unset($data['category']);

        // ✅ Si NO mandaron meli_category_id manual, intentamos resolver por mapping
        $hasManualMeliCategory = array_key_exists('meli_category_id', $data) && is_string($data['meli_category_id']) && trim($data['meli_category_id']) !== '';
        if (!$hasManualMeliCategory) {
            $mlCat = $this->resolveMeliCategoryIdFromCategoryKey($data['category_key'] ?? null);
            if ($mlCat) {
                $data['meli_category_id'] = $mlCat;
            }
        }

        return $data;
    }

    /**
     * ✅ Fuerza a guardar campos sensibles aunque el Model no tenga fillable
     */
    private function forcePersistImportantFields(CatalogItem $item, array $data): void
    {
        $force = [];

        foreach ([
            // Web
            'sku','name','slug','price','sale_price','stock','status','published_at','is_featured',
            'category_key','excerpt','description',

            // ML
            'brand_name','model_name','meli_gtin','meli_category_id','meli_listing_type_id',

            // Amazon
            'amazon_sku','amazon_asin','amazon_product_type',
        ] as $k) {
            if (array_key_exists($k, $data)) $force[$k] = $data[$k];
        }

        // ✅ Si hay category_key y NO hay meli_category_id, intentamos resolverla
        if (empty($force['meli_category_id']) && !empty($force['category_key'])) {
            $mlCat = $this->resolveMeliCategoryIdFromCategoryKey($force['category_key']);
            if ($mlCat) $force['meli_category_id'] = $mlCat;
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

            if ($permalink && isset($item->meli_permalink) && $item->meli_permalink !== $permalink) {
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
            'ML Category',
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
                $it->meli_category_id ?? '',
            ];
        }

        $export = new class($rows) implements FromArray, ShouldAutoSize, WithEvents {
            private array $rows;

            public function __construct(array $rows) { $this->rows = array_values($rows); }
            public function array(): array { return $this->rows; }

            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet->getDelegate();

                        $headerRowExcel = 3;
                        $headerIndex    = 2;

                        $headerColumnCount = count($this->rows[$headerIndex]);
                        $lastColumnLetter  = Coordinate::stringFromColumnIndex($headerColumnCount);

                        $sheet->mergeCells("A1:{$lastColumnLetter}1");
                        $sheet->getStyle("A1")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 16],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_LEFT,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                            ],
                        ]);

                        $sheet->getStyle("A{$headerRowExcel}:{$lastColumnLetter}{$headerRowExcel}")->applyFromArray([
                            'font' => ['bold' => true],
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
        $html .= '<th>ID</th><th>SKU</th><th>Nombre</th><th>Precio</th><th>Oferta</th><th>Stock</th><th>Estado</th><th>Destacado</th><th>Slug</th><th>Publicado</th><th>ML ID</th><th>ML Category</th>';
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
            $html .= '<td>'.htmlspecialchars((string) ($it->meli_category_id ?? '')).'</td>';
            $html .= '</tr>';
        }

        if ($items->isEmpty()) {
            $html .= '<tr><td colspan="12" class="muted" style="text-align:center;padding:14px 6px;">';
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

            'category'     => ['nullable', 'string', 'max:190'],
            'category_key' => ['nullable', 'string', 'max:190'],

            'use_internal'=> ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],

            // ML fields
            'brand_name'          => ['nullable', 'string', 'max:120'],
            'model_name'          => ['nullable', 'string', 'max:120'],
            'meli_gtin'           => ['nullable', 'string', 'max:50'],
            'meli_category_id'    => ['nullable', 'string', 'max:32'],
            'meli_listing_type_id'=> ['nullable', 'string', 'max:32'],

            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],

            // Amazon fields
            'amazon_sku'          => ['nullable', 'string', 'max:120'],
            'amazon_asin'         => ['nullable', 'string', 'max:40'],
            'amazon_product_type' => ['nullable', 'string', 'max:80'],
        ]);

        $data = $this->normalizeCategoryFields($data);

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
                'item_id'        => $item->id,
                'slug'           => $item->slug,
                'category_key'   => $item->category_key ?? null,
                'meli_category'  => $item->meli_category_id ?? null,
                'brand_name'     => $item->brand_name ?? null,
                'model_name'     => $item->model_name ?? null,
                'meli_gtin'      => $item->meli_gtin ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('CatalogItem@store: ERROR al crear item', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'data'      => $data,
            ]);

            return back()->withInput()->withErrors(['general' => 'No se pudo guardar el producto. Revisa el log.']);
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

            'category'     => ['nullable', 'string', 'max:190'],
            'category_key' => ['nullable', 'string', 'max:190'],

            'use_internal'=> ['nullable', 'boolean'],
            'brand_id'    => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],

            // ML fields
            'brand_name'          => ['nullable', 'string', 'max:120'],
            'model_name'          => ['nullable', 'string', 'max:120'],
            'meli_gtin'           => ['nullable', 'string', 'max:50'],
            'meli_category_id'    => ['nullable', 'string', 'max:32'],
            'meli_listing_type_id'=> ['nullable', 'string', 'max:32'],

            'excerpt'     => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'published_at'=> ['nullable', 'date'],

            // Amazon fields
            'amazon_sku'          => ['nullable', 'string', 'max:120'],
            'amazon_asin'         => ['nullable', 'string', 'max:40'],
            'amazon_product_type' => ['nullable', 'string', 'max:80'],
        ]);

        $data = $this->normalizeCategoryFields($data);

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
                'item_id'        => $catalogItem->id,
                'category_key'   => $catalogItem->category_key ?? null,
                'meli_category'  => $catalogItem->meli_category_id ?? null,
                'brand_name'     => $catalogItem->brand_name ?? null,
                'model_name'     => $catalogItem->model_name ?? null,
                'meli_gtin'      => $catalogItem->meli_gtin ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('CatalogItem@update: ERROR al actualizar item', [
                'item_id'   => $catalogItem->id,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'data'      => $data,
            ]);

            return back()->withInput()->withErrors(['general' => 'No se pudo actualizar el producto. Revisa el log.']);
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

        return redirect()->route('admin.catalog.index')->with('ok', 'Producto web eliminado.');
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
 * ✅ Publicar SI O SÍ:
 * - Intenta normal (listing clásico)
 * - Si detecta "title invalid / catalog flow" -> intenta categoría sugerida por domain_discovery
 * - Si aún falla -> intenta catálogo (allow_catalog_fallback=true)
 *
 * Query:
 * - ?force=1  => activa el modo "si o si"
 * - ?catalog=1 => fuerza catálogo directo
 */
public function meliPublish(Request $request, CatalogItem $catalogItem, MeliSyncService $svc)
{
    $catalogItem = $catalogItem->fresh();

    $force        = $request->boolean('force');     // "SI O SÍ"
    $manualCatalog= $request->boolean('catalog');   // catálogo directo

    Log::info('CatalogItem@meliPublish: inicio', [
        'item_id'        => $catalogItem->id,
        'force'          => $force,
        'manual_catalog' => $manualCatalog,
        'meli_category'  => $catalogItem->meli_category_id ?? null,
        'brand_name'     => $catalogItem->brand_name ?? null,
        'model_name'     => $catalogItem->model_name ?? null,
        'gtin'           => $catalogItem->meli_gtin ?? null,
        'name'           => $catalogItem->name ?? null,
        'sku'            => $catalogItem->sku ?? null,
    ]);

    // Helper para detectar "flujo de catálogo"
    $isCatalogFlowError = function ($res): bool {
        $msg  = strtolower((string)($res['message'] ?? ''));
        $json = $res['json'] ?? [];
        $err0 = is_array($json) ? (strtolower((string)($json['message'] ?? '')) . ' ' . strtolower((string)($json['error'] ?? ''))) : '';
        $all  = $msg . ' ' . $err0;

        return str_contains($all, 'title') && str_contains($all, 'invalid')
            || str_contains($all, 'flujo') && str_contains($all, 'cat')
            || str_contains($all, 'catalog') && str_contains($all, 'flow');
    };

    // Helper: domain_discovery para sugerir categoría
    $discoverCategory = function (CatalogItem $item): ?string {
        try {
            $q = trim((string)($item->name ?? ''));
            if ($q === '') return null;

            $http = MeliHttp::withFreshToken();
            $resp = $http->get('https://api.mercadolibre.com/sites/MLM/domain_discovery/search', [
                'q' => $q,
            ]);

            if ($resp->failed()) {
                Log::warning('meli domain_discovery failed', ['status' => $resp->status(), 'body' => $resp->body()]);
                return null;
            }

            $arr = $resp->json();
            if (!is_array($arr) || empty($arr[0])) return null;

            // Normalmente: [0]['category_id']
            $cat = $arr[0]['category_id'] ?? null;
            $cat = is_string($cat) ? trim($cat) : null;

            return $cat ?: null;
        } catch (\Throwable $e) {
            Log::warning('meli domain_discovery exception', ['err' => $e->getMessage()]);
            return null;
        }
    };

    // 1) Si el usuario pidió catálogo directo, hazlo directo
    if ($manualCatalog) {
        try {
            $res = $svc->sync($catalogItem, [
                'activate'               => true,
                'update_description'     => true,
                'ensure_picture'         => true,
                'allow_catalog_fallback' => true,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['general' => 'Error publicando en ML (catálogo): ' . $e->getMessage()]);
        }

        if (!empty($res['ok'])) {
            $this->persistMeliResponse($catalogItem, $res);
            $mlId = $res['json']['id'] ?? $catalogItem->meli_item_id ?? '—';
            $mlSt = $res['json']['status'] ?? $catalogItem->meli_status ?? '—';
            return back()->with('ok', "Publicado en ML (catálogo). ID: {$mlId} · Estado: {$mlSt}");
        }

        return back()->withErrors(['general' => $res['message'] ?? 'No se pudo publicar en ML (catálogo).']);
    }

    // 2) Intento NORMAL
    try {
        $res = $svc->sync($catalogItem, [
            'activate'               => true,
            'update_description'     => true,
            'ensure_picture'         => true,
            'allow_catalog_fallback' => false,
        ]);
    } catch (\Throwable $e) {
        return back()->withErrors(['general' => 'Error publicando en ML: ' . $e->getMessage()]);
    }

    if (!empty($res['ok'])) {
        $this->persistMeliResponse($catalogItem, $res);
        $mlId = $res['json']['id'] ?? $catalogItem->meli_item_id ?? '—';
        $mlSt = $res['json']['status'] ?? $catalogItem->meli_status ?? '—';
        return back()->with('ok', "Publicado en ML. ID: {$mlId} · Estado: {$mlSt}");
    }

    // 3) Si NO es force, regresa el error normal
    if (!$force) {
        $friendly = $res['message'] ?? 'No se pudo publicar en Mercado Libre.';
        Log::warning('meli publish failed (no-force)', ['item_id' => $catalogItem->id, 'res' => $res]);
        return back()->withErrors(['general' => $friendly]);
    }

    // 4) FORCE: si fue error de catálogo/ title invalid -> intenta category discovery
    if ($isCatalogFlowError($res)) {
        $suggested = $discoverCategory($catalogItem);

        if ($suggested && $suggested !== ($catalogItem->meli_category_id ?? null)) {
            Log::info('meli force: actualizando categoría con domain_discovery', [
                'item_id' => $catalogItem->id,
                'from'    => $catalogItem->meli_category_id ?? null,
                'to'      => $suggested,
            ]);

            // Persistir si existe la columna
            try {
                if (isset($catalogItem->meli_category_id)) {
                    $catalogItem->meli_category_id = $suggested;
                    $catalogItem->save();
                }
            } catch (\Throwable $e) {
                Log::warning('meli force: no se pudo guardar meli_category_id', ['err' => $e->getMessage()]);
            }

            // Reintentar NORMAL con nueva categoría
            try {
                $catalogItem = $catalogItem->fresh();
                $res2 = $svc->sync($catalogItem, [
                    'activate'               => true,
                    'update_description'     => true,
                    'ensure_picture'         => true,
                    'allow_catalog_fallback' => false,
                ]);
            } catch (\Throwable $e) {
                $res2 = ['ok' => false, 'message' => $e->getMessage(), 'json' => null];
            }

            if (!empty($res2['ok'])) {
                $this->persistMeliResponse($catalogItem, $res2);
                $mlId = $res2['json']['id'] ?? $catalogItem->meli_item_id ?? '—';
                $mlSt = $res2['json']['status'] ?? $catalogItem->meli_status ?? '—';
                return back()->with('ok', "Publicado en ML (force: categoría ajustada). ID: {$mlId} · Estado: {$mlSt}");
            }

            // Si aún cae en catálogo, seguirá abajo al fallback
            $res = $res2;
        }
    }

    // 5) FORCE: último intento — catálogo fallback
    try {
        $catalogItem = $catalogItem->fresh();
        $res3 = $svc->sync($catalogItem, [
            'activate'               => true,
            'update_description'     => true,
            'ensure_picture'         => true,
            'allow_catalog_fallback' => true,
        ]);
    } catch (\Throwable $e) {
        return back()->withErrors(['general' => 'Error publicando en ML (force catálogo): ' . $e->getMessage()]);
    }

    if (!empty($res3['ok'])) {
        $this->persistMeliResponse($catalogItem, $res3);
        $mlId = $res3['json']['id'] ?? $catalogItem->meli_item_id ?? '—';
        $mlSt = $res3['json']['status'] ?? $catalogItem->meli_status ?? '—';
        return back()->with('ok', "Publicado en ML (force: catálogo). ID: {$mlId} · Estado: {$mlSt}");
    }

    $friendly = $res3['message'] ?? ($res['message'] ?? 'No se pudo publicar en ML (force).');
    Log::warning('meli publish failed (force)', ['item_id' => $catalogItem->id, 'res' => $res3]);
    return back()->withErrors(['general' => $friendly]);
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
    try {
        // =========================================================
        // 1) Detectar archivos (tu JS manda files[])
        // =========================================================
        $files = [];

        $arr1 = $request->file('files', []);
        if (is_array($arr1) && count($arr1)) $files = array_merge($files, $arr1);

        $arr2 = $request->file('ai_files', []);
        if (is_array($arr2) && count($arr2)) $files = array_merge($files, $arr2);

        /** @var \Illuminate\Http\UploadedFile|null $single */
        $single =
            $request->file('file')
            ?: $request->file('upload')
            ?: $request->file('photo')
            ?: $request->file('image')
            ?: $request->file('document');

        if ($single instanceof \Illuminate\Http\UploadedFile) $files[] = $single;

        $files = array_values(array_filter($files, fn($f) => $f instanceof \Illuminate\Http\UploadedFile));

        if (!count($files)) {
            return response()->json([
                'error' => 'No se recibió archivo. Envía files[] o ai_files[] (multipart/form-data).',
            ], 422);
        }

        // =========================================================
        // 2) Validar mimes
        // =========================================================
        $allowedMimes = ['image/jpeg','image/png','image/webp','application/pdf'];

        foreach ($files as $f) {
            if (!$f->isValid()) return response()->json(['error' => 'Archivo inválido o corrupto.'], 422);
            $mime = (string)($f->getMimeType() ?: '');
            if (!in_array($mime, $allowedMimes, true)) {
                return response()->json(['error' => 'Tipo no permitido. Sube JPG/PNG/WEBP o PDF.','mime'=>$mime], 422);
            }
        }

        // =========================================================
        // 3) API KEY + modelo
        // =========================================================
        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        $apiKey = is_string($apiKey) ? trim($apiKey) : '';
        if ($apiKey === '') {
            return response()->json(['error' => 'Falta OPENAI_API_KEY en .env (o services.openai.key).'], 500);
        }

        $model = env('OPENAI_MODEL', 'gpt-5-2025-08-07');

        // =========================================================
        // 4) Subir archivos a OpenAI Files API (purpose=user_data)
        // =========================================================
        $openAiFileIds = [];
        foreach ($files as $f) {
            $filename = $f->getClientOriginalName() ?: ('upload-' . now()->format('Ymd-His'));
            $stream   = fopen($f->getRealPath(), 'r');

            $resp = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->timeout(120)
                ->attach('file', $stream, $filename)
                ->asMultipart()
                ->post('https://api.openai.com/v1/files', [
                    'purpose' => 'user_data',
                ]);

            if (!$resp->ok()) {
                \Illuminate\Support\Facades\Log::error('aiFromUpload: OpenAI files.create failed', [
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                ]);
                return response()->json(['error' => 'No se pudo subir el archivo a la IA (Files API). Revisa logs.'], 500);
            }

            $fileId = $resp->json('id');
            if (!$fileId) return response()->json(['error' => 'OpenAI no devolvió file_id.'], 500);

            $openAiFileIds[] = $fileId;
        }

        // =========================================================
        // 5) Prompt (precisión alta)
        // =========================================================
        $instruction = <<<PROMPT
Eres un extractor de partidas de productos para un catálogo (México). Lee el/los archivos adjuntos (PDF o imágenes) y extrae TODAS las partidas.

No inventes: si un campo no aparece, pon null.
NO son productos: SUBTOTAL, TOTAL, IVA, IMPUESTO, DESCUENTO, ENVÍO, CAMBIO, PAGO, ANTICIPO, ABONO, SALDO, etc.

Reglas:
- Si hay columnas CANT | DESCRIPCIÓN | P. UNIT | IMPORTE -> usa P. UNIT como price.
- Si falta P. UNIT pero hay IMPORTE y CANT -> price = IMPORTE / CANT (si es claro).
- Si hay “10 cajas de 100 pzs” -> stock=1000 y notes lo explica.
- Máximo 80 productos.

Devuelve SOLO JSON.
PROMPT;

        // =========================================================
        // 6) Schema (NOTA: aquí VA DIRECTO como "schema", y name aparte)
        // =========================================================
        $schemaName = 'catalog_ai_extract_v1';

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['suggestions', 'items'],
            'properties' => [
                'suggestions' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => [
                        'name','slug','description','excerpt','price','sale_price','stock',
                        'category_key','brand_name','model_name','meli_gtin','meli_category_id','meli_listing_type_id',
                        'amazon_sku','amazon_asin','amazon_product_type'
                    ],
                    'properties' => [
                        'name' => ['type' => ['string','null']],
                        'slug' => ['type' => ['string','null']],
                        'description' => ['type' => ['string','null']],
                        'excerpt' => ['type' => ['string','null']],
                        'price' => ['type' => ['number','null']],
                        'sale_price' => ['type' => ['number','null']],
                        'stock' => ['type' => ['integer','null']],
                        'category_key' => ['type' => ['string','null']],
                        'brand_name' => ['type' => ['string','null']],
                        'model_name' => ['type' => ['string','null']],
                        'meli_gtin' => ['type' => ['string','null']],
                        'meli_category_id' => ['type' => ['string','null']],
                        'meli_listing_type_id' => ['type' => ['string','null']],
                        'amazon_sku' => ['type' => ['string','null']],
                        'amazon_asin' => ['type' => ['string','null']],
                        'amazon_product_type' => ['type' => ['string','null']],
                    ],
                ],
                'items' => [
                    'type' => 'array',
                    'maxItems' => 80,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name','price','stock','brand_name','model_name','meli_gtin','excerpt','description','extra'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'price' => ['type' => ['number','null']],
                            'stock' => ['type' => ['integer','null']],
                            'brand_name' => ['type' => ['string','null']],
                            'model_name' => ['type' => ['string','null']],
                            'meli_gtin' => ['type' => ['string','null']],
                            'excerpt' => ['type' => ['string','null']],
                            'description' => ['type' => ['string','null']],
                            'extra' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => ['unit','pack','raw_line','page','confidence','notes'],
                                'properties' => [
                                    'unit' => ['type' => ['string','null']],
                                    'pack' => ['type' => ['string','null']],
                                    'raw_line' => ['type' => ['string','null']],
                                    'page' => ['type' => ['integer','null']],
                                    'confidence' => ['type' => ['number','null']],
                                    'notes' => ['type' => ['string','null']],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // =========================================================
        // 7) Armar content: input_file + instrucciones
        // =========================================================
        $contentParts = [];
        foreach ($openAiFileIds as $fid) {
            $contentParts[] = ['type' => 'input_file', 'file_id' => $fid];
        }
        $contentParts[] = ['type' => 'input_text', 'text' => $instruction];

        // =========================================================
        // 8) Responses API: text.format con name+schema (NUEVO)
        // =========================================================
        $resp2 = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->timeout(240)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $model,
                'temperature' => 0,
                'input' => [[
                    'role' => 'user',
                    'content' => $contentParts,
                ]],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => $schemaName,
                        'schema' => $schema,
                    ],
                ],
                'max_output_tokens' => 3500,
            ]);

        if (!$resp2->ok()) {
            \Illuminate\Support\Facades\Log::error('aiFromUpload: OpenAI responses failed', [
                'status' => $resp2->status(),
                'body'   => $resp2->body(),
                'model'  => $model,
            ]);
            return response()->json(['error' => 'La IA no pudo procesar el archivo (Responses API). Revisa logs.'], 500);
        }

        $out = $resp2->json('output_text');
        if (!is_string($out) || trim($out) === '') {
            $out = '';
            $nodes = $resp2->json('output', []);
            if (is_array($nodes)) {
                foreach ($nodes as $node) {
                    $c = $node['content'] ?? null;
                    if (is_array($c)) {
                        foreach ($c as $part) {
                            if (($part['type'] ?? '') === 'output_text' && isset($part['text'])) {
                                $out .= (string)$part['text'];
                            }
                        }
                    }
                }
            }
        }

        $out = trim((string)$out);
        $json = json_decode($out, true);

        if (!is_array($json)) {
            \Illuminate\Support\Facades\Log::warning('aiFromUpload: IA no devolvió JSON parseable', [
                'out' => mb_substr($out, 0, 4000),
            ]);
            return response()->json(['error' => 'La IA respondió, pero no devolvió JSON válido.'], 422);
        }

        // =========================================================
        // 9) Normalización mínima
        // =========================================================
        $normalizeMoney = function($v) {
            if ($v === null || $v === '') return null;
            if (is_numeric($v)) return (float)$v;
            $s = (string)$v;
            $s = str_replace(['$', ' '], '', $s);
            if (preg_match('/\d{1,3}(,\d{3})+(\.\d{2})/', $s)) $s = str_replace(',', '', $s);
            else $s = str_replace(',', '.', $s);
            return is_numeric($s) ? (float)$s : null;
        };

        $normalizeInt = function($v) {
            if ($v === null || $v === '') return null;
            if (is_numeric($v)) return (int)$v;
            if (preg_match('/\d+/', (string)$v, $m)) return (int)$m[0];
            return null;
        };

        $suggestions = $json['suggestions'] ?? [];
        $items       = $json['items'] ?? [];

        if (is_array($suggestions)) {
            if (!empty($suggestions['name']) && empty($suggestions['slug'])) {
                $suggestions['slug'] = \Illuminate\Support\Str::slug((string)$suggestions['name']);
            }
            $suggestions['price'] = $normalizeMoney($suggestions['price'] ?? null);
            $suggestions['sale_price'] = $normalizeMoney($suggestions['sale_price'] ?? null);
            $suggestions['stock'] = $normalizeInt($suggestions['stock'] ?? null);
        }

        if (is_array($items)) {
            foreach ($items as $i => $it) {
                if (!is_array($it)) continue;
                $items[$i]['price'] = $normalizeMoney($it['price'] ?? null);
                $items[$i]['stock'] = $normalizeInt($it['stock'] ?? null);
            }
        }

        return response()->json([
            'suggestions' => $suggestions,
            'items'       => $items,
        ], 200);

    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('CatalogItem@aiFromUpload: exception', [
            'err'   => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json(['error' => 'Error interno en aiFromUpload. Revisa logs.'], 500);
    }
}

    /** Dispara el sync con ML sin romper la UI si algo truena */
    private function dispatchMeliSync(CatalogItem $item): void
    {
        try {
            app(MeliSyncService::class)->sync($item, [
                'activate'               => false,
                'update_description'     => false,
                'ensure_picture'         => false,
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
        if (!$amazonSku) return back()->withErrors(['general' => 'Falta AMAZON SKU (Seller SKU real).']);

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
        if (!$amazonSku) return back()->withErrors(['general' => 'Falta AMAZON SKU (Seller SKU real).']);

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
