<?php

namespace App\Http\Controllers;

use App\Models\{Cotizacion, CotizacionProducto, CotizacionPlazo, Client, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDF; // barryvdh/laravel-dompdf

// IA + PDF
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\Process\Process;

class CotizacionController extends Controller
{
    public function index()
    {
        $q = Cotizacion::with('cliente')->latest()->paginate(12);
        return view('cotizaciones.index', compact('q'));
    }

    public function create()
    {
        // ----- CLIENTES: display dinámico -----
        $clientNameCandidates = ['name','nombre','razon_social'];
        $clientCols = array_values(array_filter($clientNameCandidates, fn($c) => Schema::hasColumn('clients', $c)));
        $clientDisplayExpr = "CONCAT('ID ', `id`)";
        if ($clientCols) {
            $clientDisplayExpr = 'COALESCE(' . implode(',', array_map(fn($c) => "`$c`", $clientCols)) . ", CONCAT('ID ', `id`))";
        }

        $clientesSelect = Client::query()
            ->select(['id', DB::raw("$clientDisplayExpr AS display")])
            ->orderByRaw($clientDisplayExpr)
            ->get();

        // Tarjeta lateral: todos los campos
        $clientesInfo = Client::query()->get();

        // ----- PRODUCTOS: nombre, precio e info extra -----
        $prodNameCandidates = ['nombre','name','descripcion','titulo','title'];
        $prodNameCols = array_values(array_filter($prodNameCandidates, fn($c) => Schema::hasColumn('products', $c)));
        $prodNameExpr = "CONCAT('ID ', `id`)";
        if ($prodNameCols) {
            $prodNameExpr = 'COALESCE(' . implode(',', array_map(fn($c) => "`$c`", $prodNameCols)) . ", CONCAT('ID ', `id`))";
        }

        $priceCandidates = ['price','precio','precio_unitario'];
        $priceCols = array_values(array_filter($priceCandidates, fn($c) => Schema::hasColumn('products', $c)));
        $priceExpr = $priceCols
            ? 'COALESCE(' . implode(',', array_map(fn($c) => "`$c`", $priceCols)) . ', 0)'
            : '0';

        // Extras opcionales
        $brandExpr    = $this->coalesceExpr('products', ['brand','marca'], "NULL");
        $categoryExpr = $this->coalesceExpr('products', ['category','categoria'], "NULL");
        $colorExpr    = $this->coalesceExpr('products', ['color','colour'], "NULL");
        $matExpr      = $this->coalesceExpr('products', ['material'], "NULL");
        $imgExpr      = $this->coalesceExpr('products', ['image','imagen','foto','thumb','thumbnail'], "NULL");
        $stockExpr    = $this->coalesceExpr('products', ['stock','existencia'], "NULL");

        $productos = Product::query()
            ->select([
                'id',
                DB::raw("$prodNameExpr  AS display"),
                DB::raw("$priceExpr     AS price"),
                DB::raw("$brandExpr     AS brand"),
                DB::raw("$categoryExpr  AS category"),
                DB::raw("$colorExpr     AS color"),
                DB::raw("$matExpr       AS material"),
                DB::raw("$imgExpr       AS image"),
                DB::raw("$stockExpr     AS stock"),
            ])
            ->orderByRaw($prodNameExpr)
            ->get();

        return view('cotizaciones.create', [
            'clientesSelect' => $clientesSelect,
            'clientesInfo'   => $clientesInfo,
            'productos'      => $productos,
        ]);
    }

    /**
     * Helper para construir un COALESCE dinámico si existen columnas.
     */
    private function coalesceExpr(string $table, array $candidates, string $fallbackExpr = "NULL"): string
    {
        $cols = array_values(array_filter($candidates, fn($c) => Schema::hasColumn($table, $c)));
        return $cols && count($cols) > 0
            ? 'COALESCE(' . implode(',', array_map(fn($c) => "`$c`", $cols)) . ", $fallbackExpr)"
            : $fallbackExpr;
    }

    public function store(Request $r)
    {
        // Decodificar items cuando llegan como JSON (desde el form)
        $raw = $r->get('items');
        if (is_string($raw)) { $r->merge(['items' => json_decode($raw, true) ?? []]); }

        $data = $r->validate([
            'cliente_id' => ['required','exists:clients,id'],
            'notas' => ['nullable','string'],
            'descuento' => ['nullable','numeric'],
            'envio' => ['nullable','numeric'],
            'validez_dias' => ['nullable','integer','min:0','max:365'],

            'items' => ['required','array','min:1'],
            'items.*.producto_id' => ['required','exists:products,id'],
            'items.*.descripcion' => ['nullable','string'],
            'items.*.cantidad' => ['required','numeric','min:0.01'],
            'items.*.precio_unitario' => ['required','numeric','min:0'],
            'items.*.descuento' => ['nullable','numeric','min:0'],
            'items.*.iva_porcentaje' => ['nullable','numeric','min:0','max:100'],

            // Financiamiento (opcional)
            'financiamiento.aplicar' => ['nullable','boolean'],
            'financiamiento.numero_plazos' => ['nullable','integer','min:1','max:60'],
            'financiamiento.enganche' => ['nullable','numeric','min:0'],
            'financiamiento.tasa_anual' => ['nullable','numeric','min:0','max:200'],
            'financiamiento.primer_vencimiento' => ['nullable','date'],
        ]);

        $cotizacion = DB::transaction(function() use ($data) {
            $cot = new Cotizacion();
            $cot->cliente_id = $data['cliente_id'];
            $cot->notas = $data['notas'] ?? null;
            $cot->descuento = $data['descuento'] ?? 0;
            $cot->envio = $data['envio'] ?? 0;
            $cot->validez_dias = (int) ($data['validez_dias'] ?? 15);
            $cot->setValidez();
            $cot->save();

            // Items
            $items = collect($data['items'])->map(function($it){
                $pu   = (float)$it['precio_unitario'];
                $cant = (float)$it['cantidad'];
                $desc = (float)($it['descuento'] ?? 0);
                $ivaP = (float)($it['iva_porcentaje'] ?? 16);
                $base = max(0, ($pu*$cant) - $desc);
                $iva  = round($base * ($ivaP/100), 2);

                return new CotizacionProducto([
                    'producto_id'     => $it['producto_id'],
                    'descripcion'     => $it['descripcion'] ?? null,
                    'cantidad'        => $cant,
                    'precio_unitario' => $pu,
                    'descuento'       => $desc,
                    'iva_porcentaje'  => $ivaP,
                    'importe'         => $base + $iva,
                ]);
            });

            $cot->items()->saveMany($items);
            $cot->load('items');
            $cot->recalcularTotales();
            $cot->save();

            // Financiamiento opcional
            if (!empty($data['financiamiento']['aplicar'])) {
                $cfg = [
                    'numero_plazos'      => (int)($data['financiamiento']['numero_plazos'] ?? 0),
                    'enganche'           => (float)($data['financiamiento']['enganche'] ?? 0),
                    'tasa_anual'         => (float)($data['financiamiento']['tasa_anual'] ?? 0),
                    'primer_vencimiento' => $data['financiamiento']['primer_vencimiento'] ?? null,
                ];
                $cot->financiamiento_config = $cfg;
                $cot->save();

                $total = max(0, $cot->total - $cfg['enganche']);
                $n     = max(1, (int)$cfg['numero_plazos']);
                $monto = round($total / $n, 2);

                $fechaBase = $cfg['primer_vencimiento'] ? \Carbon\Carbon::parse($cfg['primer_vencimiento']) : now()->addMonth();
                $plazos = [];
                for ($i=1; $i<=$n; $i++) {
                    $plazos[] = new CotizacionPlazo([
                        'numero'   => $i,
                        'vence_el' => $fechaBase->copy()->addMonths($i-1)->toDateString(),
                        'monto'    => $monto,
                        'pagado'   => false,
                    ]);
                }
                $cot->plazos()->saveMany($plazos);
            }

            return $cot;
        });

        return redirect()->route('cotizaciones.show', $cotizacion)->with('ok','Cotización creada.');
    }

    // Mostrar por ID numérico (folio)
    public function show($id)
    {
        $cotizacion = Cotizacion::with('cliente','items.producto','plazos')->find($id);
        if (!$cotizacion) {
            return redirect()->route('cotizaciones.index')->with('error', 'La cotización '.$id.' no existe.');
        }
        return view('cotizaciones.show', compact('cotizacion'));
    }

    public function edit(Cotizacion $cotizacion)
    {
        abort_unless(in_array($cotizacion->estado, ['borrador','enviada']), 403);

        // CLIENTES
        $clientNameCandidates = ['name','nombre','razon_social'];
        $clientCols = array_values(array_filter($clientNameCandidates, fn($c) => Schema::hasColumn('clients', $c)));
        $clientDisplayExpr = "CONCAT('ID ', `id`)";
        if ($clientCols) {
            $clientDisplayExpr = 'COALESCE(' . implode(',', array_map(fn($c) => "`$c`", $clientCols)) . ", CONCAT('ID ', `id`))";
        }
        $clientesSelect = Client::query()
            ->select(['id', DB::raw("$clientDisplayExpr AS display")])
            ->orderByRaw($clientDisplayExpr)
            ->get();
        $clientesInfo = Client::query()->get();

        // PRODUCTOS
        $prodNameCandidates = ['nombre','name','descripcion'];
        $prodNameCols = array_values(array_filter($prodNameCandidates, fn($c) => Schema::hasColumn('products', $c)));
        $prodNameExpr = "CONCAT('ID ', `id`)";
        if ($prodNameCols) {
            $prodNameExpr = 'COALESCE(' . implode(',', array_map(fn($c) => "`$c`", $prodNameCols)) . ", CONCAT('ID ', `id`))";
        }

        $priceCandidates = ['price','precio','precio_unitario'];
        $priceCols = array_values(array_filter($priceCandidates, fn($c) => Schema::hasColumn('products', $c)));
        $priceExpr = $priceCols
            ? 'COALESCE(' . implode(',', array_map(fn($c) => "`$c`", $priceCols)) . ', 0)'
            : '0';

        $productos = Product::query()
            ->select(['id', DB::raw("$prodNameExpr AS display"), DB::raw("$priceExpr AS price")])
            ->orderByRaw($prodNameExpr)
            ->get();

        $cotizacion->load('items','plazos');

        return view('cotizaciones.edit', [
            'cotizacion'     => $cotizacion,
            'clientesSelect' => $clientesSelect,
            'clientesInfo'   => $clientesInfo,
            'productos'      => $productos,
        ]);
    }

    public function update(Request $r, Cotizacion $cotizacion)
    {
        abort_unless(in_array($cotizacion->estado, ['borrador','enviada']), 403);

        $raw = $r->get('items');
        if (is_string($raw)) { $r->merge(['items' => json_decode($raw, true) ?? []]); }

        $data = $r->validate([
            'cliente_id' => ['required','exists:clients,id'],
            'notas' => ['nullable','string'],
            'descuento' => ['nullable','numeric'],
            'envio' => ['nullable','numeric'],
            'validez_dias' => ['nullable','integer','min:0','max:365'],

            'items' => ['required','array','min:1'],
            'items.*.producto_id' => ['required','exists:products,id'],
            'items.*.descripcion' => ['nullable','string'],
            'items.*.cantidad' => ['required','numeric','min:0.01'],
            'items.*.precio_unitario' => ['required','numeric','min:0'],
            'items.*.descuento' => ['nullable','numeric','min:0'],
            'items.*.iva_porcentaje' => ['nullable','numeric','min:0','max:100'],
        ]);

        DB::transaction(function() use ($cotizacion, $data) {
            $cotizacion->update([
                'cliente_id' => $data['cliente_id'],
                'notas' => $data['notas'] ?? null,
                'descuento' => $data['descuento'] ?? 0,
                'envio' => $data['envio'] ?? 0,
                'validez_dias' => (int) ($data['validez_dias'] ?? 15),
            ]);
            $cotizacion->setValidez();
            $cotizacion->save();

            $cotizacion->items()->delete();

            $items = collect($data['items'])->map(function($it){
                $pu   = (float)$it['precio_unitario'];
                $cant = (float)$it['cantidad'];
                $desc = (float)($it['descuento'] ?? 0);
                $ivaP = (float)($it['iva_porcentaje'] ?? 16);
                $base = max(0, ($pu*$cant) - $desc);
                $iva  = round($base * ($ivaP/100), 2);

                return new CotizacionProducto([
                    'producto_id'     => $it['producto_id'],
                    'descripcion'     => $it['descripcion'] ?? null,
                    'cantidad'        => $cant,
                    'precio_unitario' => $pu,
                    'descuento'       => $desc,
                    'iva_porcentaje'  => $ivaP,
                    'importe'         => $base + $iva,
                ]);
            });

            $cotizacion->items()->saveMany($items);
            $cotizacion->load('items');
            $cotizacion->recalcularTotales();
            $cotizacion->save();
        });

        return redirect()->route('cotizaciones.show', $cotizacion)->with('ok','Cotización actualizada.');
    }

    public function destroy(Cotizacion $cotizacion)
    {
        abort_unless(in_array($cotizacion->estado, ['borrador','rechazada']), 403);
        $cotizacion->delete();
        return redirect()->route('cotizaciones.index')->with('ok','Cotización eliminada.');
    }

    public function aprobar(Cotizacion $cotizacion)
    {
        abort_unless(in_array($cotizacion->estado, ['enviada','borrador']), 403);
        $cotizacion->estado = 'aprobada';
        $cotizacion->save();
        return back()->with('ok','Cotización aprobada. Ya puedes convertirla en venta.');
    }

    public function rechazar(Cotizacion $cotizacion)
    {
        abort_unless(in_array($cotizacion->estado, ['enviada','borrador']), 403);
        $cotizacion->estado = 'rechazada';
        $cotizacion->save();
        return back()->with('ok','Cotización rechazada.');
    }

    // PDF de la cotización
    public function pdf(Cotizacion $cotizacion)
    {
        $cotizacion->load('cliente','items.producto','plazos');
        $pdf = PDF::loadView('cotizaciones.pdf', compact('cotizacion'))->setPaper('letter');
        $filename = 'COT-'.$cotizacion->folio.'.pdf';
        return $pdf->stream($filename);
    }

    // Convertir a venta
    public function convertirAVenta(Cotizacion $cotizacion)
    {
        abort_unless($cotizacion->estado === 'aprobada', 403);

        $yaExiste = \App\Models\Venta::where('cotizacion_id', $cotizacion->id)->exists();
        if ($yaExiste) {
            $ventaExistente = \App\Models\Venta::where('cotizacion_id',$cotizacion->id)->first();
            return redirect()->route('ventas.show', $ventaExistente)
                ->with('ok','Esta cotización ya se había convertido en venta.');
        }

        $venta = DB::transaction(function() use ($cotizacion) {
            $venta = new \App\Models\Venta();
            $venta->cliente_id = $cotizacion->cliente_id;
            $venta->cotizacion_id = $cotizacion->id;
            $venta->estado = 'abierta';
            $venta->notas = $cotizacion->notas;

            $venta->descuento = $cotizacion->descuento;
            $venta->envio     = $cotizacion->envio;
            $venta->moneda    = $cotizacion->moneda;
            $venta->financiamiento_config = $cotizacion->financiamiento_config;
            $venta->save();

            $items = $cotizacion->items->map(function($it){
                return new \App\Models\VentaProducto([
                    'producto_id'     => $it->producto_id,
                    'descripcion'     => $it->descripcion,
                    'cantidad'        => $it->cantidad,
                    'precio_unitario' => $it->precio_unitario,
                    'descuento'       => $it->descuento,
                    'iva_porcentaje'  => $it->iva_porcentaje,
                    'importe'         => $it->importe,
                ]);
            });

            $venta->items()->saveMany($items);
            $venta->load('items');
            $venta->recalcularTotales();
            $venta->save();

            if ($cotizacion->plazos()->exists()) {
                $plazos = $cotizacion->plazos->map(function($pz){
                    return new \App\Models\VentaPlazo([
                        'numero'   => $pz->numero,
                        'vence_el' => $pz->vence_el,
                        'monto'    => $pz->monto,
                        'pagado'   => false,
                    ]);
                });
                $venta->plazos()->saveMany($plazos);
            }

            $cotizacion->estado = 'convertida';
            $cotizacion->save();

            return $venta;
        });

        return redirect()->route('ventas.show', $venta)->with('ok','¡Venta creada a partir de la cotización!');
    }

    /* =========================================================
     |                    IA DESDE PDF (NUEVO)
     |  Ruta: POST /cotizaciones/ai-parse   (name: cotizaciones.ai_parse)
     |  Devuelve JSON para pre-rellenar el formulario.
     * ========================================================= */

    public function aiParse(Request $r)
    {
        $r->validate([
            'pdf' => ['required','file','mimes:pdf','max:20480'], // 20MB
        ]);

        // 1) Extraer texto por páginas (OCR si hace falta)
        [$pages, $wasOcred] = $this->extractPdfPagesText($r->file('pdf')->getRealPath());

        // Previews de páginas para el meta-análisis
        $pageSummaries = [];
        foreach ($pages as $i => $txt) {
            $t = trim(preg_replace('/\s+/u', ' ', $txt));
            $pageSummaries[] = [
                'index'   => $i + 1,
                'preview' => mb_substr($t, 0, 1200),
                'length'  => mb_strlen($t),
            ];
        }

        // 2) Paso A: IA decide qué páginas son relevantes
        $findPrompt = json_encode([
            'task' => 'find_relevant_pages',
            'instruction' => 'Eres muy estricto. Devuelve solo JSON.',
            'document_type_hint' => 'licitaciones, requisiciones, pedidos, cotizaciones, órdenes de compra del gobierno y sector privado',
            'pages' => $pageSummaries,
            'want' => ['pages_with_items','pages_with_totals','pages_with_terms','pages_with_client'],
            'notes' => 'Ignora bases legales, anexos, carátulas, firmas.',
        ], JSON_UNESCAPED_UNICODE);

        $findJson = $this->callOpenAIJson(<<<PROMPT
Analiza el índice de páginas (preview). Devuelve SOLO JSON:
{
  "relevant_pages": [número de página (1-based), ...], 
  "reasoning": string
}
ÍNDICE:
{$findPrompt}
PROMPT);

        $find = $this->safeJson($findJson);
        $relevantPages = array_values(array_unique(array_filter($find['relevant_pages'] ?? [], fn($n) => is_int($n) && $n >= 1 && $n <= count($pages))));
        if (empty($relevantPages)) {
            $relevantPages = range(1, min(count($pages), 8)); // fallback
        }

        // 3) Corpus solo con páginas relevantes
        $joined = [];
        foreach ($relevantPages as $pn) {
            $txt = trim($pages[$pn - 1] ?? '');
            if ($txt !== '') {
                $joined[] = "=== PAGINA {$pn} ===\n" . mb_substr($txt, 0, 20000);
            }
        }
        $corpus = mb_substr(implode("\n\n", $joined), 0, 90000);

        // 4) Paso B: extracción estructurada
        $extractPrompt = <<<PR
Eres un extractor experto en documentos de compra y licitaciones.
Devuelve SOLO JSON con este esquema:

{
 "cliente_nombre": string|null,
 "cliente_email": string|null,
 "cliente_telefono": string|null,
 "moneda": "MXN"|"USD"|string|null,
 "notas": string|null,
 "validez_dias": number|null,
 "envio": number|null,
 "descuento_global": number|null,
 "items": [
   {
     "nombre": string,
     "descripcion": string|null,
     "cantidad": number,
     "unidad": string|null,
     "precio_unitario": number,
     "descuento": number|null,
     "iva_porcentaje": number|null
   }
 ],
 "campos_detectados": { "paginas": [número], "observaciones": string|null }
}
Reglas: si dice "IVA incluido", intenta normalizar a precio sin IVA (MXN 16% por defecto). 
Usa punto decimal. Ignora encabezados y subtotales intermedios.

TEXTO RELEVANTE:
---
{$corpus}
---
PR;

        $extractJson = $this->callOpenAIJson($extractPrompt);
        $parsed = $this->safeJson($extractJson);

        // 5) Empatar con clientes y productos locales
        $clienteId = $this->matchClientId($parsed['cliente_nombre'] ?? null, $parsed['cliente_email'] ?? null, $parsed['cliente_telefono'] ?? null);

        $itemsInput = [];
        $ivaDefault = 16;
        $items = is_array($parsed['items'] ?? null) ? $parsed['items'] : [];
        foreach ($items as $row) {
            $name = (string)($row['nombre'] ?? ($row['descripcion'] ?? ''));
            $prod = $this->matchProduct($name);
            $itemsInput[] = [
                'producto_id'     => $prod?->id,
                'descripcion'     => $row['descripcion'] ?? $name,
                'cantidad'        => (float)($row['cantidad'] ?? 1),
                'precio_unitario' => (float)($row['precio_unitario'] ?? ($prod->price ?? 0)),
                'descuento'       => (float)($row['descuento'] ?? 0),
                'iva_porcentaje'  => isset($row['iva_porcentaje']) ? (float)$row['iva_porcentaje'] : $ivaDefault,
            ];
        }

        return response()->json([
            'ok' => true,
            'ocr_used' => $wasOcred,
            'ai_reason' => $find['reasoning'] ?? null,
            'relevant_pages' => $relevantPages,
            'cliente_id' => $clienteId,
            'cliente_match_name' => $clienteId ? $this->displayClient($clienteId) : null,
            'moneda' => $parsed['moneda'] ?? 'MXN',
            'notas' => $parsed['notas'] ?? null,
            'validez_dias' => $parsed['validez_dias'] ?? 15,
            'envio' => $parsed['envio'] ?? 0,
            'descuento' => $parsed['descuento_global'] ?? 0,
            'items' => $itemsInput,
            'debug_campos' => $parsed['campos_detectados'] ?? null,
        ]);
    }

    /* ==================== Helpers IA ==================== */

    // Extrae texto por página; intenta OCR si no hay texto
    private function extractPdfPagesText(string $path): array
    {
        $parser = new PdfParser();
        $pages = $parser->parseFile($path)->getPages();
        $texts = array_map(fn($p) => $p->getText() ?? '', $pages);

        $hasText = array_reduce($texts, fn($c,$t) => $c || (trim($t) !== ''), false);
        if ($hasText) return [$texts, false];

        // Fallback OCR (si ocrmypdf está instalado)
        $tmpOcr = sys_get_temp_dir() . '/ocr_' . uniqid() . '.pdf';
        try {
            $proc = new Process(['ocrmypdf', '--force-ocr', '--skip-text', '--quiet', $path, $tmpOcr]);
            $proc->setTimeout(120);
            $proc->run();
            if ($proc->isSuccessful() && file_exists($tmpOcr)) {
                $pages2 = $parser->parseFile($tmpOcr)->getPages();
                $texts2 = array_map(fn($p) => $p->getText() ?? '', $pages2);
                @unlink($tmpOcr);
                return [$texts2, true];
            }
        } catch (\Throwable $e) {
            // sin OCR continuamos con lo que haya
        }
        return [$texts, false];
    }

    // Llamada a OpenAI que devuelve SOLO JSON en message->content
    private function callOpenAIJson(string $prompt): ?string
    {
        $key = env('OPENAI_API_KEY');
        if (!$key) return null;

        $payload = [
            'model' => 'gpt-4o-mini', // puedes subir a gpt-4.1 si quieres más calidad
            'messages' => [
                ['role' => 'system', 'content' => 'Responde estrictamente con JSON válido.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.1,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . 'Bearer ' . $key,
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 90,
        ]);
        $res = curl_exec($ch);
        if ($res === false) return null;
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($code >= 300) return null;

        $obj = json_decode($res, true);
        return $obj['choices'][0]['message']['content'] ?? null;
    }

    private function safeJson(?string $raw): array
    {
        if (!$raw) return [];
        $raw = trim($raw);
        $raw = preg_replace('/^```json|```$/m', '', $raw); // quitar fences si ocurriera
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function normalize($s)
    {
        $s = mb_strtolower($s ?? '');
        if (class_exists('\Normalizer')) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
            $s = preg_replace('/\p{Mn}+/u', '', $s); // quitar acentos
        }
        $s = preg_replace('/\s+/', ' ', trim($s));
        return $s;
    }

    private function displayClient(int $id): ?string
    {
        $c = Client::find($id);
        if (!$c) return null;
        foreach (['name','nombre','razon_social'] as $k) {
            if (!empty($c->{$k})) return $c->{$k};
        }
        return "ID {$c->id}";
    }

    private function matchClientId(?string $nombre, ?string $email, ?string $tel): ?int
    {
        $normName  = $this->normalize($nombre ?? '');
        $normEmail = $this->normalize($email ?? '');
        $normTel   = preg_replace('/\D+/', '', (string)$tel);

        $bestId = null; $best = 0;

        foreach (Client::all(['id','name','nombre','razon_social','email','telefono','phone']) as $c) {
            $score = 0;

            $candName = $this->normalize($c->name ?? $c->nombre ?? $c->razon_social ?? '');
            if ($normName && $candName) {
                similar_text($normName, $candName, $pct);
                $score += $pct;
            }

            $candEmail = $this->normalize($c->email ?? '');
            if ($normEmail && $candEmail && $normEmail === $candEmail) $score += 40;

            $candTel = preg_replace('/\D+/', '', (string)($c->telefono ?? $c->phone ?? ''));
            if ($normTel && $candTel && str_ends_with($candTel, $normTel)) $score += 25;

            if ($score > $best) { $best = $score; $bestId = $c->id; }
        }

        return $best >= 55 ? $bestId : null;
    }

    private function matchProduct(string $name): ?Product
    {
        $n = $this->normalize($name);
        if ($n === '') return null;

        $all = Product::select(['id','name','nombre','descripcion','price','precio'])->get();

        $best = null; $bestScore = 0;
        foreach ($all as $p) {
            $label = $this->normalize($p->name ?? $p->nombre ?? $p->descripcion ?? '');
            if ($label === '') continue;
            similar_text($n, $label, $pct);
            if ($pct > $bestScore) { $bestScore = $pct; $best = $p; }
        }

        return $bestScore >= 55 ? $best : null;
    }
}
