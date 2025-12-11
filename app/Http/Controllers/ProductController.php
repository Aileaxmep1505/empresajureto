<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // IA OpenAI
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;
use App\Exports\ProductsExport;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /** Filtro de búsqueda reutilizable */
    private function applySearch($query, string $q)
    {
        $q = trim($q);
        if ($q === '') return $query;

        return $query->where(function($qq) use ($q){
            $qq->where('name', 'like', "%{$q}%")
               ->orWhere('sku', 'like', "%{$q}%")
               ->orWhere('brand', 'like', "%{$q}%")
               ->orWhere('category', 'like', "%{$q}%")
               ->orWhere('tags', 'like', "%{$q}%")
               ->orWhere('clave_sat', 'like', "%{$q}%");
        });
    }

    /** Listado con paginación + filtros UI */
    public function index(Request $request)
    {
        $q         = (string) $request->get('q', '');
        $category  = $request->get('category');
        $onlyNoSat = (bool) $request->boolean('only_without_sat', false);

        $query = $this->applySearch(Product::query(), $q);

        if (!empty($category)) {
            $query->where('category', $category);
        }

        if ($onlyNoSat) {
            $query->whereNull('clave_sat');
        }

        $products = $query->orderByDesc('id')->paginate(50)->withQueryString();

        $categories = Product::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('products.index-table', [
            'products'         => $products,
            'q'                => $q,
            'categories'       => $categories,
            'selectedCategory' => $category,
            'onlyWithoutSat'   => $onlyNoSat,
        ]);
    }

    /** Bulk update de clave_sat para muchos productos */
    public function bulkClaveSat(Request $request)
    {
        $data = $request->validate([
            'product_ids'   => ['required','array'],
            'product_ids.*' => ['integer','exists:products,id'],
            'clave_sat'     => ['required','string','max:30'],
        ]);

        $affected = Product::whereIn('id', $data['product_ids'])
            ->update(['clave_sat' => $data['clave_sat']]);

        return back()->with('status', "Se actualizó la clave SAT en {$affected} productos.");
    }

    /**
     * IA / Reglas: sugerir una clave SAT para UN producto (papelería).
     * Frontend manda JSON: {name, description, category}
     * Respuesta JSON: { suggestion: "NNNNNNNN", ... }
     */
    public function aiSuggestClaveSat(Request $request)
    {
        $data = $request->validate([
            'name'        => ['nullable','string','max:255'],
            'description' => ['nullable','string'],
            'category'    => ['nullable','string','max:255'],
        ]);

        // 1) Unimos todos los textos disponibles
        $texto = trim(
            ($data['name'] ?? '') . ' ' .
            ($data['description'] ?? '') . ' ' .
            ($data['category'] ?? '')
        );

        if ($texto === '') {
            return response()->json([
                'message' => 'No hay información suficiente (nombre/descripcion/categoría) para sugerir una clave SAT.',
            ], 422);
        }

        $tNorm = mb_strtolower($texto, 'UTF-8');

        // 2) PRIMERO: heurísticas de papelería determinísticas
        $rule = $this->papeleriaHeuristicClaveSat($texto);

        if ($rule !== null) {
            return response()->json([
                'suggestion'     => $rule['clave'],
                'tipo_detectado' => $rule['tipo'],
                'source'         => 'HEURISTICA_PAPELERIA',
            ]);
        }

        // 3) Si no es claramente papelería clásica, seguimos con IA pero con reglas fuertes
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            return response()->json([
                'message' => 'No hay API key configurada para la IA (OPENAI_API_KEY).',
            ], 500);
        }

        // Tipo general sólo informativo
        $tipo = 'OTROS';
        if (str_contains($tNorm, 'libreta') || str_contains($tNorm, 'cuaderno')) {
            $tipo = 'LIBRETA';
        } elseif (
            str_contains($tNorm, 'folder') ||
            str_contains($tNorm, 'carpeta') ||
            str_contains($tNorm, 'caja para archivo') ||
            str_contains($tNorm, 'caja para archivar') ||
            str_contains($tNorm, 'archivador')
        ) {
            $tipo = 'ARCHIVO';
        } elseif (str_contains($tNorm, 'sobre')) {
            $tipo = 'SOBRE';
        }

        // 4) Prompt más limitado, dándole una lista de claves típicas de papelería
        $systemContent = <<<SYS
