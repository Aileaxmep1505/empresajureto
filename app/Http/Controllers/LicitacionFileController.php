<?php

namespace App\Http\Controllers;

use App\Models\LicitacionFile;
use App\Models\ItemOriginal;
use App\Models\ItemGlobal;
use App\Services\LicitacionOpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LicitacionFileController extends Controller
{
    /**
     * Servicio de OpenAI dedicado a este módulo.
     *
     * @var \App\Services\LicitacionOpenAIService
     */
    protected $openAIService;

    public function __construct(LicitacionOpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Mostrar listado de archivos de licitación (módulo AI).
     */
    public function index()
    {
        // Usamos $licitaciones para que coincida con la vista index
        $licitaciones = LicitacionFile::latest()->paginate(20);

        // Vista: resources/views/licitaciones_ai/index.blade.php
        return view('licitaciones_ai.index', compact('licitaciones'));
    }

    /**
     * Formulario para subir archivo de licitación (módulo AI).
     */
    public function create()
    {
        // Vista: resources/views/licitaciones_ai/create.blade.php
        return view('licitaciones_ai.create');
    }

    /**
     * Guardar archivo subido y registrar en la BD.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx'],
        ]);

        $file = $request->file('file');

        $ruta = $file->store('licitaciones', 'public');

        $licitacionFile = LicitacionFile::create([
            'nombre_original' => $file->getClientOriginalName(),
            'ruta'            => $ruta,
            'mime_type'       => $file->getClientMimeType(),
            'estado'          => 'pendiente',
        ]);

        // Procesar inmediatamente (puedes mover esto a un Job si quieres)
        $this->procesar($licitacionFile);

        return redirect()
            ->route('licitaciones-ai.show', $licitacionFile)
            ->with('success', 'Archivo subido y procesado correctamente.');
    }

    /**
     * Mostrar detalle de un archivo (y sus items).
     */
    public function show(LicitacionFile $licitacionFile)
    {
        $items = $licitacionFile->itemsOriginales()
            ->orderBy('requisicion')
            ->get();

        // Vista: resources/views/licitaciones_ai/show.blade.php
        return view('licitaciones_ai.show', compact('licitacionFile', 'items'));
    }

    /**
     * Procesar archivo: extraer items con OpenAI (vía servicio).
     * Si se acerca al límite de tiempo de PHP, se corta y se marca como procesado_parcial
     * para que al menos tengas lo que ya alcanzó a extraer.
     */
    public function procesar(LicitacionFile $licitacionFile)
    {
        $licitacionFile->update([
            'estado'        => 'procesando',
            'error_mensaje' => null,
        ]);

        // Medición de tiempo para evitar llegar al max_execution_time (600s)
        $inicio     = microtime(true);
        $maxSeconds = 480; // 8 minutos aprox; ajustable según tu servidor

        try {
            // 1) Leer el archivo (PDF / Word) y sacar texto/tablas
            $rutaAbsoluta = Storage::disk('public')->path($licitacionFile->ruta);

            $bloquesTablas = $this->extraerTablasDesdeDocumento($rutaAbsoluta);

            // 2) Por cada bloque, llamar al servicio OpenAI para convertir a JSON estructurado
            $totalItems       = 0;
            $bloquesProcesados = 0;

            foreach ($bloquesTablas as $index => $bloque) {

                // Si ya nos acercamos mucho al límite, cortamos aquí y guardamos progreso
                $elapsed = microtime(true) - $inicio;
                if ($elapsed > $maxSeconds) {
                    $licitacionFile->update([
                        'estado'        => 'procesado_parcial',
                        'total_items'   => $totalItems,
                        'error_mensaje' => 'Procesamiento detenido por límite de tiempo. Puedes reintentar para seguir procesando más bloques.',
                    ]);

                    // No llamamos a fusionarItemsGlobales aquí para evitar más tiempo.
                    return;
                }

                $items = $this->openAIService->extraerItemsDesdeTabla(
                    $bloque['texto_tabla'],
                    $bloque['requisicion']
                );

                foreach ($items as $itemData) {
                    ItemOriginal::create([
                        'licitacion_file_id' => $licitacionFile->id,
                        'requisicion'        => $itemData['requisicion'],
                        'partida'            => $itemData['partida'] ?? null,
                        'clave_verificacion' => $itemData['clave_verificacion'] ?? null,
                        'descripcion_bien'   => $itemData['descripcion'],
                        'especificaciones'   => $itemData['especificaciones'] ?? null,
                        'cantidad'           => $itemData['cantidad'],
                        'unidad_medida'      => $itemData['unidad'],
                    ]);

                    $totalItems++;
                }

                $bloquesProcesados++;
            }

            // Antes de fusionar, revisamos otra vez el tiempo
            $elapsed = microtime(true) - $inicio;
            if ($elapsed > $maxSeconds) {
                $licitacionFile->update([
                    'estado'        => 'procesado_parcial',
                    'total_items'   => $totalItems,
                    'error_mensaje' => 'Items extraídos, pero la fusión global se detuvo por límite de tiempo. Puedes relanzar la fusión después.',
                ]);

                return;
            }

            // 3) Fusionar todos los items originales en globales
            $this->fusionarItemsGlobales();

            $licitacionFile->update([
                'estado'        => 'procesado',
                'total_items'   => $totalItems,
                'error_mensaje' => null,
            ]);
        } catch (\Throwable $e) {
            $licitacionFile->update([
                'estado'        => 'error',
                'error_mensaje' => $e->getMessage(),
            ]);

            // Aquí puedes loguear el error con Log::error($e);
        }
    }

    /**
     * Ver tabla global (todos los items fusionados).
     */
    public function tablaGlobal()
    {
        $itemsGlobales = ItemGlobal::orderBy('clave_verificacion')
            ->orderBy('descripcion_global')
            ->get();

        // Vista: resources/views/licitaciones_ai/tabla-global.blade.php
        return view('licitaciones_ai.tabla-global', compact('itemsGlobales'));
    }

    /**
     * Actualizar marca y modelo de un item global (desde la tabla global).
     */
    public function actualizarMarcaModelo(ItemGlobal $itemGlobal, Request $request)
    {
        $data = $request->validate([
            'marca'  => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
        ]);

        $itemGlobal->update($data);

        return back()->with('success', 'Item actualizado correctamente.');
    }

    /**
     * Lógica para fusionar todos los items originales en items globales.
     * Versión simple: agrupar por clave_verificacion + unidad_medida,
     * y si no hay clave, por descripción + unidad.
     */
    public function fusionarItemsGlobales()
    {
        DB::transaction(function () {
            $items = ItemOriginal::with('itemGlobal')->get();

            foreach ($items as $item) {
                // Definir "llave" de agrupación básica
                $clave  = $item->clave_verificacion ?: null;
                $unidad = $item->unidad_medida;

                // Si no hay clave, aquí podrías usar una lógica de similitud de texto / embeddings.
                $query = ItemGlobal::query()
                    ->where('unidad_medida', $unidad);

                if ($clave) {
                    $query->where('clave_verificacion', $clave);
                } else {
                    $query->whereNull('clave_verificacion')
                          ->where('descripcion_global', $item->descripcion_bien);
                }

                $global = $query->first();

                if (! $global) {
                    // Crear un nuevo item global
                    $global = ItemGlobal::create([
                        'clave_verificacion'      => $clave,
                        'descripcion_global'      => $item->descripcion_bien,
                        'especificaciones_global' => $item->especificaciones,
                        'unidad_medida'           => $unidad,
                        'cantidad_total'          => 0,
                        'requisiciones'           => [],
                    ]);
                }

                // Sumar cantidad
                $global->cantidad_total = $global->cantidad_total + $item->cantidad;

                // Actualizar lista de requisiciones
                $reqs = $global->requisiciones ?: [];
                if (! in_array($item->requisicion, $reqs)) {
                    $reqs[] = $item->requisicion;
                }
                $global->requisiciones = $reqs;

                $global->save();

                // Relacionar item original con item global
                $item->item_global_id = $global->id;
                $item->save();
            }
        });
    }

    /**
     * === Helpers privados ===
     */

    /**
     * Extrae bloques de tablas desde un PDF/Word.
     * Devuelve bloques pequeños (~40 líneas) por cada REQUISICIÓN.
     *
     * [
     *   ['requisicion' => 'CA-0232-2025', 'texto_tabla' => '...'],
     *   ['requisicion' => 'CA-0232-2025', 'texto_tabla' => '...otro bloque...'],
     *   ...
     * ]
     */
    protected function extraerTablasDesdeDocumento(string $path): array
    {
        // Usa smalot/pdfparser para leer el PDF:
        // composer require smalot/pdfparser
        $parser = new \Smalot\PdfParser\Parser();

        try {
            $pdf  = $parser->parseFile($path);
            $text = $pdf->getText() ?? '';
        } catch (\Throwable $e) {
            return [];
        }

        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $blocks = [];

        // Buscar secciones que empiecen con "REQUISICIÓN: XXXX"
        if (preg_match_all('/REQUISICI[ÓO]N:\s*([A-Z0-9\-\/ ]+)/u', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $total = count($matches[0]);

            for ($i = 0; $i < $total; $i++) {
                $requisicion = trim($matches[1][$i][0]);

                $start = $matches[0][$i][1]; // posición donde inicia "REQUISICIÓN: ..."
                $end   = ($i + 1 < $total)
                    ? $matches[0][$i + 1][1] // siguiente "REQUISICIÓN:"
                    : strlen($text);         // o fin del documento

                $chunk = substr($text, $start, $end - $start);

                // Intentar recortar desde la cabecera de tabla (PARTIDA / CLAVE / BIENES / ...)
                $posCabecera = mb_stripos($chunk, 'PARTIDA', 0, 'UTF-8');
                if ($posCabecera !== false) {
                    $chunk = mb_substr($chunk, $posCabecera);
                }

                // Partir el chunk en grupos de líneas para no mandar todo de golpe
                $lines    = preg_split("/\r\n|\n|\r/", $chunk);
                $buffer   = [];
                $maxLines = 40; // aprox 20–25 renglones por bloque

                foreach ($lines as $line) {
                    $buffer[] = $line;

                    if (count($buffer) >= $maxLines) {
                        $blocks[] = [
                            'requisicion' => $requisicion,
                            'texto_tabla' => implode("\n", $buffer),
                        ];
                        $buffer = [];
                    }
                }

                // Último bloque de esa requisición
                if (! empty($buffer)) {
                    $blocks[] = [
                        'requisicion' => $requisicion,
                        'texto_tabla' => implode("\n", $buffer),
                    ];
                }
            }
        } else {
            // Fallback: si no encontramos "REQUISICIÓN:", cortar todo el texto en bloques
            $lines    = preg_split("/\r\n|\n|\r/", $text);
            $buffer   = [];
            $maxLines = 40;

            foreach ($lines as $line) {
                $buffer[] = $line;

                if (count($buffer) >= $maxLines) {
                    $blocks[] = [
                        'requisicion' => '',
                        'texto_tabla' => implode("\n", $buffer),
                    ];
                    $buffer = [];
                }
            }

            if (! empty($buffer)) {
                $blocks[] = [
                    'requisicion' => '',
                    'texto_tabla' => implode("\n", $buffer),
                ];
            }
        }

        return $blocks;
    }
}
