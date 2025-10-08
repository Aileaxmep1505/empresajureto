<?php

namespace App\Http\Controllers;

use App\Models\{Cotizacion, CotizacionProducto, CotizacionPlazo, Client, Product, Venta, VentaProducto};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDF;
use Illuminate\Support\Facades\Http;
use App\Services\FacturaApiService;

// IA + PDF
use Smalot\PdfParser\Parser as PdfParser;   // composer require smalot/pdfparser
use Symfony\Component\Process\Process;

class CotizacionController extends Controller
{
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
        : "CONCAT('ID ', `id`)";

    $clientesSelect = Client::query()
        ->select(['id', DB::raw("$clientDisplayExpr AS display")])
        ->orderByRaw($clientDisplayExpr)->get();

    $clientesInfo = Client::query()->get();

    // PRODUCTOS (nombre + COSTO base y price de referencia)
    $prodNameCols = array_values(array_filter(['nombre','name','descripcion','titulo','title'], fn($c)=>Schema::hasColumn('products',$c)));
    $prodNameExpr = $prodNameCols
        ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$prodNameCols)).", CONCAT('ID ', `id`))"
        : "CONCAT('ID ', `id`)";
    // COSTO real (prioriza cost/costo/precio_compra)
    $costCols = array_values(array_filter(['cost','costo','precio_costo','precio_compra'], fn($c)=>Schema::hasColumn('products',$c)));
    $costExpr = $costCols
        ? 'COALESCE('.implode(',',array_map(fn($c)=>"`$c`",$costCols)).',0)'
        : '0';
    // price de referencia (solo para mostrar; el cálculo usa cost)
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
            DB::raw("$costExpr AS cost"),   // << COSTO base para el front
            DB::raw("$priceExpr AS price"), //    (solo referencia visual)
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
    // items puede venir como JSON string
    $raw = $r->get('items');
    if (is_string($raw)) $r->merge(['items'=>json_decode($raw,true) ?? []]);

    $data = $r->validate([
        'cliente_id'      => ['required','exists:clients,id'],
        'notas'           => ['nullable','string'],
        'descuento'       => ['nullable','numeric'],   // descuento global (monto $)
        'envio'           => ['nullable','numeric'],
        'validez_dias'    => ['nullable','integer','min:0','max:365'],
        'utilidad_global' => ['nullable','numeric','min:0'], // << NUEVO %

        'items'                   => ['required','array','min:1'],
        'items.*.producto_id'     => ['required','exists:products,id'],
        'items.*.descripcion'     => ['nullable','string'],
        'items.*.cantidad'        => ['required','numeric','min:0.01'],
        // ahora exigimos COSTO y NO usamos P.Unit enviado por front
        'items.*.cost'            => ['required','numeric','min:0'],
        'items.*.descuento'       => ['nullable','numeric','min:0'], // monto $
        'items.*.iva_porcentaje'  => ['nullable','numeric','min:0','max:100'],
    ]);

    $cot = DB::transaction(function() use ($data){
        $cot = new Cotizacion();
        $cot->cliente_id      = $data['cliente_id'];
        $cot->notas           = $data['notas'] ?? null;
        $cot->descuento       = $data['descuento'] ?? 0; // global (monto)
        $cot->envio           = $data['envio'] ?? 0;
        $cot->validez_dias    = (int)($data['validez_dias'] ?? 15);
        $cot->utilidad_global = (float)($data['utilidad_global'] ?? 0); // << %
        $cot->setValidez();
        $cot->save();

        // Construimos items con COSTO y dejamos snapshots calculados
        $items = collect($data['items'])->map(function($it) use ($cot){
            $cost = (float)$it['cost'];
            $qty  = (float)$it['cantidad'];
            $desc = (float)($it['descuento'] ?? 0);     // monto por fila
            $ivaP = (float)($it['iva_porcentaje'] ?? 16);

            // Precio unitario desde costo + utilidad_global de la cabecera
            $precioUnit = round($cost * (1 + ($cot->utilidad_global/100)), 2);
            $base       = max(0, ($precioUnit * $qty) - $desc);
            $ivaMonto   = round($base * ($ivaP/100), 2);
            $totalFila  = round($base + $ivaMonto, 2);

            return new CotizacionProducto([
                'producto_id'     => $it['producto_id'],
                'descripcion'     => $it['descripcion'] ?? null,
                'cantidad'        => $qty,
                'cost'            => $cost,          // << guardamos COSTO
                'precio_unitario' => $precioUnit,    // snapshot de venta
                'descuento'       => $desc,          // monto $
                'iva_porcentaje'  => $ivaP,
                // snapshots de importes
                'importe_sin_iva' => round($base, 2),
                'iva_monto'       => $ivaMonto,
                'importe_total'   => $totalFila,
                // compatibilidad con tu campo previo:
                'importe'         => $totalFila,
            ]);
        });

        $cot->items()->saveMany($items);

        // Recalcula totales en servidor (usa utilidad_global y cost)
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
    $clientesSelect = Client::query()->select(['id',DB::raw("$clientDisplayExpr AS display")])->orderByRaw($clientDisplayExpr)->get();
    $clientesInfo   = Client::query()->get();

    // Productos con COSTO para el editor
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
        'utilidad_global' => ['nullable','numeric','min:0'], // << NUEVO %

        'items'                   => ['required','array','min:1'],
        'items.*.producto_id'     => ['required','exists:products,id'],
        'items.*.descripcion'     => ['nullable','string'],
        'items.*.cantidad'        => ['required','numeric','min:0.01'],
        'items.*.cost'            => ['required','numeric','min:0'],  // << COSTO requerido
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

        // Reemplaza items
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
                'importe'         => $totalFila, // compat
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

        // Prioriza coincidencia en nombre y luego alfabético
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

    /* ======================= IA DESDE PDF ======================= */

    /**
     * Analiza el PDF y devuelve JSON:
     * - cliente_id, cliente_match_name
     * - resumen formal + overview por página
     * - items mapeados al catálogo (desde DB)
     * - alternativas y pendientes_ai (para front)
     * Params:
     *  - pages: "1,3-5,8" (opcional)
     */
  public function aiParse(Request $r)
{
    // --- LOGS MUY VERBOSOS PARA DIAGNÓSTICO ---
    $logStepFile = storage_path('logs/ai_parse_steps.log');
    @file_put_contents($logStepFile, '['.date('c')."] ---- aiParse INICIO ----\n", FILE_APPEND);

    // Captura de FATALES del motor PHP (no pasan por Laravel)
    register_shutdown_function(function () use ($logStepFile) {
        $e = error_get_last();
        if ($e && in_array($e['type'] ?? 0, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            @file_put_contents(storage_path('logs/ai_parse_fatal.log'),
                '['.date('c')."] {$e['message']} in {$e['file']}:{$e['line']}\n", FILE_APPEND);
            @file_put_contents($logStepFile,
                '['.date('c')."] FATAL: {$e['message']} in {$e['file']}:{$e['line']}\n", FILE_APPEND);
        }
    });

    @ini_set('log_errors', '1');
    @ini_set('error_log', storage_path('logs/php_runtime.log'));
    @error_reporting(E_ALL);

    // soft deadline
    $softSeconds = (int) env('AI_PARSE_SOFT_SECONDS', 540);
    $startedAt   = microtime(true);
    $deadline    = $startedAt + max(60, $softSeconds);
    DB::disableQueryLog();
    @set_time_limit($softSeconds + 60);
    ini_set('max_execution_time', (string)($softSeconds + 60));

    // Paso 1: validación
    @file_put_contents($logStepFile, '['.date('c')."] Paso 1: validación\n", FILE_APPEND);
    $r->validate([
        'pdf'   => ['required','file','mimes:pdf','max:20480'],
        'pages' => ['nullable','string'],
    ]);

    // Paso 2: OPENAI key
    @file_put_contents($logStepFile, '['.date('c')."] Paso 2: checar OPENAI_API_KEY\n", FILE_APPEND);
    if (!env('OPENAI_API_KEY')) {
        @file_put_contents($logStepFile, '['.date('c')."] ERROR: OPENAI_API_KEY faltante\n", FILE_APPEND);
        return response()->json(['ok'=>false,'error'=>'OPENAI_API_KEY no configurado en .env'], 422);
    }

    try {
        // Paso 3: extraer texto del PDF
        @file_put_contents($logStepFile, '['.date('c')."] Paso 3: extractPdfPagesText()\n", FILE_APPEND);
        [$pages, $wasOcr] = $this->extractPdfPagesText($r->file('pdf')->getRealPath());
        @file_put_contents($logStepFile, '['.date('c')."] Paso 3 OK: pages=".count($pages).", wasOcr=".($wasOcr?'1':'0')."\n", FILE_APPEND);

        if (empty($pages) || count(array_filter($pages, fn($t)=>trim($t) !== '')) === 0) {
            @file_put_contents($logStepFile, '['.date('c')."] Paso 3 ERROR: sin texto extraído\n", FILE_APPEND);
            return response()->json([
                'ok' => false,
                'error' => 'No se pudo extraer texto del PDF. Parece escaneado. Activa OCR externo (OCR_SPACE_API_KEY) o sube un PDF con texto digital.'
            ], 422);
        }

        $forced = $this->parsePagesParam((string)$r->input('pages',''), count($pages));

        // Paso 4: index por página
        @file_put_contents($logStepFile, '['.date('c')."] Paso 4: construir pageSummaries\n", FILE_APPEND);
        $pageSummaries = [];
        foreach ($pages as $i=>$txt) {
            $t = trim(preg_replace('~\s+~u', ' ', $txt));
            $pageSummaries[] = [
                'index'   => $i+1,
                'preview' => mb_substr($t, 0, 1000),
                'length'  => mb_strlen($t),
            ];
        }

        // Paso 5: seleccionar páginas relevantes
        @file_put_contents($logStepFile, '['.date('c')."] Paso 5: seleccionar páginas relevantes\n", FILE_APPEND);
        $reason = null; $relevant = [];
        if ($forced) {
            $relevant = $forced;
            $reason   = 'Páginas forzadas por el usuario.';
        } else {
            if ($this->timeUp($deadline)) {
                $relevant = range(1, min(count($pages), 20));
                $reason   = 'Tiempo limitado: selección heurística de primeras páginas.';
            } else {
                $findJson = $this->callOpenAIJson(json_encode([
                    'task' => 'find_relevant_pages_for_tender',
                    'instruction' => 'Eres estricto. Devuelve sólo JSON.',
                    'document_type_hint' => 'anexo técnico, listado de insumos, bases de licitación, cotización requerida',
                    'pages' => $pageSummaries,
                    'want' => ['items', 'terms', 'client', 'delivery', 'deadlines', 'payment', 'object'],
                    'notes' => 'Ignora carátulas, firmas y anexos legales repetidos. Prioriza tablas/listados.'
                ], JSON_UNESCAPED_UNICODE));
                $find = $this->safeJson($findJson);
                $relevant = array_values(array_unique(array_filter($find['relevant_pages'] ?? [], fn($n)=>is_int($n)&&$n>=1&&$n<=count($pages))));
                if (!$relevant) { $relevant = range(1, min(count($pages), 25)); }
                $reason = $find['reasoning'] ?? null;
            }
        }
        @file_put_contents($logStepFile, '['.date('c')."] Paso 5 OK: relevant=".json_encode($relevant)."\n", FILE_APPEND);

        // Paso 6: armar corpus
        @file_put_contents($logStepFile, '['.date('c')."] Paso 6: armar corpus\n", FILE_APPEND);
        $joined = [];
        foreach ($relevant as $pn) {
            $txt = trim($pages[$pn-1] ?? '');
            if ($txt !== '') $joined[] = "=== PAGINA {$pn} ===\n".mb_substr($txt, 0, 15000);
        }
        $corpus = mb_substr(implode("\n\n", $joined), 0, 80000);

        // ---------- (A) EXTRAER CONTACTO CLIENTE + LIMPIAR CORPUS ----------
        $extractClient = function (string $raw): array {
            $emailRe = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';
            $telLbl  = '(?:cel|cel\.?|tel|tel\.?|telefono|teléfono|celular|whats|whatsapp|contacto)';
            $telRe   = '/'.$telLbl.'[^0-9+]*([+]?[\d][\d\s\-\(\)\.]{7,})/iu';
            $attRe   = '/\b(?:att|atn|atención|atte)\.?\s*[:\-]?\s*(.+)$/iu';

            $email = null; $phone = null; $att = null;

            // Buscar email
            if (preg_match($emailRe, $raw, $m)) { $email = trim($m[0]); }
            // Buscar teléfono con label
            if (preg_match($telRe, $raw, $m)) {
                $phone = preg_replace('/\D+/', '', $m[1]);
            } else {
                // fallback: número largo suelto
                if (preg_match('/\+?\d[\d\-\s\(\)\.]{7,}/', $raw, $m)) {
                    $digits = preg_replace('/\D+/', '', $m[0]);
                    if (strlen($digits) >= 8) $phone = $digits;
                }
            }
            // Buscar ATT/ATN
            if (preg_match($attRe, $raw, $m)) {
                $att = trim($m[1]);
            }

            // Si hay "contacto:" con un email, úsalo como nombre si no hay ATT
            if (!$att && preg_match('/contacto\s*[:\-]\s*([^\n\r]+)/iu', $raw, $m)) {
                $candidate = trim($m[1]);
                if (!preg_match($emailRe, $candidate)) $att = $candidate;
            }

            return ['att'=>$att, 'email'=>$email, 'phone'=>$phone];
        };

        $stripContactLines = function (string $raw): string {
            $emailRe = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';
            $lbl = '(?:att|atn|atención|atte|cel|cel\.?|tel|tel\.?|telefono|teléfono|celular|whats|whatsapp|contacto|email|correo|correo empresarial)';
            $lines = preg_split('/\R/u', $raw) ?: [];
            $keep = [];
            foreach ($lines as $L) {
                $Ltrim = trim($L);
                if ($Ltrim === '') continue;
                if (preg_match('/^'.$lbl.'\b/iu', $Ltrim)) continue;
                if (preg_match($emailRe, $Ltrim)) continue;
                if (preg_match('/\+?\d[\d\-\s\(\)\.]{7,}/', $Ltrim)) continue;
                $keep[] = $L;
            }
            return implode("\n", $keep);
        };

        $clientFromText = $extractClient($corpus);
        $corpusNoContact = $stripContactLines($corpus);

        // ---------- (B) Parser local heurístico (filtra contacto y respeta símbolos) ----------
        $localExtract = function (string $raw) {
            $txt   = preg_replace("/[ \t]+/u", " ", $raw);
            $txt   = preg_replace("/\r\n|\r/u", "\n", $txt);
            $lines = array_values(array_filter(array_map('trim', explode("\n", $txt)), fn($l)=>$l!==""));

            // Quitar encabezados comunes de tabla
            $isHdr = fn($L)=>preg_match("/^\s*(PRODUCTOS?|DESCRIPCION|DESCRIPCIÓN|CANTIDAD|CANT\.)\s*$/iu", $L);

            $buf = [];
            $current = "";
            foreach ($lines as $L) {
                if ($isHdr($L)) continue;

                // Si termina en número, es fin de fila
                if (preg_match("/\b\d+(?:[.,]\d+)?\s*$/u", $L)) {
                    $current = $current ? ($current.' '.$L) : $L;
                    $buf[] = trim($current);
                    $current = "";
                } else {
                    $current = $current ? ($current.' '.$L) : $L;
                }
            }
            if ($current !== "") $buf[] = trim($current);

            // Convertir a filas (desc + cantidad)
            $rows = [];
            foreach ($buf as $rowLine) {
                // desc ... cant
                if (preg_match("/^(?P<desc>.+?)\s+(?P<cant>\d+(?:[.,]\d+)?)\s*$/u", $rowLine, $m)) {
                    $desc = trim($m['desc']);
                    // evita que pasen contactos
                    if (preg_match('/@/',$desc) || preg_match('/\+?\d[\d\-\s\(\)\.]{7,}/',$desc)) continue;

                    $rows[] = [
                        'nombre'      => $desc,
                        'descripcion' => $desc,
                        'cantidad'    => (float) str_replace(',', '.', $m['cant']),
                        'unidad'      => 'PIEZA'
                    ];
                // cant ... desc
                } elseif (preg_match("/^(?P<cant>\d+(?:[.,]\d+)?)\s+(?P<desc>.+)$/u", $rowLine, $m)) {
                    $desc = trim($m['desc']);
                    if (preg_match('/@/',$desc) || preg_match('/\+?\d[\d\-\s\(\)\.]{7,}/',$desc)) continue;

                    $rows[] = [
                        'nombre'      => $desc,
                        'descripcion' => $desc,
                        'cantidad'    => (float) str_replace(',', '.', $m['cant']),
                        'unidad'      => 'PIEZA'
                    ];
                } else {
                    $desc = preg_replace('/\s{2,}/u',' ', $rowLine);
                    if ($desc !== '' &&
                        !preg_match('/@/', $desc) &&
                        !preg_match('/\+?\d[\d\-\s\(\)\.]{7,}/', $desc)) {
                        $rows[] = [
                            'nombre'      => $desc,
                            'descripcion' => $desc,
                            'cantidad'    => 1,
                            'unidad'      => 'PIEZA'
                        ];
                    }
                }
            }
            return $rows;
        };

        // Paso 7: extracción IA
        @file_put_contents($logStepFile, '['.date('c')."] Paso 7: extracción IA\n", FILE_APPEND);
        $parsed = [];
        if ($this->timeUp($deadline)) {
            $parsed['items'] = $this->fallbackListExtractor($corpusNoContact);
            $parsed['pages_overview'] = $this->buildPagesOverviewFallback($relevant, $pages);
            @file_put_contents($logStepFile, '['.date('c')."] Paso 7: fallback por tiempo\n", FILE_APPEND);
        } else {
            $extractJson = $this->callOpenAIJson(<<<PR
Devuelve SOLO JSON con:
{
 "cliente_nombre": string|null,
 "cliente_email": string|null,
 "cliente_telefono": string|null,
 "licitacion": {
   "titulo_u_objeto": string|null,
   "procedimiento": string|null,
   "dependencia_o_unidad": string|null,
   "lote_o_partidas": number|null,
   "lugar_entrega": string|null,
   "fechas_clave": {
     "publicacion": string|null,
     "aclaraciones": string|null,
     "presentacion": string|null,
     "fallo": string|null,
     "vigencia_cotizacion_dias": number|null
   },
   "condiciones_pago": string|null,
   "moneda": "MXN"|"USD"|string|null
 },
 "resumen_general": string,
 "pages_overview": [ {"page": number, "bullets": [string, ...]} ],
 "items": [ { "nombre": string, "descripcion": string|null, "cantidad": number|null, "unidad": string|null } ]
}
Reglas: NO tomes precios del PDF. Cantidad=1 si falta. Si es escuela o dependencia mexicana, menciónalo.
TEXTO (sin líneas de contacto):
{$corpusNoContact}
PR);
            $parsed = $this->safeJson($extractJson) ?: [];
            if (empty($parsed['items'])) $parsed['items'] = $this->fallbackListExtractor($corpusNoContact);
            if (empty($parsed['pages_overview']) || !is_array($parsed['pages_overview'])) {
                $parsed['pages_overview'] = $this->buildPagesOverviewFallback($relevant, $pages);
            }
        }
        @file_put_contents($logStepFile, '['.date('c')."] Paso 7 OK: items_ia=".count($parsed['items'] ?? [])."\n", FILE_APPEND);

        // Paso 7B: refuerzo local + fusión (evita contar contacto como producto)
        $localItems = $localExtract($corpusNoContact);
        @file_put_contents($logStepFile, '['.date('c')."] Paso 7B: items_local=".count($localItems)."\n", FILE_APPEND);

        $byKey = [];
        $norm = fn($s)=> preg_replace('~\s+~u',' ', mb_strtolower(trim((string)$s)));
        foreach (($parsed['items'] ?? []) as $it) {
            $k = $norm(($it['descripcion'] ?? '') ?: ($it['nombre'] ?? ''));
            if ($k==='') continue;
            $byKey[$k] = [
                'nombre'      => $it['nombre'] ?? $it['descripcion'] ?? '',
                'descripcion' => $it['descripcion'] ?? $it['nombre'] ?? '',
                'cantidad'    => max(1,(float)($it['cantidad'] ?? 1)),
                'unidad'      => $it['unidad'] ?? 'PIEZA',
            ];
        }
        foreach ($localItems as $it) {
            $k = $norm($it['descripcion']);
            if (!isset($byKey[$k])) {
                $byKey[$k] = $it;
            } else {
                if (($byKey[$k]['cantidad'] ?? 1) < ($it['cantidad'] ?? 1)) {
                    $byKey[$k]['cantidad'] = $it['cantidad'];
                }
            }
        }
        $parsed['items'] = array_values($byKey);
        @file_put_contents($logStepFile, '['.date('c')."] Paso 7B OK: items_fusionados=".count($parsed['items'])."\n", FILE_APPEND);

        // Paso 8: pool productos
        @file_put_contents($logStepFile, '['.date('c')."] Paso 8: getProductPool()\n", FILE_APPEND);
        $pool = $this->getProductPool();

        // Paso 9: mapeo items (usar nombre del catálogo en la descripción final)
        @file_put_contents($logStepFile, '['.date('c')."] Paso 9: mapear items\n", FILE_APPEND);
        $mapped = [];
        $pendientes = [];
        $totalItems = count($parsed['items'] ?? []);
        $procesados = 0;
        $timedOut   = false;

        foreach (($parsed['items'] ?? []) as $row) {
            if ($this->timeUp($deadline)) { $timedOut = true; break; }

            // Combinar para mejorar match
            $row['descripcion'] = trim(($row['descripcion'] ?? '').' '.($row['nombre'] ?? '')) ?: ($row['nombre'] ?? null);
            $alts = $this->topCandidatesForRow($row, 6);
            $qty  = max(1,(float)($row['cantidad'] ?? 1));

            if (!$alts) {
                $pendientes[] = [
                    'raw' => [
                        'nombre' => $row['nombre'] ?? null,
                        'descripcion' => $row['descripcion'] ?? null,
                        'cantidad' => $qty,
                        'unidad' => $row['unidad'] ?? null,
                    ],
                    'candidatos' => [],
                    'debug_score' => null,
                ];
            } else {
                $best = $alts[0];
                if (($best['score'] ?? 0) < 0.08) {
                    $pendientes[] = [
                        'raw' => [
                            'nombre' => $row['nombre'] ?? null,
                            'descripcion' => $row['descripcion'] ?? null,
                            'cantidad' => $qty,
                            'unidad' => $row['unidad'] ?? null,
                        ],
                        'candidatos' => $alts,
                        'debug_score' => $best['score'] ?? 0,
                    ];
                } else {
                    // <<< clave: usar display del catálogo como descripción final >>>
                    $mapped[] = [
                        'producto_id'     => $best['id'],
                        'descripcion'     => $best['display'], // NO el texto del PDF
                        'cantidad'        => $qty,
                        'precio_unitario' => (float)$best['price'],
                        'descuento'       => 0,
                        'iva_porcentaje'  => 16,
                        'alternativas'    => array_slice($alts,0,3),
                    ];
                }
            }
            $procesados++;
        }

        if ($timedOut && $procesados < $totalItems) {
            foreach (array_slice($parsed['items'], $procesados) as $row) {
                $pendientes[] = [
                    'raw' => [
                        'nombre' => $row['nombre'] ?? null,
                        'descripcion' => $row['descripcion'] ?? null,
                        'cantidad' => max(1,(float)($row['cantidad'] ?? 1)),
                        'unidad' => $row['unidad'] ?? null,
                    ],
                    'candidatos' => [],
                    'debug_score' => null,
                    'status' => 'timeout',
                ];
            }
        }

        // Paso 10: cliente (preferimos lo detectado por reglas si está)
        @file_put_contents($logStepFile, '['.date('c')."] Paso 10: cliente\n", FILE_APPEND);
        $clienteNombre = $parsed['cliente_nombre'] ?? ($parsed['licitacion']['dependencia_o_unidad'] ?? ($clientFromText['att'] ?? null));
        $clienteEmail  = $parsed['cliente_email']  ?? ($clientFromText['email'] ?? null);
        $clienteTel    = $parsed['cliente_telefono'] ?? ($clientFromText['phone'] ?? null);

        $issuerGuess = $this->detectIssuerKind($clienteNombre, implode("\n", $pages));
        $clienteId   = $this->createOrGetClientId(
            $clienteNombre,
            $clienteEmail,
            $clienteTel,
            $issuerGuess['kind'] ?? null
        );

        // Paso 11: resumen
        @file_put_contents($logStepFile, '['.date('c')."] Paso 11: resumen\n", FILE_APPEND);
        $summary = $this->buildTenderSummary(
            $parsed['licitacion'] ?? [],
            $parsed['resumen_general'] ?? null,
            $issuerGuess
        );

        $usedSec = microtime(true) - $startedAt;
        @file_put_contents($logStepFile, '['.date('c')."] Paso 12: RESPUESTA OK, seconds={$usedSec}\n", FILE_APPEND);

        return response()->json([
            'ok'                 => true,
            'partial'            => $timedOut,
            'processed_items'    => $procesados,
            'total_items'        => $totalItems,
            'seconds_used'       => round($usedSec, 2),

            'ocr_used'           => $wasOcr,
            'ai_reason'          => $reason ?? null,
            'relevant_pages'     => $relevant,

            'cliente_id'         => $clienteId,
            'cliente_match_name' => $this->displayClient($clienteId),
            'cliente_ai'         => [
                'nombre'   => $clienteNombre,
                'email'    => $clienteEmail,
                'telefono' => $clienteTel,
            ],
            'issuer_kind'        => $issuerGuess['kind'] ?? null,
            'issuer_flags'       => $issuerGuess['flags'] ?? [],

            'summary'            => $summary,
            'pages_overview'     => $parsed['pages_overview'] ?? [],

            'moneda'             => $parsed['licitacion']['moneda'] ?? 'MXN',
            'notas'              => $parsed['resumen_general'] ?? null,
            'validez_dias'       => $parsed['licitacion']['fechas_clave']['vigencia_cotizacion_dias'] ?? 15,
            'envio_sugerido'     => 0,

            'items'              => $mapped,
            'pendientes_ai'      => $pendientes,
        ]);
    } catch (\Throwable $e) {
        Log::error('AI_PARSE_PDF', ['msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()]);
        @file_put_contents($logStepFile, '['.date('c')."] CATCH: ".$e->getMessage()." @ ".$e->getFile().":".$e->getLine()."\n", FILE_APPEND);

        if (str_contains($e->getMessage(),'Smalot\\PdfParser\\Parser')) {
            return response()->json(['ok'=>false,'error'=>'Falta smalot/pdfparser. composer require smalot/pdfparser'],500);
        }
        if (preg_match('~(SSL|cURL|Connection|timed out|resolve host|certificate)~i', $e->getMessage())) {
            return response()->json(['ok'=>false,'error'=>'No fue posible contactar a los servicios externos (OCR/OpenAI). Revisa conectividad/SSL del servidor.'], 422);
        }
        return response()->json(['ok'=>false,'error'=>$e->getMessage()],500);
    }
}



    /** true si se acabó el tiempo suave para devolver algo parcial */
    private function timeUp(float $deadline): bool
    {
        return microtime(true) >= ($deadline - 0.5);
    }

    /**
     * Lee una sola vez un pool de productos (columns ligeras) y lo normaliza.
     * (nota: la variable $pool estática se mantiene aquí por compatibilidad)
     */
    private function getProductPool(): array
    {
        static $pool = null;
        if ($pool !== null) return $pool;

        $limit = (int) env('AI_PRODUCT_POOL_LIMIT', 25000);
        $cols = ['id'];
        foreach (['name','nombre','descripcion','category','categoria','brand','marca','color','material','sku','supplier_sku','tags','unit','unidad','pieces_per_unit','price','precio'] as $c) {
            if (Schema::hasColumn('products',$c)) $cols[]=$c;
        }

        $rows = Product::query()->select(array_unique($cols))->limit($limit)->get();

        $pool = [];
        foreach ($rows as $p) {
            $display = ($p->nombre ?? $p->name ?? ('ID '.$p->id));
            $price   = (float)($p->price ?? $p->precio ?? 0);
            $blob    = implode(' ', array_map(fn($v)=> (string)$v, array_filter([
                $p->name ?? null, $p->nombre ?? null, $p->descripcion ?? null,
                $p->category ?? null, $p->categoria ?? null, $p->brand ?? null, $p->marca ?? null,
                $p->color ?? null, $p->material ?? null, $p->sku ?? null, $p->supplier_sku ?? null, $p->tags ?? null,
                $p->unit ?? null, $p->unidad ?? null
            ])));
            $blobNorm = $this->normalize($blob);
            $pool[] = [
                'id'      => $p->id,
                'display' => $display,
                'price'   => $price,
                'tokens'  => $this->tokens($blobNorm),
                'blob'    => $blobNorm,
            ];
        }

        return $pool;
    }

    /**
     * Crea la cotización completa (cliente ya creado/garantizado por aiParse)
     */
    public function aiCreate(Request $r)
    {
        $BUDGET = $this->aiTimeout(); // 600s por defecto
        @set_time_limit($BUDGET + 30);
        ini_set('max_execution_time', (string)($BUDGET + 30));
        ignore_user_abort(true);

        $r->validate([
            'pdf'   => ['required','file','mimes:pdf','max:20480'],
            'envio' => ['required','numeric','min:0'],
            'pages' => ['nullable','string'],
        ]);

        // 1) aiParse con pages
        $subReq = Request::create('', 'POST', ['pages'=>(string)$r->input('pages','')]);
        $subReq->files->set('pdf', $r->file('pdf'));
        $res = $this->aiParse($subReq);
        $payload = $res->getData(true);
        if (empty($payload['ok'])) {
            return response()->json(['ok'=>false,'error'=>$payload['error'] ?? 'Error al analizar PDF'], 422);
        }

        // 2) Cliente ya existe por aiParse
        $clienteId = (int)($payload['cliente_id'] ?? 0);
        if (!$clienteId || !Client::find($clienteId)) {
            return response()->json(['ok'=>false,'error'=>'No se pudo crear/recuperar el cliente.'], 422);
        }

        // 3) Items finales (precio DB)
        $items = [];
        foreach ($payload['items'] as $row) {
            if (empty($row['producto_id'])) continue;
            $p = Product::find($row['producto_id']);
            if (!$p) continue;

            $precioDb = (float)($p->price ?? $p->precio ?? 0);
            $qty = max(1,(float)($row['cantidad'] ?? 1));

            $items[] = [
                'producto_id'     => $p->id,
                'descripcion'     => $row['descripcion'] ?? ($p->nombre ?? $p->name ?? ''),
                'cantidad'        => $qty,
                'precio_unitario' => $precioDb,
                'descuento'       => 0,
                'iva_porcentaje'  => 16,
            ];
        }
        if (empty($items)) {
            return response()->json(['ok'=>false,'error'=>'No se pudo empatar ningún producto de tu catálogo.'], 422);
        }

        // 4) Crear cotización
        $cot = DB::transaction(function() use ($r, $clienteId, $payload, $items) {
            $cot = new Cotizacion();
            $cot->cliente_id   = $clienteId;
            $cot->notas        = $payload['notas'] ?? null;
            $cot->descuento    = 0;
            $cot->envio        = (float)$r->input('envio', 0);
            $cot->validez_dias = (int)($payload['validez_dias'] ?? 15);
            $cot->setValidez();
            $cot->save();

            $models = collect($items)->map(fn($it)=> new CotizacionProducto($it));
            $cot->items()->saveMany($models);
            $cot->load('items');
            $cot->recalcularTotales();
            $cot->save();

            return $cot;
        });

        return response()->json([
            'ok' => true,
            'cotizacion_id' => $cot->id,
            'folio' => $cot->folio,
            'redirect' => route('cotizaciones.show', $cot),
        ]);
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

        if (empty($tokens)) {
            return $qb;
        }

        foreach ($tokens as $t) {
            $qb->where(function($w) use ($cols, $t) {
                foreach ($cols as $c) {
                    $w->orWhere($c, 'LIKE', "%{$t}%");
                }
            });
        }

        return $qb;
    }

    /** Transforma tokens a consulta boolean FULLTEXT segura: "tok1* tok2* ..." */
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

    /** Devuelve columnas que tienen algún índice FULLTEXT en la tabla dada. */
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

    /* ======================= HELPERS IA ======================= */

    // ---- Timeouts centralizados (lee de .env, con defaults) ----
    private function aiTimeout(): int      { return (int) env('PDF_AI_TIMEOUT', 600); }
    private function ocrTimeout(): int     { return (int) env('PDF_OCR_TIMEOUT', 540); }
    private function openAiTimeout(): int  { return (int) env('OPENAI_TIMEOUT', 300); }
    private function openAiRetries(): int  { return max(1, (int) env('OPENAI_RETRIES', 2)); }

    /**
     * Intenta: texto directo -> (Hostinger: SIN binarios) -> OCR por API (opcional)
     * Devuelve: [array $pagesText, bool $wasOcr]
     */
    private function extractPdfPagesText(string $path): array
    {
        if (!is_file($path)) throw new \RuntimeException("PDF no encontrado: $path");
        if (!class_exists(\Smalot\PdfParser\Parser::class)) throw new \RuntimeException("Falta smalot/pdfparser");

        // 1) Intento directo con Smalot (sólo PDFs con texto, no imágenes)
        try {
            $parser = new PdfParser();
            $pdf    = $parser->parseFile($path);
            $pages  = $pdf->getPages();
            $texts  = array_map(fn($p)=>$p->getText() ?? '', $pages);

            $hasText = array_reduce($texts, fn($c,$t)=> $c || (trim($t) !== ''), false);
            if ($hasText) {
                return [$texts, false]; // sin OCR
            }
        } catch (\Throwable $e) {
            \Log::info('Smalot parse falló, posible PDF escaneado: '.$e->getMessage());
        }

        // 2) Hostinger compartido: no ejecutamos binarios. Usar OCR por API si hay clave.
        $ocrKey = env('OCR_SPACE_API_KEY');
        if (!$ocrKey || trim($ocrKey) === '') {
            // Fallback: usa la clave que nos diste si no hay .env
            $ocrKey = 'K82192623888957';
        }

        if ($ocrKey) {
            try {
                [$textFromApi, $wasOk] = $this->ocrSpace($path, $ocrKey);
                if ($wasOk && trim($textFromApi) !== '') {
                    // Lo devolvemos como una sola "página" de texto
                    return [[ $textFromApi ], true];
                }
            } catch (\Throwable $e) {
                \Log::error('OCR.space error', ['msg'=>$e->getMessage()]);
            }
        }

        // 3) Sin texto y sin OCR: devolvemos vacío para que el caller responda 422
        return [[], false];
    }

    // === NUEVO: OCR vía OCR.space (sin binarios locales) ===
  private function ocrSpace(string $pdfPath, string $apiKey): array
{
    $stream = fopen($pdfPath, 'rb');
    if ($stream === false) {
        throw new \RuntimeException("No se pudo abrir el PDF para OCR: $pdfPath");
    }

    try {
        $response = Http::timeout(max(60, (int) env('PDF_OCR_TIMEOUT', 120)))
            ->attach('file', $stream, basename($pdfPath))   // streaming
            ->asMultipart()
            ->post('https://api.ocr.space/parse/image', [
                'apikey'             => $apiKey,
                'language'           => 'spa',
                'isOverlayRequired'  => 'false',
                'OCREngine'          => '2',
            ]);

        // Log breve de la respuesta
        @file_put_contents(
            storage_path('logs/ocr_space.log'),
            '['.date('c')."] status={$response->status()} len=".strlen($response->body())." body=".substr($response->body(),0,600)."\n",
            FILE_APPEND
        );

        if ($response->failed()) {
            throw new \RuntimeException('OCR HTTP '.$response->status().': '.substr($response->body(),0,400));
        }

        $obj = $response->json();
        if (!is_array($obj) || empty($obj['ParsedResults'][0]['ParsedText'])) {
            return ['', false];
        }
        return [ (string) $obj['ParsedResults'][0]['ParsedText'], true ];
    } finally {
        fclose($stream);
    }
}
    // === NUEVO: helpers para bloquear procesos externos en hosting compartido ===
    private function canRunProcesses(): bool
    {
        $disabled = strtolower((string) ini_get('disable_functions'));
        $bad = ['proc_open','proc_get_status','exec','shell_exec','system','passthru','popen'];
        foreach ($bad as $fn) {
            if (stripos($disabled, $fn) !== false) return false;
        }
        if (!filter_var(env('PDF_ALLOW_PROCESSES', false), FILTER_VALIDATE_BOOL)) return false;
        return function_exists('proc_open');
    }

    // === REEMPLAZO: binaryExists sin 'which/where' si no se permite ejecutar procesos
    private function binaryExists(string $cmd): bool
    {
        if (!$this->canRunProcesses()) return false;
        try {
            $proc = new Process(['which', $cmd]);
            $proc->setTimeout(3);
            $proc->run();
            if ($proc->isSuccessful() && trim($proc->getOutput()) !== '') return true;
        } catch (\Throwable $e) {}

        try {
            $proc = new Process(['where', $cmd]);
            $proc->setTimeout(3);
            $proc->run();
            if ($proc->isSuccessful() && trim($proc->getOutput()) !== '') return true;
        } catch (\Throwable $e) {}

        return false;
    }

    /**
     * Llama a OpenAI con reintentos y timeout alto.
     */
    private function callOpenAIJson(string $prompt, ?int $timeout = null): ?string
    {
        $key = env('OPENAI_API_KEY'); if(!$key) return null;

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role'=>'system','content'=>'Responde estrictamente con JSON válido.'],
                ['role'=>'user','content'=>$prompt],
            ],
            'temperature'=>0.1,
            'response_format' => ['type'=>'json_object'],
        ];

        $tries = $this->openAiRetries();
        $timeout = $timeout ?? $this->openAiTimeout();

        $lastBody = null; $lastCode = null; $lastErr = null;

        for ($i=0; $i<$tries; $i++) {
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$key,
                    'Accept-Encoding: gzip',
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 20,
            ]);
            $res = curl_exec($ch);
            if ($res === false){
                $lastErr = curl_error($ch);
                curl_close($ch);
                usleep((300 + 500*$i) * 1000);
                continue;
            }
            $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            $lastBody = $res; $lastCode = $code;

            if ($code < 300) {
                $obj = json_decode($res,true);
                return $obj['choices'][0]['message']['content'] ?? null;
            }

            if (in_array($code, [429,500,502,503,504], true)) {
                usleep((600 + 800*$i) * 1000);
                continue;
            } else {
                Log::error('OpenAI HTTP', ['status'=>$code,'body'=>$res]);
                break;
            }
        }

        Log::error('OpenAI CURL', ['err'=>$lastErr, 'status'=>$lastCode, 'body'=>$lastBody]);
        return null;
    }

    private function safeJson(?string $raw): array
    {
        if (!$raw) return [];
        $raw = trim($raw);
        $raw = preg_replace('~^```(?:json)?\s*|\s*```$~m','', $raw);
        $j = json_decode($raw,true);
        return is_array($j) ? $j : [];
    }

    private function parsePagesParam(string $s, int $max): array
    {
        $s = trim($s); if ($s==='') return [];
        $out=[];
        foreach (explode(',', $s) as $part) {
            $part = trim($part);
            if (preg_match('~^(\d+)-(\d+)$~', $part, $m)) {
                $a = max(1, (int)$m[1]); $b = min($max, (int)$m[2]);
                if ($a <= $b) $out = array_merge($out, range($a,$b));
            } elseif (ctype_digit($part)) {
                $n = (int)$part; if ($n>=1 && $n<=$max) $out[]=$n;
            }
        }
        return array_values(array_unique($out));
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

    private function jaccard(array $a, array $b): float
    {
        if (!$a || !$b) return 0.0;
        $ia = array_intersect($a, $b);
        $ua = array_unique(array_merge($a,$b));
        return count($ia)/max(1,count($ua));
    }

    private function displayClient(int $id): ?string
    {
        $c = Client::find($id);
        if (!$c) return null;
        foreach (['name','nombre','razon_social'] as $k) if (!empty($c->{$k})) return $c->{$k};
        return "ID {$c->id}";
    }

    private function matchClientId(?string $nombre, ?string $email, ?string $tel): ?int
    {
        $want = ['id','name','nombre','razon_social','email','telefono','phone'];
        $cols = ['id'];
        foreach ($want as $c) if ($c!=='id' && Schema::hasColumn('clients',$c)) $cols[]=$c;
        $clients = Client::query()->select(array_unique($cols))->get();

        $normName  = $this->normalize($nombre ?? '');
        $normEmail = $this->normalize($email ?? '');
        $normTel   = preg_replace('/\D+/', '', (string)$tel);

        $bestId = null; $best=0;
        foreach ($clients as $c) {
            $score = 0;
            $candName = $this->normalize(($c->name ?? null) ?? ($c->nombre ?? null) ?? ($c->razon_social ?? null) ?? '');
            if ($normName && $candName){ similar_text($normName,$candName,$pct); $score += $pct; }
            if (Schema::hasColumn('clients','email')) {
                $candEmail = $this->normalize($c->email ?? '');
                if ($normEmail && $candEmail && $normEmail === $candEmail) $score += 40;
            }
            $candTel = '';
            if (Schema::hasColumn('clients','telefono')) $candTel = preg_replace('/\D+/', '', (string)$c->telefono);
            if (!$candTel && Schema::hasColumn('clients','phone')) $candTel = preg_replace('/\D+/', '', (string)$c->phone);
            if ($normTel && $candTel && str_ends_with($candTel,$normTel)) $score += 25;

            if ($score > $best){ $best=$score; $bestId=$c->id; }
        }
        return $best >= 55 ? $bestId : null;
    }

    private function createOrGetClientId(?string $nombre, ?string $email, ?string $tel, ?string $issuerKind): int
    {
        if ($id = $this->matchClientId($nombre, $email, $tel)) return $id;

        if ($email) {
            $exists = Client::where('email', $email)->first();
            if ($exists) return $exists->id;
        }

        $name = $nombre ?: 'Cliente de PDF';
        $mail = $email ?: $this->genFakeEmail($name);
        $tipo = ($issuerKind === 'dependencia_gobierno_mx') ? 'gobierno' : 'empresa';

        $client = Client::create([
            'nombre'       => $name,
            'email'        => $mail,
            'tipo_cliente' => $tipo,
            'telefono'     => $tel,
            'estatus'      => true,
        ]);

        return $client->id;
    }

    private function genFakeEmail(string $name): string
    {
        $base = Str::slug(mb_substr($name, 0, 40)) ?: 'cliente';
        do {
            $candidate = $base.'-'.substr(md5(uniqid('', true)), 0, 6).'-'.time().'@example.com';
        } while (Client::where('email', $candidate)->exists());
        return $candidate;
    }

    /** Devuelve top N candidatos para una fila: [{id,display,price,score}] */
    private function topCandidatesForRow(array $row, int $limit=3): array
    {
        $queryText = trim(($row['nombre'] ?? '').' '.($row['descripcion'] ?? ''));
        if ($queryText==='') return [];

        $qTokens = $this->tokens($queryText);

        $cols = ['id'];
        foreach (['name','nombre','descripcion','category','categoria','brand','marca','color','material','sku','supplier_sku','tags','unit','unidad','pieces_per_unit','price','precio'] as $c) {
            if (Schema::hasColumn('products',$c)) $cols[]=$c;
        }

        $q = $this->productSearchQuery($queryText)->select(array_unique($cols));
        $cands = $q->take(2000)->get();

        if ($cands->isEmpty()) {
            $cands = Product::query()->select(array_unique($cols))->take(5000)->get();
        }
        if ($cands->isEmpty()) return [];

        $unitPdf = $this->normalize((string)($row['unidad'] ?? ''));
        $scored = [];
        foreach ($cands as $p) {
            $bag=[];
            foreach (['name','nombre','descripcion','category','categoria','brand','marca','color','material','sku','supplier_sku','tags'] as $c) {
                if (!empty($p->{$c})) $bag[] = (string)$p->{$c};
            }
            $pTokens = $this->tokens(implode(' ', $bag));
            $score = $this->jaccard($qTokens,$pTokens);

            foreach (['unit','unidad'] as $uCol) {
                if ($unitPdf && Schema::hasColumn('products',$uCol) && !empty($p->{$uCol})) {
                    if (str_starts_with($this->normalize($p->{$uCol}), $unitPdf)) $score += 0.05;
                }
            }

            $price = (float)($p->price ?? $p->precio ?? 0);
            $display = ($p->nombre ?? $p->name ?? 'ID '.$p->id);

            $scored[] = ['id'=>$p->id, 'display'=>$display, 'price'=>$price, 'score'=>$score];
        }

        usort($scored, function($a,$b){
            if (abs($a['score'] - $b['score']) > 0.0001) return ($a['score'] < $b['score']) ? 1 : -1;
            return $a['price'] <=> $b['price'];
        });

        if (empty($scored)) {
            $fallback = Product::query()->select(['id', DB::raw("COALESCE(nombre,name,CONCAT('ID ',id)) as display"), DB::raw("COALESCE(price,precio,0) as price")])->take(max(3,$limit))->get();
            return $fallback->map(fn($p)=>['id'=>$p->id,'display'=>$p->display,'price'=>(float)$p->price,'score'=>0.0])->all();
        }

        return array_slice($scored, 0, max(1,$limit));
    }

    /** Fallback extractor cuando la IA no devuelve items (regex con delimitador seguro) */
    private function fallbackListExtractor(string $corpus): array
    {
        $lines = preg_split('~\R~u',$corpus) ?: [];
        $items=[];
        $units='(PZA|PZAS?|PIEZA|PIEZAS|CAJA(?:/\d+\s*PZ)?|BOLSA|MTS?|CM|M|JGO|JUEGO|PAQ|PAQUETE|ROLLO|BLISTER|KIT)';
        $rx1='~^\s*\d{1,3}\s+[A-Z0-9\-]{3,}\s+(.+?)\s+'.$units.'\b~iu';
        $rx2='~^\s*\d{1,3}\s+(.+?)\s+'.$units.'\b~iu';
        $rx3='~^\s*(?:-?\s*)?([A-ZÁÉÍÓÚÜÑ0-9][^,]{6,}?)\s+'.$units.'\b~iu';

        foreach ($lines as $ln){
            $ln = trim(preg_replace('~\s+~u',' ', $ln));
            if ($ln==='') continue;
            $nombre=null; $unidad=null;
            if (preg_match($rx1,$ln,$m)) { $nombre=trim($m[1]); $unidad=strtoupper(trim($m[2])); }
            elseif (preg_match($rx2,$ln,$m)) { $nombre=trim($m[1]); $unidad=strtoupper(trim($m[2])); }
            elseif (preg_match($rx3,$ln,$m)) { $nombre=trim($m[1]); $unidad=strtoupper(trim($m[2])); }

            if ($nombre){
                $items[] = [
                    'nombre'=>$nombre,
                    'descripcion'=>null,
                    'cantidad'=>1,
                    'unidad'=>$unidad ?? 'PZA',
                ];
            }
        }
        $seen=[]; $out=[];
        foreach ($items as $it){ $k=mb_strtolower($it['nombre']); if(isset($seen[$k])) continue; $seen[$k]=1; $out[]=$it; }
        return array_slice($out, 0, 400);
    }

    /** Resumen uniforme */
    private function buildTenderSummary(array $lic, ?string $freeSummary, array $issuerGuess): array
    {
        $fc = $lic['fechas_clave'] ?? [];
        $out = [
            'titulo_u_objeto' => $lic['titulo_u_objeto'] ?? null,
            'procedimiento'   => $lic['procedimiento']   ?? null,
            'dependencia'     => $lic['dependencia_o_unidad'] ?? null,
            'lote_o_partidas' => $lic['lote_o_partidas'] ?? null,
            'lugar_entrega'   => $lic['lugar_entrega']   ?? null,
            'condiciones_pago'=> $lic['condiciones_pago']?? null,
            'moneda'          => $lic['moneda']          ?? null,
            'fechas_clave'    => [
                'publicacion'  => $fc['publicacion']  ?? null,
                'aclaraciones' => $fc['aclaraciones'] ?? null,
                'presentacion' => $fc['presentacion'] ?? null,
                'fallo'        => $fc['fallo']        ?? null,
                'vigencia_cotizacion_dias' => $fc['vigencia_cotizacion_dias'] ?? null,
            ],
            'issuer_detected' => [
                'kind'  => $issuerGuess['kind']  ?? null,
                'flags' => $issuerGuess['flags'] ?? [],
            ],
            'resumen_texto'   => $freeSummary,
        ];
        if (!$out['dependencia'] && !empty($issuerGuess['name'])) {
            $out['dependencia'] = $issuerGuess['name'];
        }
        return $out;
    }

    /** Heurística tipo emisor */
    private function detectIssuerKind(?string $nameFromAi, string $fullText): array
    {
        $name = $nameFromAi ? trim($nameFromAi) : null;
        $txt  = mb_strtolower($fullText);

        $flags = [];
        $kind  = 'empresa';

        $govHints = ['ayuntamiento','secretaria','secretaría','dirección','coordinación','sistema dif','imss','issste','conalep','conacyt','pemex','cfe','universidad','uach','uanl','ipn','unam','h. ayuntamiento','gobierno'];
        foreach ($govHints as $h) { if (str_contains($txt, $h)) { $flags[]='gov_hint:'.$h; } }
        if (preg_match('~\b(gob\.mx|\.gob\.mx)\b~u', $txt)) $flags[]='domain_gob_mx';

        $eduHints = ['escuela','secundaria','primaria','bachillerato','preparatoria','universidad','instituto','tecnológico','jardín de niños','kínder','colegio'];
        foreach ($eduHints as $h) { if (str_contains($txt, $h)) { $flags[]='edu_hint:'.$h; } }
        if (preg_match('~\b(edu\.mx|\.edu\.mx)\b~u', $txt)) $flags[]='domain_edu_mx';

        if (array_filter($flags, fn($f)=>str_starts_with($f,'edu_hint') || $f==='domain_edu_mx')) {
            $kind = 'escuela';
        } elseif (array_filter($flags, fn($f)=>str_starts_with($f,'gov_hint') || $f==='domain_gob_mx')) {
            $kind = 'dependencia_gobierno_mx';
        }

        if (!$name) {
            if (preg_match('~(?:ayuntamiento|secretar[íi]a|universidad|colegio|instituto|tecnol[óo]gico)[^,\n]{0,120}~iu', $fullText, $m)) {
                $name = trim($m[0]);
            }
        }

        return ['kind'=>$kind, 'flags'=>$flags, 'name'=>$name];
    }

    /** Bullets por página fallback */
    private function buildPagesOverviewFallback(array $relevant, array $pages): array
    {
        $out = [];
        $kw = ['entrega','pago','vigencia','presentación','presentacion','fallo','aclaraciones','lugar','domicilio','contacto','correo','tel','cantidad','unidad','partida','lote','garant','plazo','envío','envio','orden','requisición','requisicion'];
        foreach ($relevant as $pn) {
            $txt = $pages[$pn-1] ?? '';
            $txt = preg_replace('~\s+~u',' ', $txt);
            $chunks = preg_split('~(?<=[\.\:\;\n])\s+~u', $txt) ?: [];
            $bul = [];
            foreach ($chunks as $c) {
                $low = mb_strtolower($c);
                foreach ($kw as $k) {
                    if (str_contains($low, $k)) { $bul[] = trim($c); break; }
                }
                if (count($bul)>=6) break;
            }
            if (!$bul) {
                $bul = array_values(array_filter(array_map('trim', array_slice($chunks,0,3))));
            }
            if ($bul) $out[] = ['page'=>$pn, 'bullets'=>$bul];
        }
        return $out;
    }
  public function convertirAVenta(Request $request, Cotizacion $cotizacion)
    {
        // No convertir si ya está convertida/cancelada
        if (in_array((string) $cotizacion->estado, ['converted', 'cancelled'], true)) {
            return back()->withErrors(
                'Esta cotización no puede convertirse (estado actual: ' . ($cotizacion->estado ?? '—') . ').'
            );
        }

        // Debe tener items
        $cotizacion->loadMissing('items.producto');
        if ($cotizacion->items->isEmpty()) {
            return back()->withErrors('La cotización no tiene conceptos para convertir.');
        }

        try {
            $venta = DB::transaction(function () use ($cotizacion) {
                // ===== Crear venta base
                $venta = new Venta();
                $venta->cliente_id    = $cotizacion->cliente_id;
                $venta->cotizacion_id = $cotizacion->id;     // requiere la FK en ventas
                $venta->moneda        = $cotizacion->moneda ?: 'MXN';
                $venta->notas         = $cotizacion->notas ?: null;
                $venta->subtotal      = 0;
                $venta->descuento     = (float) ($cotizacion->descuento ?? 0);
                $venta->envio         = (float) ($cotizacion->envio ?? 0);
                $venta->iva           = 0;
                $venta->total         = 0;
                $venta->estado        = 'emitida';

                if (array_key_exists('financiamiento_config', $cotizacion->getAttributes())) {
                    $venta->financiamiento_config = $cotizacion->financiamiento_config;
                }

                $venta->save();

                // ===== Mapear items de cotización -> venta
                $rows = [];
                foreach ($cotizacion->items as $it) {
                    $cantidad  = max(0.01, (float) ($it->cantidad ?? 1));
                    $pu        = round((float) ($it->precio_unitario ?? $it->precio ?? 0), 2);
                    $desc      = round((float) ($it->descuento ?? 0), 2);          // monto
                    $ivaPct    = round((float) ($it->iva_porcentaje ?? 0), 2);

                    $base      = max(0, round($cantidad * $pu - $desc, 2));
                    $ivaMonto  = round($base * ($ivaPct / 100), 2);
                    $importe   = round($base + $ivaMonto, 2);

                    $rows[] = [
                        'venta_id'        => $venta->id,
                        'producto_id'     => $it->producto_id,
                        // En Product tus campos son "name" (no "nombre"), usamos fallback al name.
                        'descripcion'     => $it->descripcion ?? optional($it->producto)->name ?? 'Producto',
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $pu,
                        'descuento'       => $desc,
                        'iva_porcentaje'  => $ivaPct,
                        'importe'         => $importe,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }

                if (!empty($rows)) {
                    VentaProducto::insert($rows);
                }

                // ===== Totales
                $venta->load('items');
                if (method_exists($venta, 'recalcularTotales')) {
                    $venta->recalcularTotales();
                }
                $venta->save();

                // ===== Marcar cotización como convertida
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

        // === Timbrado inmediato si ?facturar=1 o services.facturaapi.auto=true
        $mustInvoice = $request->boolean('facturar') || (bool) config('services.facturaapi.auto', false);

        if ($mustInvoice) {
            try {
                $svc = app(FacturaApiService::class);
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
}