Eres un experto en el catálogo de productos y servicios del SAT (CFDI 4.0, México).

Solo puedes sugerir claves de este subconjunto típico de papelería y suministros de oficina (elige la que mejor coincida):

- 44121618 Tijeras
- 14111514 Blocs o cuadernos de papel
- 44121707 Lápices de colores
- 44121706 Lápices de madera
- 44121705 Lápices mecánicos / lapiceros
- 44121701 Bolígrafos
- 60121535 Borradores de goma
- 14111506 Papel para impresión de computadores
- 31201610 Pegamentos (lápiz adhesivo)
- 31201600 Otros adhesivos y selladores
- 31201500 Cinta adhesiva
- 60103110 Guías de referencia de geometría
- 44121708 Marcadores
- 44121709 Crayolas
- 44121801 Película o cinta de corrección (Corrector en cinta)
- 44121802 Fluido de corrección (Corrector líquido)
- 44121503 Sobres
- 44122019 Bolsillos para archivos / sobres manila
- 44122000 Carpetas de archivo, carpetas y separadores
- 44122104 Clips para papel
- 44111521 Sujetadores de copias / broches
- 44122107 Grapas
- 44121615 Engrapadoras
- 14111530 Papel de notas autoadhesivas
- 41111604 Reglas
- 43211503 Calculadoras

REGLAS:

