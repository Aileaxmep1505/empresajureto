<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogAiIntake;
use App\Models\CatalogItem;
use App\Models\CategoryProduct;
use App\Models\Location;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\ShopifyService;
use Illuminate\Http\RedirectResponse;

class CatalogItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    /**
     * Aplica los filtros comunes (búsqueda, estado, destacados, muestras)
     * a una query de CatalogItem.
     *
     * Modo de muestras (parámetro "samples"):
     *   ''     => solo catálogo de venta (sin muestras)  [por defecto]
     *   'only' => solo muestras
     *   'all'  => todos (catálogo + muestras)
     *
     * Si $forceExcludeSamples es true, siempre se excluyen las muestras
     * sin importar el parámetro (se usa en reportes/analíticas).
     */
    private function applyCatalogFilters($q, Request $request, bool $forceExcludeSamples = false): void
    {
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

        if ($forceExcludeSamples) {
            $q->where('is_sample', false);
            return;
        }

        $mode = (string) $request->get('samples', '');
        if ($mode === 'only') {
            $q->where('is_sample', true);
        } elseif ($mode !== 'all') {
            $q->where('is_sample', false);
        }
        // 'all' => no se aplica ningún filtro de is_sample (salen todos).
    }

    public function index(Request $request)
    {
        $q = CatalogItem::query()->with(['categoryProduct', 'primaryLocation']);

        $this->applyCatalogFilters($q, $request);

        $items = $q->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.catalog.index', [
            'items'   => $items,
            'filters' => [
                's'             => trim((string) $request->get('s', '')),
                'status'        => $request->get('status'),
                'featured_only' => $request->boolean('featured_only'),
                'samples'       => (string) $request->get('samples', ''),
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $q = CatalogItem::query()->with(['categoryProduct', 'primaryLocation']);

        $this->applyCatalogFilters($q, $request);

        $items = $q->orderBy('id')->get();

        $rows = [];

        $rows[] = ['Inventario interno de Jureto'];
        $rows[] = [''];

        $rows[] = [
            'ID',
            'SKU',
            'GTIN/EAN',
            'Nombre',
            'Categoría',
            'Unidad de medida',
            'Contenido por unidad',
            'Ubicación principal',
            'Precio',
            'Precio oferta',
            'Stock',
            'Stock mínimo',
            'Stock máximo',
            'Estado',
            'Destacado',
            'Muestra',
            'Estado muestra',
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
                $it->meli_gtin,
                $it->name,
                $it->categoryProduct?->full_path ?? '',
                ($it->unit_measure ?? 'pieza') . ((($it->unit_measure ?? 'pieza') !== 'pieza') ? ' con ' . (string)($it->content_quantity ?? 1) . ' ' . (string)($it->content_unit_measure ?? 'pieza') : ''),
                $it->primaryLocation?->code ?? $it->primaryLocation?->name ?? '',
                (float) $it->price,
                $it->sale_price !== null ? (float) $it->sale_price : '',
                $it->stock,
                $it->stock_min,
                $it->stock_max,
                $statusText,
                $featuredText,
                $it->is_sample ? 'Sí' : 'No',
                $it->sampleStatusLabel() ?? '',
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

                        $headerIndex       = 2;
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

    public function exportPdf(Request $request)
    {
        $q = CatalogItem::query()->with(['categoryProduct', 'primaryLocation']);

        $this->applyCatalogFilters($q, $request);

        $items = $q->orderBy('id')->get();

        $logoBase64 = null;
        $logoPath   = public_path('images/logo-mail.png');
        if (is_file($logoPath)) {
            $logoData   = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
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

        if ($logoBase64) {
            $html .= '<div class="logo-wrap"><img class="logo" src="' . $logoBase64 . '" alt="Logo Jureto"></div>';
        }

        $html .= '<h1 class="title-main">Inventario interno de Jureto</h1>';
        $html .= '<p class="top-sub">Listado de productos con filtros actuales</p>';

        $html .= '<table><thead><tr>';
        $html .= '<th>ID</th>';
        $html .= '<th>SKU</th>';
        $html .= '<th>GTIN/EAN</th>';
        $html .= '<th>Nombre</th>';
        $html .= '<th>Categoría</th>';
        $html .= '<th>U.M.</th>';
        $html .= '<th>Contenido</th>';
        $html .= '<th>Ubicación</th>';
        $html .= '<th>Precio</th>';
        $html .= '<th>Oferta</th>';
        $html .= '<th>Stock</th>';
        $html .= '<th>Stock mín.</th>';
        $html .= '<th>Stock máx.</th>';
        $html .= '<th>Estado</th>';
        $html .= '<th>Destacado</th>';
        $html .= '<th>Muestra</th>';
        $html .= '<th>Estado muestra</th>';
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
            $html .= '<td>' . htmlspecialchars((string) $it->id) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($it->sku ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($it->meli_gtin ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) $it->name) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($it->categoryProduct?->full_path ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($it->unit_measure ?? 'pieza')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ((($it->unit_measure ?? 'pieza') !== 'pieza') ? (($it->content_quantity ?? 1) . ' ' . ($it->content_unit_measure ?? 'pieza')) : '1 pieza')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($it->primaryLocation?->code ?? $it->primaryLocation?->name ?? '')) . '</td>';
            $html .= '<td>$' . number_format((float) $it->price, 2) . '</td>';
            $html .= '<td>' . ($it->sale_price !== null ? '$' . number_format((float) $it->sale_price, 2) : '—') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($it->stock ?? 0)) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($it->stock_min ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($it->stock_max ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars($statusText) . '</td>';
            $html .= '<td>' . htmlspecialchars($featuredText) . '</td>';
            $html .= '<td>' . htmlspecialchars($it->is_sample ? 'Sí' : 'No') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($it->sampleStatusLabel() ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string) $it->slug) . '</td>';
            $html .= '<td>' . ($it->published_at ? htmlspecialchars($it->published_at->format('Y-m-d H:i')) : '—') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) ($it->meli_item_id ?? '')) . '</td>';
            $html .= '</tr>';
        }

        if ($items->isEmpty()) {
            $html .= '<tr><td colspan="20" class="muted" style="text-align:center;padding:14px 6px;">';
            $html .= 'No hay productos que coincidan con el filtro.';
            $html .= '</td></tr>';
        }

        $html .= '</tbody></table></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

        return $pdf->download('inventario-jureto.pdf');
    }

    public function create()
    {
        return view('admin.catalog.create', [
            'item'       => null,
            'categories' => $this->getCategoryOptions(),
            'locations'  => $this->getLocationOptions(),
        ]);
    }

    public function store(Request $request)
    {
        Log::info('CatalogItem@store: inicio', [
            'input' => $request->all(),
        ]);

        $skuOrGtin = trim((string) ($request->input('sku') ?: $request->input('meli_gtin') ?: ''));
        $request->merge([
            'sku'       => $skuOrGtin,
            'meli_gtin' => $skuOrGtin,
        ]);

        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'slug'                => ['nullable', 'string', 'max:255'],
            'sku'                 => ['required', 'string', 'max:120'],
            'price'               => ['required', 'numeric', 'min:0'],
            'sale_price'          => ['nullable', 'numeric', 'min:0'],
            'stock'               => ['required', 'integer', 'min:0'],
            'stock_min'           => ['required', 'integer', 'min:0'],
            'stock_max'           => ['required', 'integer', 'min:0'],
            'unit_measure'        => ['required', 'string', 'in:pieza,caja,paquete,rollo,juego,kit,bolsa,par,set,display,docena,metro,litro'],
            'content_quantity'     => ['nullable', 'integer', 'min:1'],
            'content_unit_measure' => ['nullable', 'string', 'in:pieza,caja,paquete,rollo,juego,kit,bolsa,par,set,display,docena,metro,litro'],
            'status'              => ['required', 'integer', 'in:0,1,2'],
            'is_featured'         => ['nullable', 'boolean'],
            'category_product_id' => ['required', 'integer', 'exists:category_products,id'],
            'primary_location_id' => ['nullable', 'integer', 'exists:locations,id'],

            'use_internal'        => ['nullable', 'boolean'],
            'brand_id'            => ['nullable', 'integer'],
            'category_id'         => ['nullable', 'integer'],

            'brand_name'          => ['nullable', 'string', 'max:120'],
            'model_name'          => ['nullable', 'string', 'max:120'],
            'meli_gtin'           => ['required', 'string', 'max:120'],

            'excerpt'             => ['nullable', 'string'],
            'description'         => ['nullable', 'string'],
            'published_at'        => ['nullable', 'date'],

            // Muestras
            'is_sample'           => ['nullable', 'boolean'],
            'sample_status'       => ['nullable', 'string', 'in:guardada,prestada,regalada,danada'],
            'sample_holder'       => ['nullable', 'string', 'max:255'],
            'sample_out_at'       => ['nullable', 'date'],
        ], [
            'sku.required'                 => 'El SKU interno / código GTIN es obligatorio.',
            'meli_gtin.required'           => 'El SKU interno / código GTIN es obligatorio.',
            'stock.required'               => 'El stock es obligatorio.',
            'stock_min.required'           => 'El stock mínimo es obligatorio.',
            'stock_max.required'           => 'El stock máximo es obligatorio.',
            'unit_measure.required'        => 'La unidad de medida es obligatoria.',
            'content_quantity.min'         => 'El contenido por unidad debe ser mínimo 1.',
            'category_product_id.required' => 'La categoría es obligatoria.',
        ]);

        if ((int) $data['stock_max'] < (int) $data['stock_min']) {
            throw ValidationException::withMessages([
                'stock_max' => 'El stock máximo no puede ser menor al stock mínimo.',
            ]);
        }

        $unitMeasure = strtolower(trim((string) ($data['unit_measure'] ?? 'pieza')));
        if ($unitMeasure === 'pieza') {
            $data['content_quantity'] = 1;
            $data['content_unit_measure'] = 'pieza';
        } else {
            $data['content_quantity'] = max(1, (int) ($data['content_quantity'] ?? 1));
            $data['content_unit_measure'] = strtolower(trim((string) ($data['content_unit_measure'] ?? 'pieza')));
        }

        $data['sku']       = trim((string) $data['sku']);
        $data['meli_gtin'] = $data['sku'];

        Log::info('CatalogItem@store: datos validados', [
            'data' => $data,
        ]);

        $baseSlug = isset($data['slug']) && trim($data['slug']) !== ''
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        if ($baseSlug === '') {
            $baseSlug = Str::slug(Str::random(8));
        }

        $slug = $baseSlug;
        $i = 1;
        while (CatalogItem::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . ($i++);
        }
        $data['slug'] = $slug;

        $data['is_featured']  = (bool) ($data['is_featured'] ?? false);
        $data['stock']        = (int) $data['stock'];
        $data['stock_min']    = (int) $data['stock_min'];
        $data['stock_max']    = (int) $data['stock_max'];
        $data['unit_measure'] = strtolower(trim((string) $data['unit_measure']));
        $data['content_quantity'] = (int) ($data['content_quantity'] ?? 1);
        $data['content_unit_measure'] = strtolower(trim((string) ($data['content_unit_measure'] ?? 'pieza')));
        $data['category_key'] = null;

        if (!$request->boolean('use_internal')) {
            $data['brand_id']    = null;
            $data['category_id'] = null;
        }

        // Normalización de muestras (en alta no se ajusta stock; se toma el capturado)
        $data['is_sample'] = (bool) ($data['is_sample'] ?? false);
        if ($data['is_sample']) {
            $status = (string) ($data['sample_status'] ?? 'guardada');
            if (!array_key_exists($status, CatalogItem::SAMPLE_STATUSES)) {
                $status = 'guardada';
            }
            $data['sample_status'] = $status;

            if (!in_array($status, CatalogItem::SAMPLE_OUT_STATUSES, true)) {
                $data['sample_holder'] = null;
                $data['sample_out_at'] = null;
            }
        } else {
            $data['sample_status'] = null;
            $data['sample_holder'] = null;
            $data['sample_out_at'] = null;
        }

        try {
            $item = new CatalogItem();
            $item->forceFill($data);
            $item->save();

            Log::info('CatalogItem@store: item creado en BD', [
                'item_id'             => $item->id,
                'slug'                => $item->slug,
                'category_product_id' => $item->category_product_id ?? null,
                'primary_location_id' => $item->primary_location_id ?? null,
                'unit_measure'        => $item->unit_measure ?? null,
                'content_quantity'     => $item->content_quantity ?? null,
                'content_unit_measure' => $item->content_unit_measure ?? null,
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

        $this->saveOrReplacePhoto($request, $item, 'photo_1', 'photo_1_file');
        $this->saveOrReplacePhoto($request, $item, 'photo_2', 'photo_2_file');
        $this->saveOrReplacePhoto($request, $item, 'photo_3', 'photo_3_file');

        try {
            $this->ensureThreePhotos($item);
        } catch (ValidationException $e) {
            Log::warning('CatalogItem@store: faltan fotos después de crear item', [
                'item_id' => $item->id,
                'errors'  => $e->errors(),
            ]);
            throw $e;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok'   => true,
                'item' => $item->fresh(['categoryProduct', 'primaryLocation']),
                'msg'  => 'Producto web creado.',
            ]);
        }

        return redirect()
            ->route('admin.catalog.create')
            ->with('ok', 'Producto web creado correctamente.');
    }

    public function edit(CatalogItem $catalogItem)
    {
        $catalogItem->load(['categoryProduct', 'primaryLocation']);

        return view('admin.catalog.edit', [
            'item'       => $catalogItem,
            'categories' => $this->getCategoryOptions(),
            'locations'  => $this->getLocationOptions(),
        ]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        Log::info('CatalogItem@update: inicio', [
            'item_id' => $catalogItem->id,
            'input'   => $request->all(),
        ]);

        $skuOrGtin = trim((string) ($request->input('sku') ?: $request->input('meli_gtin') ?: ''));
        $request->merge([
            'sku'       => $skuOrGtin,
            'meli_gtin' => $skuOrGtin,
        ]);

        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'slug'                => ['nullable', 'string', 'max:255'],
            'sku'                 => ['required', 'string', 'max:120'],
            'price'               => ['required', 'numeric', 'min:0'],
            'sale_price'          => ['nullable', 'numeric', 'min:0'],
            'stock'               => ['required', 'integer', 'min:0'],
            'stock_min'           => ['required', 'integer', 'min:0'],
            'stock_max'           => ['required', 'integer', 'min:0'],
            'unit_measure'        => ['required', 'string', 'in:pieza,caja,paquete,rollo,juego,kit,bolsa,par,set,display,docena,metro,litro'],
            'content_quantity'     => ['nullable', 'integer', 'min:1'],
            'content_unit_measure' => ['nullable', 'string', 'in:pieza,caja,paquete,rollo,juego,kit,bolsa,par,set,display,docena,metro,litro'],
            'status'              => ['required', 'integer', 'in:0,1,2'],
            'is_featured'         => ['nullable', 'boolean'],
            'category_product_id' => ['required', 'integer', 'exists:category_products,id'],
            'primary_location_id' => ['nullable', 'integer', 'exists:locations,id'],

            'use_internal'        => ['nullable', 'boolean'],
            'brand_id'            => ['nullable', 'integer'],
            'category_id'         => ['nullable', 'integer'],

            'brand_name'          => ['nullable', 'string', 'max:120'],
            'model_name'          => ['nullable', 'string', 'max:120'],
            'meli_gtin'           => ['required', 'string', 'max:120'],
            'excerpt'             => ['nullable', 'string'],
            'description'         => ['nullable', 'string'],
            'published_at'        => ['nullable', 'date'],

            // Muestras
            'is_sample'           => ['nullable', 'boolean'],
            'sample_status'       => ['nullable', 'string', 'in:guardada,prestada,regalada,danada'],
            'sample_holder'       => ['nullable', 'string', 'max:255'],
            'sample_out_at'       => ['nullable', 'date'],
        ], [
            'sku.required'                 => 'El SKU interno / código GTIN es obligatorio.',
            'meli_gtin.required'           => 'El SKU interno / código GTIN es obligatorio.',
            'stock.required'               => 'El stock es obligatorio.',
            'stock_min.required'           => 'El stock mínimo es obligatorio.',
            'stock_max.required'           => 'El stock máximo es obligatorio.',
            'unit_measure.required'        => 'La unidad de medida es obligatoria.',
            'content_quantity.min'         => 'El contenido por unidad debe ser mínimo 1.',
            'category_product_id.required' => 'La categoría es obligatoria.',
        ]);

        if ((int) $data['stock_max'] < (int) $data['stock_min']) {
            throw ValidationException::withMessages([
                'stock_max' => 'El stock máximo no puede ser menor al stock mínimo.',
            ]);
        }

        $unitMeasure = strtolower(trim((string) ($data['unit_measure'] ?? 'pieza')));
        if ($unitMeasure === 'pieza') {
            $data['content_quantity'] = 1;
            $data['content_unit_measure'] = 'pieza';
        } else {
            $data['content_quantity'] = max(1, (int) ($data['content_quantity'] ?? 1));
            $data['content_unit_measure'] = strtolower(trim((string) ($data['content_unit_measure'] ?? 'pieza')));
        }

        $data['sku']       = trim((string) $data['sku']);
        $data['meli_gtin'] = $data['sku'];

        Log::info('CatalogItem@update: datos validados', [
            'item_id' => $catalogItem->id,
            'data'    => $data,
        ]);

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
            $slug = $baseSlug . '-' . ($i++);
        }

        $data['slug']         = $slug;
        $data['is_featured']  = (bool) ($data['is_featured'] ?? false);
        $data['stock']        = (int) $data['stock'];
        $data['stock_min']    = (int) $data['stock_min'];
        $data['stock_max']    = (int) $data['stock_max'];
        $data['unit_measure'] = strtolower(trim((string) $data['unit_measure']));
        $data['content_quantity'] = (int) ($data['content_quantity'] ?? 1);
        $data['content_unit_measure'] = strtolower(trim((string) ($data['content_unit_measure'] ?? 'pieza')));
        $data['category_key'] = null;

        if (!$request->boolean('use_internal')) {
            $data['brand_id']    = null;
            $data['category_id'] = null;
        }

        // Normalización de muestras
        $data['is_sample'] = (bool) ($data['is_sample'] ?? false);
        if ($data['is_sample']) {
            $status = (string) ($data['sample_status'] ?? 'guardada');
            if (!array_key_exists($status, CatalogItem::SAMPLE_STATUSES)) {
                $status = 'guardada';
            }
            $data['sample_status'] = $status;

            if (!in_array($status, CatalogItem::SAMPLE_OUT_STATUSES, true)) {
                $data['sample_holder'] = null;
                $data['sample_out_at'] = null;
            }
        } else {
            $data['sample_status'] = null;
            $data['sample_holder'] = null;
            $data['sample_out_at'] = null;
        }

        // Ajuste automático de stock por cambio de estado de muestra.
        // a "prestada"/"regalada" => salió 1 pieza (stock -1)
        // de vuelta a "guardada"/"dañada" => regresó 1 pieza (stock +1)
        if ($data['is_sample'] && $catalogItem->is_sample) {
            $wasOut = in_array((string) $catalogItem->sample_status, CatalogItem::SAMPLE_OUT_STATUSES, true);
            $isOut  = in_array((string) $data['sample_status'], CatalogItem::SAMPLE_OUT_STATUSES, true);

            if (!$wasOut && $isOut) {
                $data['stock'] = max(0, (int) $data['stock'] - 1);
            } elseif ($wasOut && !$isOut) {
                $data['stock'] = (int) $data['stock'] + 1;
            }
        }

        try {
            $catalogItem->forceFill($data);
            $catalogItem->save();

            Log::info('CatalogItem@update: item actualizado en BD', [
                'item_id'             => $catalogItem->id,
                'slug'                => $catalogItem->slug,
                'category_product_id' => $catalogItem->category_product_id ?? null,
                'primary_location_id' => $catalogItem->primary_location_id ?? null,
                'unit_measure'        => $catalogItem->unit_measure ?? null,
                'content_quantity'     => $catalogItem->content_quantity ?? null,
                'content_unit_measure' => $catalogItem->content_unit_measure ?? null,
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

        return back()->with('ok', 'Producto web actualizado correctamente.');
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

        Log::info('CatalogItem@destroy: item eliminado', [
            'item_id' => $catalogItem->id,
        ]);

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

        Log::info('CatalogItem@toggleStatus: estado cambiado', [
            'item_id' => $catalogItem->id,
            'status'  => $catalogItem->status,
        ]);

        return back()->with('ok', 'Estado actualizado correctamente.');
    }

    public function meliPublish(CatalogItem $catalogItem, MeliSyncService $svc)
    {
        if ($catalogItem->is_sample) {
            return back()->with('ok', 'Este producto es una muestra y no se publica en marketplaces.');
        }

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
        if ($catalogItem->is_sample) {
            return back()->with('ok', 'Este producto es una muestra y no se publica en marketplaces.');
        }

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

    public function aiStart(Request $r)
    {
        $intake = CatalogAiIntake::create([
            'token'       => Str::random(40),
            'created_by'  => $r->user()->id,
            'status'      => 0,
            'source_type' => $r->get('source_type', 'factura'),
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
        $request->validate([
            'files.*' => 'required|file|max:8192|mimes:jpg,jpeg,png,webp,pdf',
        ]);

        $files = $request->file('files', []);

        if (empty($files)) {
            return response()->json([
                'error' => 'No se recibieron archivos.',
            ], 422);
        }

        $apiKey  = config('services.openai.api_key') ?: config('services.openai.key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
        $modelId = 'gpt-4.1-mini';

        if (!$apiKey) {
            Log::warning('AI catalog error: missing OpenAI API key');

            return response()->json([
                'error' => 'Falta configurar la API key de OpenAI en el servidor.',
            ], 500);
        }

        $fileInputs = [];

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            try {
                $uploadResponse = Http::withToken($apiKey)
                    ->attach(
                        'file',
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName()
                    )
                    ->post($baseUrl . '/v1/files', [
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

        $userText = 'Analiza los archivos adjuntos (PDFs/imágenes) y genera SOLO el JSON con items[], uno por producto encontrado.';

        try {
            $response = Http::withToken($apiKey)
                ->timeout(config('services.openai.timeout', 60))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($baseUrl . '/v1/responses', [
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
                if (!is_array($row)) {
                    continue;
                }

                $price = $row['price'] ?? null;
                if (is_string($price)) {
                    $clean = preg_replace('/[^0-9.,]/', '', $price);
                    $clean = str_replace(',', '.', $clean);
                    $price = is_numeric($clean) ? (float) $clean : null;
                }

                $qty = $row['quantity'] ?? ($row['qty'] ?? ($row['cantidad'] ?? ($row['stock'] ?? null)));
                if (is_string($qty)) {
                    $cleanQty = preg_replace('/[^0-9]/', '', $qty);
                    $qty = is_numeric($cleanQty) ? (int) $cleanQty : null;
                }
                if ($qty !== null) {
                    $qty = max(0, (int) $qty);
                }

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
        if (!$path) {
            return;
        }

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
        if ($catalogItem->is_sample) {
            return back()->with('ok', 'Este producto es una muestra y no se publica en marketplaces.');
        }

        $res = $svc->upsertBySku($catalogItem, []);

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

        $sku = urlencode($catalogItem->sku);
        return redirect()->away("https://www.amazon.com.mx/s?k={$sku}");
    }

    public function amazonPause(CatalogItem $catalogItem, AmazonSpApiListingService $svc)
    {
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
        if ($catalogItem->is_sample) {
            return back()->with('ok', 'Este producto es una muestra y no se publica en marketplaces.');
        }

        $res = $svc->upsertBySku($catalogItem, [
            'status' => 'active',
        ]);

        if ($res['ok']) {
            return back()->with('ok', 'Solicitud enviada a Amazon para activar/actualizar.');
        }

        return back()->with('ok', $res['message'] ?? 'No se pudo activar en Amazon.');
    }

    public function analytics(Request $request)
    {
        $data = $this->buildCatalogAnalyticsData($request);

        return view('admin.catalog.analytics', $data);
    }

    public function analyticsPdf(Request $request)
    {
        $data = $this->buildCatalogAnalyticsData($request);

        $pdf = Pdf::loadView('admin.catalog.analytics_pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('reporte-inventario-jureto-' . now()->format('Y-m-d-H-i') . '.pdf');
    }

    private function buildCatalogAnalyticsData(Request $request): array
    {
        $q = CatalogItem::query()->with(['categoryProduct', 'primaryLocation']);

        $s = trim((string) $request->get('s', ''));
        if ($s !== '') {
            $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                   ->orWhere('sku', 'like', "%{$s}%")
                   ->orWhere('slug', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $q->where('status', (int) $request->integer('status'));
        }

        if ($request->boolean('featured_only')) {
            $q->where('is_featured', true);
        }

        // Los reportes de inventario no consideran muestras (no se venden).
        $q->where('is_sample', false);

        $items = $q->orderByDesc('id')->get();

        $effectivePrice = function ($it): float {
            if ($it->sale_price !== null && (float) $it->sale_price > 0) {
                return (float) $it->sale_price;
            }

            return (float) ($it->price ?? 0);
        };

        $stockValue = function ($it) use ($effectivePrice): float {
            return max(0, (float) ($it->stock ?? 0)) * $effectivePrice($it);
        };

        $totalProducts = $items->count();
        $totalStock = (float) $items->sum(fn ($it) => max(0, (float) ($it->stock ?? 0)));
        $totalMoney = (float) $items->sum(fn ($it) => $stockValue($it));

        $published = $items->where('status', 1)->count();
        $draft = $items->where('status', 0)->count();
        $hidden = $items->where('status', 2)->count();
        $featured = $items->where('is_featured', true)->count();

        $meliPublished = $items->filter(fn ($it) => !empty($it->meli_item_id))->count();
        $meliPending = max(0, $totalProducts - $meliPublished);

        $criticalItems = $items->filter(function ($it) {
            $stock = (float) ($it->stock ?? 0);
            $min = $it->stock_min;

            return $min !== null && $min !== '' && $stock <= (float) $min;
        })->sortBy(fn ($it) => (float) ($it->stock ?? 0))->values();

        $noStockItems = $items->filter(fn ($it) => (float) ($it->stock ?? 0) <= 0)->values();

        $topStock = $items
            ->sortByDesc(fn ($it) => (float) ($it->stock ?? 0))
            ->take(10)
            ->values();

        $lowStock = $items
            ->filter(fn ($it) => (float) ($it->stock ?? 0) > 0)
            ->sortBy(fn ($it) => (float) ($it->stock ?? 0))
            ->take(10)
            ->values();

        $expensiveItems = $items
            ->sortByDesc(fn ($it) => $effectivePrice($it))
            ->take(10)
            ->values();

        $cheapItems = $items
            ->filter(fn ($it) => $effectivePrice($it) > 0)
            ->sortBy(fn ($it) => $effectivePrice($it))
            ->take(10)
            ->values();

        $categoryStats = $items
            ->groupBy(fn ($it) => $it->categoryProduct?->full_path ?: 'Sin categoría')
            ->map(function ($group, $category) use ($stockValue) {
                return [
                    'category' => $category,
                    'count' => $group->count(),
                    'stock' => (float) $group->sum(fn ($it) => max(0, (float) ($it->stock ?? 0))),
                    'value' => (float) $group->sum(fn ($it) => $stockValue($it)),
                ];
            })
            ->sortByDesc('stock')
            ->take(10)
            ->values();

        $movementStats = $this->getCatalogMovementStats($items->pluck('id')->all());

        $topMovements = collect();
        $fastMoving = collect();
        $movementSource = $movementStats['source'];

        if ($movementStats['rows']->isNotEmpty()) {
            $itemsById = $items->keyBy('id');

            $topMovements = $movementStats['rows']
                ->sortByDesc('total_movements')
                ->take(10)
                ->map(function ($row) use ($itemsById) {
                    $it = $itemsById->get($row['item_id']);

                    return [
                        'item' => $it,
                        'total_movements' => $row['total_movements'],
                        'outgoing' => $row['outgoing'],
                        'incoming' => $row['incoming'],
                    ];
                })
                ->filter(fn ($row) => $row['item'])
                ->values();

            $fastMoving = $movementStats['rows']
                ->sortByDesc('outgoing')
                ->take(10)
                ->map(function ($row) use ($itemsById) {
                    $it = $itemsById->get($row['item_id']);

                    return [
                        'item' => $it,
                        'total_movements' => $row['total_movements'],
                        'outgoing' => $row['outgoing'],
                        'incoming' => $row['incoming'],
                    ];
                })
                ->filter(fn ($row) => $row['item'])
                ->values();
        }

        if ($fastMoving->isEmpty()) {
            $fastMoving = $criticalItems
                ->take(10)
                ->map(function ($it) {
                    return [
                        'item' => $it,
                        'total_movements' => null,
                        'outgoing' => null,
                        'incoming' => null,
                    ];
                })
                ->values();

            $movementSource = null;
        }

        return [
            'items' => $items,
            'filters' => [
                's' => $s,
                'status' => $request->get('status'),
                'featured_only' => $request->boolean('featured_only'),
            ],
            'summary' => [
                'total_products' => $totalProducts,
                'total_stock' => $totalStock,
                'total_money' => $totalMoney,
                'published' => $published,
                'draft' => $draft,
                'hidden' => $hidden,
                'featured' => $featured,
                'meli_published' => $meliPublished,
                'meli_pending' => $meliPending,
                'critical' => $criticalItems->count(),
                'no_stock' => $noStockItems->count(),
            ],
            'criticalItems' => $criticalItems->take(12),
            'noStockItems' => $noStockItems->take(12),
            'topStock' => $topStock,
            'lowStock' => $lowStock,
            'expensiveItems' => $expensiveItems,
            'cheapItems' => $cheapItems,
            'categoryStats' => $categoryStats,
            'topMovements' => $topMovements,
            'fastMoving' => $fastMoving,
            'movementSource' => $movementSource,
            'effectivePrice' => $effectivePrice,
            'stockValue' => $stockValue,
        ];
    }

    private function getCatalogMovementStats(array $itemIds): array
    {
        $itemIds = array_values(array_filter(array_map('intval', $itemIds)));

        if (empty($itemIds)) {
            return [
                'source' => null,
                'rows' => collect(),
            ];
        }

        $candidates = [
            [
                'table' => 'catalog_stock_movements',
                'item_columns' => ['catalog_item_id', 'item_id'],
                'qty_columns' => ['quantity', 'qty', 'amount'],
            ],
            [
                'table' => 'stock_movements',
                'item_columns' => ['catalog_item_id', 'item_id'],
                'qty_columns' => ['quantity', 'qty', 'amount'],
            ],
            [
                'table' => 'inventory_movements',
                'item_columns' => ['catalog_item_id', 'item_id'],
                'qty_columns' => ['quantity', 'qty', 'amount'],
            ],
            [
                'table' => 'wms_stock_movements',
                'item_columns' => ['catalog_item_id', 'item_id'],
                'qty_columns' => ['quantity', 'qty', 'amount'],
            ],
            [
                'table' => 'wms_inventory_movements',
                'item_columns' => ['catalog_item_id', 'item_id'],
                'qty_columns' => ['quantity', 'qty', 'amount'],
            ],
        ];

        foreach ($candidates as $candidate) {
            $table = $candidate['table'];

            if (!Schema::hasTable($table)) {
                continue;
            }

            $itemColumn = collect($candidate['item_columns'])->first(fn ($column) => Schema::hasColumn($table, $column));
            $qtyColumn = collect($candidate['qty_columns'])->first(fn ($column) => Schema::hasColumn($table, $column));

            if (!$itemColumn || !$qtyColumn) {
                continue;
            }

            try {
                $rows = DB::table($table)
                    ->selectRaw("
                        {$itemColumn} as item_id,
                        SUM(ABS(COALESCE({$qtyColumn}, 0))) as total_movements,
                        SUM(CASE WHEN COALESCE({$qtyColumn}, 0) < 0 THEN ABS(COALESCE({$qtyColumn}, 0)) ELSE 0 END) as outgoing,
                        SUM(CASE WHEN COALESCE({$qtyColumn}, 0) > 0 THEN COALESCE({$qtyColumn}, 0) ELSE 0 END) as incoming
                    ")
                    ->whereIn($itemColumn, $itemIds)
                    ->groupBy($itemColumn)
                    ->get()
                    ->map(fn ($row) => [
                        'item_id' => (int) $row->item_id,
                        'total_movements' => (float) $row->total_movements,
                        'outgoing' => (float) $row->outgoing,
                        'incoming' => (float) $row->incoming,
                    ]);

                return [
                    'source' => $table,
                    'rows' => $rows,
                ];
            } catch (\Throwable $e) {
                Log::warning('Catalog analytics: no se pudieron leer movimientos', [
                    'table' => $table,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'source' => null,
            'rows' => collect(),
        ];
    }

    private function getCategoryOptions()
    {
        return CategoryProduct::query()
            ->orderBy('full_path')
            ->get();
    }

    private function getLocationOptions()
    {
        return Location::query()
            ->orderBy('code')
            ->orderBy('name')
            ->get();
    }

    public function shopifySync(CatalogItem $item, ShopifyService $shopify): RedirectResponse
    {
        try {
            if ($item->is_sample) {
                return back()->with('error', 'Las muestras no se sincronizan con Shopify.');
            }

            if (!$item->sku) {
                return back()->with('error', 'El producto necesita SKU para sincronizarse con Shopify.');
            }

            $shopify->syncCatalogItem($item);

            return back()->with('success', 'Producto sincronizado correctamente con Shopify.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Error Shopify: ' . $e->getMessage());
        }
    }
}