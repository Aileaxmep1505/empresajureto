<?php

namespace App\Http\Controllers;

use App\Models\{
    Cotizacion,
    CotizacionProducto,
    Client,
    Product,
    Venta,
    VentaProducto
};
use App\Services\CotizacionAiService;          // <<< ESTE ES TU SERVICIO DE IA/PDF
use App\Services\FacturaApiInternalService;   // <<< EL QUE SÍ EXISTE
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use PDF;

class CotizacionController extends Controller
{
    public function __construct(
        protected CotizacionAiService $ai,   // IA delegada a servicio
    ) {}

    /* ======================= CRUD BÁSICO ======================= */

    public function index()
    {
        $q = Cotizacion::with('cliente')->latest()->paginate(12);
        return view('cotizaciones.index', compact('q'));
    }

    public function create()
    {
        // CLIENTES (display dinámico)
        $clientCols = array_values(array_filter(['name','nombre','razon_social'], fn($c)=>Schema::hasColumn('clients',$c)));
        $clientDisplayExpr = $clientCols
            ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$clientCols)).", CONCAT('ID ', `id`))"
            : "CONCAT('ID ', `id`))";

        $clientesSelect = Client::query()
            ->select(['id', DB::raw("$clientDisplayExpr AS display")])
            ->orderByRaw($clientDisplayExpr)->get();

        $clientesInfo = Client::query()->get();

        // PRODUCTOS (nombre + COSTO base y price de referencia)
        $prodNameCols = array_values(array_filter(['nombre','name','descripcion','titulo','title'], fn($c)=>Schema::hasColumn('products',$c)));
        $prodNameExpr = $prodNameCols
            ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$prodNameCols)).", CONCAT('ID ', `id`))"
            : "CONCAT('ID ', `id`)";

        $costCols = array_values(array_filter(['cost','costo','precio_costo','precio_compra'], fn($c)=>Schema::hasColumn('products',$c)));
        $costExpr = $costCols
            ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$costCols)).',0)'
            : '0';

        $priceCols = array_values(array_filter(['price','precio','precio_unitario'], fn($c)=>Schema::hasColumn('products',$c)));
        $priceExpr = $priceCols
            ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$priceCols)).',0)'
            : '0';

        $brandExpr    = $this->coalesceExpr('products',['brand','marca'],"NULL");
        $categoryExpr = $this->coalesceExpr('products',['category','categoria'],"NULL");
        $colorExpr    = $this->coalesceExpr('products',['color','colour'],"NULL");
        $matExpr      = $this->coalesceExpr('products',['material'],"NULL");
        $imgExpr      = $this->coalesceExpr('products',['image','imagen','foto','thumb','thumbnail','image_path'],"NULL");
        $stockExpr    = $this->coalesceExpr('products',['stock','existencia'],"NULL");

        $productos = Product::query()
            ->select([
                'id',
                DB::raw("$prodNameExpr AS display"),
                DB::raw("$costExpr AS cost"),
                DB::raw("$priceExpr AS price"),
                DB::raw("$brandExpr AS brand"),
                DB::raw("$categoryExpr AS category"),
                DB::raw("$colorExpr AS color"),
                DB::raw("$matExpr AS material"),
                DB::raw("$imgExpr AS image"),
                DB::raw("$stockExpr AS stock"),
            ])->orderByRaw($prodNameExpr)->get();

        return view('cotizaciones.create', compact('clientesSelect','clientesInfo','productos'));
    }

    private function coalesceExpr(string $table, array $candidates, string $fallbackExpr="NULL"): string
    {
        $cols = array_values(array_filter($candidates, fn($c)=>Schema::hasColumn($table,$c)));
        return $cols ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$cols)).", $fallbackExpr)" : $fallbackExpr;
    }

    public function store(Request $r)
    {
        $raw = $r->get('items');
        if (is_string($raw)) $r->merge(['items'=>json_decode($raw,true) ?? []]);

        $data = $r->validate([
            'cliente_id'      => ['required','exists:clients,id'],
            'notas'           => ['nullable','string'],
            'descuento'       => ['nullable','numeric'],
            'envio'           => ['nullable','numeric'],
            'validez_dias'    => ['nullable','integer','min:0','max:365'],
            'utilidad_global' => ['nullable','numeric','min:0'],

            'items'                   => ['required','array','min:1'],
            'items.*.producto_id'     => ['required','exists:products,id'],
            'items.*.descripcion'     => ['nullable','string'],
            'items.*.cantidad'        => ['required','numeric','min:0.01'],
            'items.*.cost'            => ['required','numeric','min:0'],
            'items.*.descuento'       => ['nullable','numeric','min:0'],
            'items.*.iva_porcentaje'  => ['nullable','numeric','min:0','max:100'],
        ]);

        $cot = DB::transaction(function() use ($data){
            $cot = new Cotizacion();
            $cot->cliente_id      = $data['cliente_id'];
            $cot->notas           = $data['notas'] ?? null;
            $cot->descuento       = $data['descuento'] ?? 0;
            $cot->envio           = $data['envio'] ?? 0;
            $cot->validez_dias    = (int)($data['validez_dias'] ?? 15);
            $cot->utilidad_global = (float)($data['utilidad_global'] ?? 0);
            $cot->setValidez();
            $cot->save();

            $items = collect($data['items'])->map(function($it) use ($cot){
                $cost = (float)$it['cost'];
                $qty  = (float)$it['cantidad'];
                $desc = (float)($it['descuento'] ?? 0);
                $ivaP = (float)($it['iva_porcentaje'] ?? 16);

                $precioUnit = round($cost * (1 + ($cot->utilidad_global/100)), 2);
                $base       = max(0, ($precioUnit * $qty) - $desc);
                $ivaMonto   = round($base * ($ivaP/100), 2);
                $totalFila  = round($base + $ivaMonto, 2);

                return new CotizacionProducto([
                    'producto_id'     => $it['producto_id'],
                    'descripcion'     => $it['descripcion'] ?? null,
                    'cantidad'        => $qty,
                    'cost'            => $cost,
                    'precio_unitario' => $precioUnit,
                    'descuento'       => $desc,
                    'iva_porcentaje'  => $ivaP,
                    'importe_sin_iva' => round($base, 2),
                    'iva_monto'       => $ivaMonto,
                    'importe_total'   => $totalFila,
                    'importe'         => $totalFila,
                ]);
            });

            $cot->items()->saveMany($items);

            $cot->load('items');
            $cot->recalcularTotales();
            $cot->save();

            return $cot;
        });

        return redirect()->route('cotizaciones.show', $cot)->with('ok','Cotización creada.');
    }

    public function show($id)
    {
        $cotizacion = Cotizacion::with('cliente','items.producto','plazos')->find($id);
        if (!$cotizacion) return redirect()->route('cotizaciones.index')->with('error',"La cotización $id no existe.");
        return view('cotizaciones.show', compact('cotizacion'));
    }

    public function edit(Cotizacion $cotizacion)
    {
        abort_unless(in_array($cotizacion->estado,['borrador','enviada']), 403);

        $clientCols = array_values(array_filter(['name','nombre','razon_social'], fn($c)=>Schema::hasColumn('clients',$c)));
        $clientDisplayExpr = $clientCols
            ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$clientCols)).", CONCAT('ID ', `id`))"
            : "CONCAT('ID ', `id`)";

        $clientesSelect = Client::query()
            ->select(['id',DB::raw("$clientDisplayExpr AS display")])
            ->orderByRaw($clientDisplayExpr)->get();

        $clientesInfo = Client::query()->get();

        $prodNameCols = array_values(array_filter(['nombre','name','descripcion','titulo','title'], fn($c)=>Schema::hasColumn('products',$c)));
        $prodNameExpr = $prodNameCols
            ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$prodNameCols)).", CONCAT('ID ', `id`))"
            : "CONCAT('ID ', `id`)";

        $costCols = array_values(array_filter(['cost','costo','precio_costo','precio_compra'], fn($c)=>Schema::hasColumn('products',$c)));
        $costExpr = $costCols ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$costCols)).',0)' : '0';

        $priceCols = array_values(array_filter(['price','precio','precio_unitario'], fn($c)=>Schema::hasColumn('products',$c)));
        $priceExpr = $priceCols ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$priceCols)).',0)' : '0';

        $productos = Product::query()
            ->select(['id', DB::raw("$prodNameExpr AS display"), DB::raw("$costExpr AS cost"), DB::raw("$priceExpr AS price")])
            ->orderByRaw($prodNameExpr)->get();

        $cotizacion->load('items','plazos');

        return view('cotizaciones.edit', compact('cotizacion','clientesSelect','clientesInfo','productos'));
    }

    public function update(Request $r, Cotizacion $cotizacion)
    {
        abort_unless(in_array($cotizacion->estado,['borrador','enviada']), 403);

        $raw = $r->get('items');
        if (is_string($raw)) $r->merge(['items'=>json_decode($raw,true) ?? []]);

        $data = $r->validate([
            'cliente_id'      => ['required','exists:clients,id'],
            'notas'           => ['nullable','string'],
            'descuento'       => ['nullable','numeric'],
            'envio'           => ['nullable','numeric'],
            'validez_dias'    => ['nullable','integer','min:0','max:365'],
            'utilidad_global' => ['nullable','numeric','min:0'],

            'items'                   => ['required','array','min:1'],
            'items.*.producto_id'     => ['required','exists:products,id'],
            'items.*.descripcion'     => ['nullable','string'],
            'items.*.cantidad'        => ['required','numeric','min:0.01'],
            'items.*.cost'            => ['required','numeric','min:0'],
            'items.*.descuento'       => ['nullable','numeric','min:0'],
            'items.*.iva_porcentaje'  => ['nullable','numeric','min:0','max:100'],
        ]);

        DB::transaction(function() use ($cotizacion,$data){
            $cotizacion->update([
                'cliente_id'      => $data['cliente_id'],
                'notas'           => $data['notas'] ?? null,
                'descuento'       => $data['descuento'] ?? 0,
                'envio'           => $data['envio'] ?? 0,
                'validez_dias'    => (int)($data['validez_dias'] ?? 15),
                'utilidad_global' => (float)($data['utilidad_global'] ?? $cotizacion->utilidad_global ?? 0),
            ]);
            $cotizacion->setValidez();

            $cotizacion->items()->delete();

            $items = collect($data['items'])->map(function($it) use ($cotizacion){
                $cost = (float)$it['cost'];
                $qty  = (float)$it['cantidad'];
                $desc = (float)($it['descuento'] ?? 0);
                $ivaP = (float)($it['iva_porcentaje'] ?? 16);

                $precioUnit = round($cost * (1 + ($cotizacion->utilidad_global/100)), 2);
                $base       = max(0, ($precioUnit * $qty) - $desc);
                $ivaMonto   = round($base * ($ivaP/100), 2);
                $totalFila  = round($base + $ivaMonto, 2);

                return new CotizacionProducto([
                    'producto_id'     => $it['producto_id'],
                    'descripcion'     => $it['descripcion'] ?? null,
                    'cantidad'        => $qty,
                    'cost'            => $cost,
                    'precio_unitario' => $precioUnit,
                    'descuento'       => $desc,
                    'iva_porcentaje'  => $ivaP,
                    'importe_sin_iva' => round($base, 2),
                    'iva_monto'       => $ivaMonto,
                    'importe_total'   => $totalFila,
                    'importe'         => $totalFila,
                ]);
            });

            $cotizacion->items()->saveMany($items);

            $cotizacion->load('items');
            $cotizacion->recalcularTotales();
            $cotizacion->save();
        });

        return redirect()->route('cotizaciones.show',$cotizacion)->with('ok','Cotización actualizada.');
    }

    public function destroy(Cotizacion $cotizacion)
    {
        abort_unless(in_array($cotizacion->estado,['borrador','rechazada']), 403);
        $cotizacion->delete();
        return redirect()->route('cotizaciones.index')->with('ok','Cotización eliminada.');
    }

    public function aprobar(Cotizacion $cotizacion)
    {
        abort_unless(in_array($cotizacion->estado,['enviada','borrador']), 403);
        $cotizacion->estado = 'aprobada';
        $cotizacion->save();
        return back()->with('ok','Cotización aprobada.');
    }

    public function rechazar(Cotizacion $cotizacion)
    {
        abort_unless(in_array($cotizacion->estado,['enviada','borrador']), 403);
        $cotizacion->estado = 'rechazada';
        $cotizacion->save();
        return back()->with('ok','Cotización rechazada.');
    }

    public function pdf(Cotizacion $cotizacion)
    {
        $cotizacion->load('cliente','items.producto','plazos');
        $pdf = PDF::loadView('cotizaciones.pdf', compact('cotizacion'))->setPaper('letter');
        return $pdf->stream('COT-'.$cotizacion->folio.'.pdf');
    }

    /* ======================= BUSCADOR DE PRODUCTOS (AJAX) ======================= */

    public function buscarProductos(Request $r)
    {
        $r->validate([
            'q' => ['nullable','string','max:200'],
            'page' => ['nullable','integer','min:1'],
            'per_page' => ['nullable','integer','min:1','max:500'],
        ]);

        $page = (int)($r->input('page', 1));
        $per  = (int)($r->input('per_page', 50));

        $q = $this->productSearchQuery((string)$r->input('q',''));

        $q->orderByRaw("
            (CASE
                WHEN COALESCE(nombre, name, '') LIKE ? THEN 0
                ELSE 1
             END), COALESCE(nombre, name, descripcion, 'zzzz')
        ", ['%'.$r->input('q','').'%']);

        $paginator = $q->paginate($per, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function($p){
            return [
                'id'       => $p->id,
                'display'  => $p->nombre ?? $p->name ?? ('ID '.$p->id),
                'price'    => (float)($p->price ?? $p->precio ?? 0),
                'brand'    => $p->brand ?? $p->marca ?? null,
                'category' => $p->category ?? $p->categoria ?? null,
                'color'    => $p->color ?? null,
                'material' => $p->material ?? null,
                'sku'      => $p->sku ?? null,
                'image'    => $p->image ?? $p->imagen ?? $p->image_path ?? null,
                'stock'    => $p->stock ?? $p->existencia ?? null,
            ];
        });

        return response()->json([
            'total'      => $paginator->total(),
            'per_page'   => $paginator->perPage(),
            'current'    => $paginator->currentPage(),
            'last_page'  => $paginator->lastPage(),
            'items'      => $data,
        ]);
    }

    /* ======================= IA DESDE PDF (YA EN SERVICIO) ======================= */

    public function aiParse(Request $r)
    {
        return $this->ai->aiParse($r);
    }

    public function aiCreate(Request $r)
    {
        return $this->ai->aiCreate($r);
    }

    /* ======================= CONVERTIR A VENTA ======================= */

    public function convertirAVenta(Request $request, Cotizacion $cotizacion)
    {
        if (in_array((string) $cotizacion->estado, ['converted', 'cancelled'], true)) {
            return back()->withErrors(
                'Esta cotización no puede convertirse (estado actual: ' . ($cotizacion->estado ?? '—') . ').'
            );
        }

        $cotizacion->loadMissing('items.producto');
        if ($cotizacion->items->isEmpty()) {
            return back()->withErrors('La cotización no tiene conceptos para convertir.');
        }

        try {
            $venta = DB::transaction(function () use ($cotizacion) {

                $venta = new Venta();
                $venta->cliente_id    = $cotizacion->cliente_id;
                $venta->cotizacion_id = $cotizacion->id;
                $venta->moneda        = $cotizacion->moneda ?: 'MXN';
                $venta->notas         = $cotizacion->notas ?: null;

                $venta->utilidad_global = (float) ($cotizacion->utilidad_global ?? 0);
                $venta->descuento       = (float) ($cotizacion->descuento ?? 0);
                $venta->envio           = (float) ($cotizacion->envio ?? 0);
                $venta->estado          = 'emitida';

                if (array_key_exists('financiamiento_config', $cotizacion->getAttributes())) {
                    $venta->financiamiento_config = $cotizacion->financiamiento_config;
                }

                $venta->subtotal = 0;
                $venta->iva      = 0;
                $venta->total    = 0;
                $venta->save();

                $rows = [];
                $sumBase  = 0.0;
                $sumIva   = 0.0;
                $sumCosto = 0.0;

                $ventaProductoModel  = new VentaProducto();
                $ventaProductosTable = $ventaProductoModel->getTable();
                $hasCostColumn       = Schema::hasColumn($ventaProductosTable, 'cost');

                foreach ($cotizacion->items as $it) {
                    $cantidad  = max(0.01, (float) ($it->cantidad ?? 1));
                    $pu        = round((float) ($it->precio_unitario ?? $it->precio ?? 0), 2);
                    $descFila  = round((float) ($it->descuento ?? 0), 2);
                    $ivaPct    = round((float) ($it->iva_porcentaje ?? 0), 2);
                    $cost      = round((float) ($it->cost ?? 0), 2);

                    $base      = max(0, round($cantidad * $pu - $descFila, 2));
                    $ivaMonto  = round($base * ($ivaPct / 100), 2);
                    $importe   = round($base + $ivaMonto, 2);

                    $sumBase  += $base;
                    $sumIva   += $ivaMonto;
                    $sumCosto += ($cost * $cantidad);

                    $row = [
                        'venta_id'        => $venta->id,
                        'producto_id'     => $it->producto_id,
                        'descripcion'     => $it->descripcion ?? optional($it->producto)->name ?? 'Producto',
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $pu,
                        'descuento'       => $descFila,
                        'iva_porcentaje'  => $ivaPct,
                        'importe'         => $importe,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];

                    if ($hasCostColumn) $row['cost'] = $cost;
                    if (Schema::hasColumn($ventaProductosTable, 'importe_sin_iva')) $row['importe_sin_iva'] = $base;
                    if (Schema::hasColumn($ventaProductosTable, 'iva_monto'))       $row['iva_monto']       = $ivaMonto;

                    $rows[] = $row;
                }

                if (!empty($rows)) {
                    VentaProducto::insert($rows);
                }

                $venta->subtotal = round($sumBase, 2);
                $venta->iva      = round($sumIva, 2);
                $venta->total    = max(0, round($venta->subtotal - $venta->descuento + $venta->envio + $venta->iva, 2));

                if (Schema::hasColumn($venta->getTable(), 'inversion_total')) {
                    $venta->inversion_total = round($sumCosto, 2);
                }
                if (Schema::hasColumn($venta->getTable(), 'ganancia_estimada')) {
                    $gan = $cotizacion->ganancia_estimada;
                    if (is_null($gan)) $gan = round($venta->subtotal - $sumCosto, 2);
                    $venta->ganancia_estimada = (float) $gan;
                }

                $venta->save();

                $cotizacion->estado = 'converted';
                if (Schema::hasColumn($cotizacion->getTable(), 'converted_at')) {
                    $cotizacion->converted_at = now();
                }
                if (Schema::hasColumn($cotizacion->getTable(), 'venta_id')) {
                    $cotizacion->venta_id = $venta->id;
                }
                $cotizacion->save();

                return $venta;
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors('No se pudo convertir la cotización: ' . $e->getMessage());
        }

        $mustInvoice = $request->boolean('facturar') || (bool) config('services.facturaapi_internal.auto', false);

        if ($mustInvoice) {
            try {
                $svc = app(FacturaApiInternalService::class);
                $svc->facturarVenta($venta);
                $svc->guardarArchivos($venta);

                Log::info('Venta facturada automáticamente al convertir cotización', [
                    'venta_id' => $venta->id,
                    'uuid' => $venta->factura_uuid,
                ]);

                return redirect()
                    ->route('ventas.show', $venta)
                    ->with('ok', 'Venta creada y facturada correctamente.');
            } catch (\Throwable $e) {
                report($e);
                Log::warning('Venta creada pero falló el timbrado automático', [
                    'venta_id' => $venta->id,
                    'error' => $e->getMessage(),
                ]);

                return redirect()
                    ->route('ventas.show', $venta)
                    ->with('warn', 'Venta creada, pero la facturación falló: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('ventas.show', $venta)
            ->with('ok', 'Cotización convertida a venta correctamente.');
    }

    /* ======================= HELPERS DE BÚSQUEDA ======================= */

    private function productSearchQuery(string $queryText)
    {
        $qText = trim($queryText);
        $cols = [];
        foreach ([
            'name','nombre','descripcion','category','categoria','brand','marca',
            'color','material','sku','supplier_sku','tags','unit','unidad'
        ] as $c) {
            if (Schema::hasColumn('products', $c)) $cols[] = $c;
        }
        if (!$cols) $cols = ['id'];

        $tokens = $this->tokens($qText);
        $qb = Product::query()->select('*');

        if ($qText !== '') {
            $ftColsReal = $this->getFulltextColumns('products');
            $ftUsables  = array_values(array_intersect($cols, $ftColsReal));
            if (count($ftUsables) >= 1) {
                $ftCols = implode(',', array_map(fn($c)=>$c, $ftUsables));
                $boolean = $this->makeBooleanQueryFromTokens($tokens);
                if ($boolean !== '') {
                    $qb->whereRaw("MATCH($ftCols) AGAINST (? IN BOOLEAN MODE)", [$boolean]);
                    return $qb;
                }
            }
        }

        if (empty($tokens)) return $qb;

        foreach ($tokens as $t) {
            $qb->where(function($w) use ($cols, $t) {
                foreach ($cols as $c) {
                    $w->orWhere($c, 'LIKE', "%{$t}%");
                }
            });
        }

        return $qb;
    }

    private function makeBooleanQueryFromTokens(array $tokens): string
    {
        if (!$tokens) return '';
        $out = [];
        foreach ($tokens as $t) {
            $t = str_replace(['+','-','<','>','(',')','~','"','@'], ' ', (string)$t);
            $t = trim($t);
            if ($t === '' || mb_strlen($t) < 2) continue;
            $out[] = $t . '*';
        }
        return implode(' ', $out);
    }

    private function getFulltextColumns(string $table): array
    {
        static $cache = [];
        if (isset($cache[$table])) return $cache[$table];
        try {
            $db = DB::getDatabaseName();
            $rows = DB::select("
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_TYPE = 'FULLTEXT'
            ", [$db, $table]);
            $cache[$table] = array_values(array_unique(array_map(fn($r)=>$r->COLUMN_NAME, $rows)));
            return $cache[$table];
        } catch (\Throwable $e) {
            Log::info('FT detect fallback', ['msg'=>$e->getMessage()]);
            return [];
        }
    }

    private function normalize($s)
    {
        $s = mb_strtolower($s ?? '');
        if (class_exists('\Normalizer')) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
            $s = preg_replace('~\p{Mn}+~u','',$s);
        }
        return preg_replace('~\s+~u', ' ', trim($s));
    }

    private function tokens(string $s): array
    {
        $s = $this->normalize($s);
        $s = preg_replace('~[^a-z0-9áéíóúñ#\/\.\-\s]~u',' ', $s);
        $parts = preg_split('~\s+~u', $s) ?: [];
        $stop = ['de','del','la','el','y','en','para','con','sin','tipo','tinta','color','pieza','piezas','pza','pz','mm','cm','m','marca'];
        $parts = array_values(array_filter($parts, fn($t)=>mb_strlen($t)>=3 && !in_array($t,$stop,true)));
        return array_slice(array_unique($parts), 0, 20);
    }
}