- RESPONDES ÚNICAMENTE con una clave de 8 dígitos del SAT de la lista anterior.
- NO expliques nada, NO agregues texto adicional, solo la clave de 8 dígitos.
- Si ninguna coincide claramente, responde: 00000000.
SYS;

        if ($tipo === 'LIBRETA') {
            $systemContent .= "\n\nPara libretas y cuadernos de cualquier tipo, si dudas, prefiere 14111514.\n";
        } elseif ($tipo === 'ARCHIVO') {
            $systemContent .= "\n\nPara carpetas, folders y archivadores, si dudas, prefiere 44122000.\n";
        } elseif ($tipo === 'SOBRE') {
            $systemContent .= "\n\nPara sobres y sobres bolsa, si dudas, prefiere 44121503.\n";
        }

        try {
            $resp = Http::withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4.1-mini',
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => $systemContent,
                        ],
                        [
                            'role'    => 'user',
                            'content' => "Texto del producto:\n\"{$texto}\"\n\nDame SOLO la clave SAT más probable (8 dígitos) de la lista anterior:",
                        ],
                    ],
                    'temperature' => 0.0,
                    'max_tokens'  => 10,
                ]);

            if (!$resp->ok()) {
                Log::error('AI clave_sat HTTP error', ['status' => $resp->status(), 'body' => $resp->body()]);
                return response()->json([
                    'message' => 'La IA no respondió correctamente.',
                ], 500);
            }

            $json = $resp->json();
            $raw  = $json['choices'][0]['message']['content'] ?? '';

            // Extraemos SOLO dígitos
            $suggestion = preg_replace('/\D+/', '', $raw);

            if (strlen($suggestion) !== 8) {
                return response()->json([
                    'message' => 'La IA no pudo determinar una clave SAT clara para este producto.',
                    'raw'     => $raw,
                ], 422);
            }

            // Si la IA responde 00000000, consideramos que no supo
            if ($suggestion === '00000000') {
                return response()->json([
                    'message' => 'La IA no encontró una clave adecuada en el subconjunto de papelería.',
                    'raw'     => $raw,
                ], 422);
            }

            return response()->json([
                'suggestion'     => $suggestion,
                'raw'            => $raw,
                'origen'         => 'IA_SUBCONJUNTO_PAPELERIA',
                'tipo_detectado' => $tipo,
                'source'         => 'IA_OPENAI',
            ]);
        } catch (\Throwable $e) {
            Log::error('AI clave_sat exception', ['msg' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al consultar la IA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Heurísticas específicas para papelería y suministros de oficina (sin IA).
     * Recibe el texto completo original y lo normaliza.
     */
    private function papeleriaHeuristicClaveSat(string $texto): ?array
    {
        $t = mb_strtolower($texto, 'UTF-8');
        $t = preg_replace('/\s+/', ' ', $t);

        // =========================
        // 1) SOBRES Y SOBRES BOLSA
        // =========================
        if (str_contains($t, 'sobre')) {
            // sobres manila / archivo → 44122019
            if (str_contains($t, 'manila')) {
                return [
                    'clave' => '44122019',
                    'tipo'  => 'SOBRE MANILA / BOLSILLOS ARCHIVO',
                ];
            }

            // "sobre bolsa tamaño oficio/legal/carta/ministro" → sobres 44121503
            if (
                str_contains($t, 'bolsa') ||
                str_contains($t, 'oficio') ||
                str_contains($t, 'carta') ||
                str_contains($t, 'legal') ||
                str_contains($t, 'ministro') ||
                str_contains($t, 'radiografia') ||
                str_contains($t, 'hilo') ||
                str_contains($t, 'rondana')
            ) {
                return [
                    'clave' => '44121503',
                    'tipo'  => 'SOBRES / SOBRES BOLSA',
                ];
            }

            // Genérico “sobres”
            return [
                'clave' => '44121503',
                'tipo'  => 'SOBRES / GENÉRICO',
            ];
        }

        // =========================
        // 2) LIBRETAS / CUADERNOS
        // =========================
        if (
            str_contains($t, 'libreta') ||
            str_contains($t, 'cuaderno') ||
            str_contains($t, 'bitacora') ||
            str_contains($t, 'bitácora') ||
            str_contains($t, 'bloc de notas') ||
            str_contains($t, 'block de notas')
        ) {
            return [
                'clave' => '14111514', // Blocs o cuadernos de papel
                'tipo'  => 'LIBRETAS / CUADERNOS',
            ];
        }

        // =========================
        // 3) PAPEL / HOJAS / RESMAS
        // =========================
        if (
            (str_contains($t, 'hojas') || str_contains($t, 'resma') || str_contains($t, 'resmas')) &&
            (str_contains($t, 'carta') || str_contains($t, 'oficio') || str_contains($t, 'tamaño carta') || str_contains($t, 'tamaño oficio'))
        ) {
            if (str_contains($t, 'bond') || str_contains($t, 'papel') || str_contains($t, 'blanco')) {
                return [
                    'clave' => '14111506', // Papel para impresión de computadores
                    'tipo'  => 'PAPEL PARA IMPRESIÓN (RESMAS)',
                ];
            }
        }

        // Notas adhesivas tipo Post-it
        if (
            str_contains($t, 'post-it') ||
            str_contains($t, 'post it') ||
            str_contains($t, 'notas adhesivas') ||
            str_contains($t, 'papel auto adhesivo') ||
            str_contains($t, 'papel autoadhesivo') ||
            str_contains($t, 'sticky notes')
        ) {
            return [
                'clave' => '14111530', // Papel de notas autoadhesivas
                'tipo'  => 'PAPEL DE NOTAS AUTOADHESIVAS',
            ];
        }

        // =========================
        // 4) INSTRUMENTOS DE ESCRITURA
        // =========================
        // Lápices de madera / colores
        if (str_contains($t, 'lapiz') || str_contains($t, 'lápiz') || str_contains($t, 'lapices') || str_contains($t, 'lápices')) {

            if (
                str_contains($t, 'color') ||
                str_contains($t, 'colores') ||
                str_contains($t, 'escolar') ||
                str_contains($t, 'mapita')
            ) {
                return [
                    'clave' => '44121707', // Lápices de colores
                    'tipo'  => 'LÁPICES DE COLORES',
                ];
            }

            if (
                str_contains($t, 'mecanico') ||
                str_contains($t, 'mecánico') ||
                str_contains($t, 'portaminas') ||
                str_contains($t, 'portaminuto') ||
                str_contains($t, 'lapicero')
            ) {
                return [
                    'clave' => '44121705', // Lápices mecánicos / lapiceros
                    'tipo'  => 'LÁPICES MECÁNICOS / LAPICEROS',
                ];
            }

            return [
                'clave' => '44121706', // Lápices de madera
                'tipo'  => 'LÁPICES DE MADERA',
            ];
        }

        // Bolígrafos / plumas
        if (
            str_contains($t, 'boligrafo') ||
            str_contains($t, 'bolígrafo') ||
            str_contains($t, 'pluma') ||
            str_contains($t, 'tinta gel') ||
            str_contains($t, 'balpen') ||
            str_contains($t, 'ball pen') ||
            str_contains($t, 'esfero')
        ) {
            return [
                'clave' => '44121701', // Bolígrafos
                'tipo'  => 'BOLÍGRAFOS / PLUMAS',
            ];
        }

        // Marcadores / marcatextos / resaltadores
        if (
            str_contains($t, 'marcador') ||
            str_contains($t, 'marcatexto') ||
            str_contains($t, 'marcatextos') ||
            str_contains($t, 'resaltador') ||
            str_contains($t, 'highlighter')
        ) {
            return [
                'clave' => '44121708', // Marcadores
                'tipo'  => 'MARCADORES / MARCATEXTOS',
            ];
        }

        // Crayolas
        if (
            str_contains($t, 'crayola') ||
            str_contains($t, 'crayolas') ||
            str_contains($t, 'ceras escolares')
        ) {
            return [
                'clave' => '44121709', // Crayolas
                'tipo'  => 'CRAYOLAS',
            ];
        }

        // =========================
        // 5) PEGAMENTO / ADHESIVOS
        // =========================
        if (
            str_contains($t, 'pegamento') ||
            str_contains($t, 'resistol') ||
            str_contains($t, 'adhesivo') ||
            str_contains($t, 'barra adhesiva') ||
            str_contains($t, 'lápiz adhesivo') ||
            str_contains($t, 'lapiz adhesivo')
        ) {
            if (
                str_contains($t, 'barra') ||
                str_contains($t, 'lápiz') ||
                str_contains($t, 'lapiz')
            ) {
                return [
                    'clave' => '31201610', // Pegamentos (lápiz adhesivo)
                    'tipo'  => 'PEGAMENTO ESCOLAR / LÁPIZ ADHESIVO',
                ];
            }

            return [
                'clave' => '31201600', // Otros adhesivos y selladores
                'tipo'  => 'OTROS ADHESIVOS',
            ];
        }

        // Cinta adhesiva
        if (
            str_contains($t, 'cinta adhesiva') ||
            str_contains($t, 'diurex') ||
            str_contains($t, 'masking') ||
            str_contains($t, 'maskin') ||
            str_contains($t, 'masking tape') ||
            str_contains($t, 'cinta doble cara') ||
            (str_contains($t, 'cinta') && str_contains($t, 'adhesiva'))
        ) {
            return [
                'clave' => '31201500', // Cinta adhesiva
                'tipo'  => 'CINTA ADHESIVA',
            ];
        }

        // Correctores
        if (str_contains($t, 'corrector')) {
            if (str_contains($t, 'cinta') || str_contains($t, 'cintillo')) {
                return [
                    'clave' => '44121801', // Película o cinta de corrección
                    'tipo'  => 'CORRECTOR EN CINTA',
                ];
            }

            return [
                'clave' => '44121802', // Fluido de corrección
                'tipo'  => 'CORRECTOR LÍQUIDO',
            ];
        }

        // =========================
        // 6) ORGANIZACIÓN / ARCHIVO
        // =========================

        // Carpetas, folders, separadores, micas, engargolados
        if (
            str_contains($t, 'folder') ||
            str_contains($t, 'carpeta') ||
            str_contains($t, 'archivador') ||
            str_contains($t, 'archivero') ||
            str_contains($t, 'clasificador') ||
            str_contains($t, 'separador') ||
            str_contains($t, 'micas') ||
            str_contains($t, 'mica') ||
            str_contains($t, 'engargolado') ||
            str_contains($t, 'engargolar')
        ) {
            return [
                'clave' => '44122000', // Carpetas de archivo, carpetas y separadores
                'tipo'  => 'CARPETAS / SEPARADORES / MICA',
            ];
        }

        // Clips / sujetapapeles / broches
        if (
            str_contains($t, 'clip') ||
            str_contains($t, 'clips') ||
            str_contains($t, 'bulldog') ||
            str_contains($t, 'sujetapapeles') ||
            str_contains($t, 'sujetador') ||
            str_contains($t, 'broche baco') ||
            str_contains($t, 'broches baco') ||
            str_contains($t, 'broche mariposa')
        ) {
            if (str_contains($t, 'clip') || str_contains($t, 'clips')) {
                return [
                    'clave' => '44122104', // Clips para papel
                    'tipo'  => 'CLIPS PARA PAPEL',
                ];
            }

            return [
                'clave' => '44111521', // Sujetadores de copias / broches
                'tipo'  => 'SUJETADORES / BROCHES',
            ];
        }

        // Grapas
        if (
            str_contains($t, 'grapas') ||
            str_contains($t, 'grapa no') ||
            str_contains($t, 'grapa estándar') ||
            str_contains($t, 'grapa estandar')
        ) {
            return [
                'clave' => '44122107', // Grapas
                'tipo'  => 'GRAPAS',
            ];
        }

        // Engrapadoras
        if (
            str_contains($t, 'engrapadora') ||
            str_contains($t, 'engrampadora')
        ) {
            return [
                'clave' => '44121615', // Engrapadoras
                'tipo'  => 'ENGRAPADORAS',
            ];
        }

        // Reglas / escuadras / transportadores
        if (
            str_contains($t, 'regla ') ||
            str_contains($t, 'regla de') ||
            str_contains($t, 'escuadra') ||
            str_contains($t, 'transportador')
        ) {
            return [
                'clave' => '41111604', // Reglas
                'tipo'  => 'REGLAS / INSTRUMENTOS DE MEDICIÓN ESCOLAR',
            ];
        }

        // Calculadoras
        if (str_contains($t, 'calculadora')) {
            return [
                'clave' => '43211503', // Calculadoras
                'tipo'  => 'CALCULADORAS',
            ];
        }

        // Tijeras
        if (str_contains($t, 'tijera')) {
            return [
                'clave' => '44121618', // Tijeras
                'tipo'  => 'TIJERAS',
            ];
        }

        // Borradores / gomas
        if (
            str_contains($t, 'borrador') ||
            str_contains($t, 'goma de borrar') ||
            str_contains($t, 'goma para borrar') ||
            (str_contains($t, 'goma') && str_contains($t, 'borrar'))
        ) {
            return [
                'clave' => '60121535', // Borradores de goma
                'tipo'  => 'BORRADORES / GOMAS',
            ];
        }

        // Si no entra en ninguna regla, devolvemos null y dejamos que la IA intente
        return null;
    }

    /** Exportación a PDF profesional / minimalista */
   public function exportPdf(Request $request)
{
    $q = (string) $request->get('q','');

    // ⚙️ subir memoria y tiempo solo para este proceso
    @ini_set('memory_limit', '512M');
    @set_time_limit(120);

    // Query base solo con las columnas que usamos en el PDF
    $baseQuery = $this->applySearch(
        Product::select('id','name','sku','brand','category','unit','color','cost','price','clave_sat'),
        $q
    )->orderBy('name');

    // total real (por si quieres mostrar "mostrando X de Y")
    $totalCount = (clone $baseQuery)->count();

    // ⚠️ Limitar productos para que DomPDF no muera
    $maxRows = 1500; // puedes bajarlo/subirlo si ves problemas
    $products = $baseQuery->limit($maxRows)->get();

    $generated_at = now();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('products.export-pdf', [
        'products'     => $products,
        'q'            => $q,
        'generated_at' => $generated_at,
        'totalCount'   => $totalCount,
        'maxRows'      => $maxRows,
    ])->setPaper('a4', 'landscape');

    return $pdf->download('productos.pdf');
}


    /** Exportación a Excel profesional / minimalista */
   public function exportExcel(Request $request)
{
    $q = (string) $request->get('q','');

    $fileName = 'productos_' . now()->format('Ymd_His') . '.xlsx';

    // Pasamos el filtro al export
    return Excel::download(new ProductsExport($q), $fileName);
}
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function create()
    {
        return view('products.form', ['product' => new Product(), 'mode' => 'create']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => ['nullable','string','max:255'],
            'sku'             => ['nullable','string','max:255'],
            'supplier_sku'    => ['nullable','string','max:255'],
            'unit'            => ['nullable','string','max:100'],
            'weight'          => ['nullable','numeric'],
            'cost'            => ['nullable','numeric'],
            'price'           => ['nullable','numeric'],
            'market_price'    => ['nullable','numeric'],
            'bid_price'       => ['nullable','numeric'],
            'dimensions'      => ['nullable','string','max:255'],
            'color'           => ['nullable','string','max:255'],
            'pieces_per_unit' => ['nullable','integer','min:0'],
            'active'          => ['nullable','boolean'],
            'brand'           => ['nullable','string','max:255'],
            'category'        => ['nullable','string','max:255'],
            'material'        => ['nullable','string','max:255'],
            'description'     => ['nullable','string'],
            'notes'           => ['nullable','string'],
            'tags'            => ['nullable','string','max:255'],
            'clave_sat'       => ['nullable','string','max:30'],
            'image'           => ['nullable','image','max:4096'],
        ]);

        $data['active'] = (bool) $request->boolean('active');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products','public');
        }

        $product = Product::create($data);

        return redirect()->route('products.edit',$product)
            ->with('status','Producto creado');
    }

    public function edit(Product $product)
    {
        return view('products.form', ['product' => $product, 'mode' => 'edit']);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'            => ['nullable','string','max:255'],
            'sku'             => ['nullable','string','max:255'],
            'supplier_sku'    => ['nullable','string','max:255'],
            'unit'            => ['nullable','string','max:100'],
            'weight'          => ['nullable','numeric'],
            'cost'            => ['nullable','numeric'],
            'price'           => ['nullable','numeric'],
            'market_price'    => ['nullable','numeric'],
            'bid_price'       => ['nullable','numeric'],
            'dimensions'      => ['nullable','string','max:255'],
            'color'           => ['nullable','string','max:255'],
            'pieces_per_unit' => ['nullable','integer','min:0'],
            'active'          => ['nullable','boolean'],
            'brand'           => ['nullable','string','max:255'],
            'category'        => ['nullable','string','max:255'],
            'material'        => ['nullable','string','max:255'],
            'description'     => ['nullable','string'],
            'notes'           => ['nullable','string'],
            'tags'            => ['nullable','string','max:255'],
            'clave_sat'       => ['nullable','string','max:30'],
            'image'           => ['nullable','image','max:4096'],
        ]);

        $data['active'] = (bool) $request->boolean('active');

        if ($request->hasFile('image')) {
            if ($product->image_path) Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = $request->file('image')->store('products','public');
        }

        $product->update($data);

        return back()->with('status','Producto actualizado');
    }

    public function destroy(Product $product)
    {
        if ($product->image_path) Storage::disk('public')->delete($product->image_path);
        $product->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok'=>true]);
        }
        return redirect()->route('products.index')->with('status','Producto eliminado');
    }

    public function importForm()
    {
        return view('products.import');
    }

    public function importStore(Request $request)
    {
        $data = $request->validate([
            'file'            => ['required','file','mimes:xlsx,xls,csv','max:51200'],
            'download_images' => ['nullable','boolean'],
            'queue'           => ['nullable','boolean'],
        ]);

        try {
            $file = $request->file('file');
            $ext  = strtolower($file->getClientOriginalExtension());

            // Comprueba BD actual y conteo previo
            $dbName = \DB::connection()->getDatabaseName();
            $before = \App\Models\Product::count();
            \Log::info('Import inicia', ['db' => $dbName, 'before_count' => $before, 'ext' => $ext]);

            if (in_array($ext, ['xlsx','xls'], true) && !class_exists(\ZipArchive::class)) {
                return back()->withErrors(['file' => 'Habilita ZipArchive en php.ini (o importa como CSV).']);
            }

            $import = new ProductsImport(
                downloadImages: (bool)$request->boolean('download_images')
            );

            Excel::import($import, $file);

            $after = \App\Models\Product::count();
            \Log::info('Import termina', [
                'db'          => $dbName,
                'after_count' => $after,
                'created'     => $import->created,
                'updated'     => $import->updated,
                'skipped'     => $import->skipped,
            ]);

            $failures = method_exists($import, 'failures') ? $import->failures() : [];
            $msg = "Importación completada. Nuevos: {$import->created}, Actualizados: {$import->updated}, Omitidos: {$import->skipped}. Total antes: {$before}, después: {$after} (BD: {$dbName}).";

            return back()->with(['status' => $msg, 'failures' => $failures]);
        } catch (\Throwable $e) {
            \Log::error('Importación de productos fallida', [
                'msg'   => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['file' => 'Error al importar: '.$e->getMessage()]);
        }
    }

    private function importCsv(string $path): int
    {
        if (!is_readable($path)) throw new \RuntimeException('No se pudo leer el CSV.');
        $h = fopen($path, 'r'); if ($h === false) throw new \RuntimeException('No se pudo abrir el CSV.');

        $header = null; $count = 0;
        while (($row = fgetcsv($h, 0, ',')) !== false) {
            if ($header === null) {
                $header = array_map(function($s){
                    $s = strtolower(trim($s ?? ''));
                    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
                    $s = preg_replace('/[^a-z0-9]+/i', '_', $s);
                    return trim($s, '_');
                }, $row);
                continue;
            }

            $assoc = [];
            foreach ($header as $i => $k) $assoc[$k] = $row[$i] ?? null;

            $bool = fn($v) => in_array(strtolower((string)$v), ['1','true','si','sí','yes','y'], true);

            $data = [
                'name'            => $assoc['name']            ?? ($assoc['nombre'] ?? null),
                'sku'             => $assoc['sku']             ?? null,
                'supplier_sku'    => $assoc['supplier_sku']    ?? ($assoc['sku_prov'] ?? null),
                'unit'            => $assoc['unit']            ?? ($assoc['unidad'] ?? null),
                'weight'          => $assoc['weight']          ?? null,
                'cost'            => $assoc['cost']            ?? null,
                'price'           => $assoc['price']           ?? null,
                'market_price'    => $assoc['market_price']    ?? null,
                'bid_price'       => $assoc['bid_price']       ?? null,
                'dimensions'      => $assoc['dimensions']      ?? ($assoc['dimensiones'] ?? null),
                'color'           => $assoc['color']           ?? null,
                'pieces_per_unit' => $assoc['pieces_per_unit'] ?? ($assoc['pzs_u'] ?? null),
                'active'          => $bool($assoc['active'] ?? ($assoc['activo'] ?? '1')),
                'brand'           => $assoc['brand']           ?? ($assoc['marca'] ?? null),
                'category'        => $assoc['category']        ?? ($assoc['categoria'] ?? null),
                'material'        => $assoc['material']        ?? null,
                'description'     => $assoc['description']     ?? ($assoc['descripcion'] ?? null),
                'notes'           => $assoc['notes']           ?? ($assoc['notas'] ?? null),
                'tags'            => $assoc['tags']            ?? null,
                'clave_sat'       => $assoc['clave_sat']       ?? ($assoc['sat_key'] ?? null),
            ];

            if (!empty($data['sku'])) {
                Product::updateOrCreate(['sku' => $data['sku']], $data);
            } else {
                Product::create($data);
            }
            $count++;
        }

        fclose($h);
        return $count;
    }

    /** API: listado paginado en JSON (para el frontend React) */
    public function apiIndex(Request $request)
    {
        $q        = (string) $request->get('q', '');
        $category = $request->get('category');
        $sort     = $request->get('sort');
        $min      = $request->get('min_price');
        $max      = $request->get('max_price');
        $per      = (int) $request->get('per_page', 24);

        $query = $this->applySearch(Product::query(), $q);

        if (!empty($category)) $query->where('category', $category);
        if ($min !== null && $min !== '') $query->where(function($qq) use ($min){
            $qq->where('price','>=',(float)$min)
               ->orWhere('market_price','>=',(float)$min)
               ->orWhere('bid_price','>=',(float)$min);
        });
        if ($max !== null && $max !== '') $query->where(function($qq) use ($max){
            $qq->where('price','<=',(float)$max)
               ->orWhere('market_price','<=',(float)$max)
               ->orWhere('bid_price','<=',(float)$max);
        });

        $query->orderByRaw("
            (CASE WHEN (name IS NULL OR name='') THEN 1 ELSE 0 END) ASC,
            (CASE WHEN COALESCE(price, market_price, bid_price, 0) = 0 THEN 1 ELSE 0 END) ASC
        ");

        switch ($sort) {
            case 'newest':     $query->orderByDesc('id'); break;
            case 'price_asc':  $query->orderByRaw('COALESCE(price, market_price, bid_price, 0) ASC'); break;
            case 'price_desc': $query->orderByRaw('COALESCE(price, market_price, bid_price, 0) DESC'); break;
            default:           $query->orderByDesc('id'); break;
        }

        $page = $query->paginate($per)->through(function (Product $p) {
            $priceRaw = $p->getRawOriginal('price');
            if ($priceRaw === null || (float)$priceRaw == 0.0) {
                $priceRaw = $p->getRawOriginal('market_price');
            }
            if ($priceRaw === null || (float)$priceRaw == 0.0) {
                $priceRaw = $p->getRawOriginal('bid_price');
            }

            $listRaw  = $p->getRawOriginal('market_price');

            $name  = (string)($p->name ?? $p->getRawOriginal('name') ?? '');
            $brand = (string)($p->brand ?? $p->getRawOriginal('brand') ?? '');

            $img = $p->image_src ?: url('/placeholder.png');

            return [
                'id'         => $p->id,
                'slug'       => $p->slug ?? (string)$p->id,
                'name'       => ($name !== '' ? $name : 'Producto'),
                'sku'        => $p->sku,
                'brand'      => $brand,
                'category'   => $p->category,
                'price'      => $priceRaw !== null ? (float)$priceRaw : 0.0,
                'list_price' => $listRaw  !== null ? (float)$listRaw  : null,
                'short_description' => $p->description ? Str::limit(strip_tags($p->description), 140) : null,
                'image_src'  => $img,
                'active'     => (bool)$p->active,
                'clave_sat'  => $p->clave_sat,
                'rating'        => (float)($p->rating ?? 0),
                'reviews_count' => (int)($p->reviews_count ?? 0),
                'free_shipping' => (bool)($p->free_shipping ?? false),
                'badge'         => $p->badge ?? null,
            ];
        });

        if ($page->count()) \Log::info('apiIndex sample', ['first' => $page->items()[0]]);

        return response()->json($page);
    }

    /** GET /api/products/{product} -> detalle JSON */
    public function apiShow(Product $product)
    {
        return response()->json([
            'id'          => $product->id,
            'name'        => $product->name,
            'sku'         => $product->sku,
            'brand'       => $product->brand,
            'category'    => $product->category,
            'price'       => $product->price,
            'list_price'  => $product->market_price ?? null,
            'description' => $product->description,
            'image_src'   => $product->image_src,
            'active'      => (bool)$product->active,
            'clave_sat'   => $product->clave_sat,
        ]);
    }
}
