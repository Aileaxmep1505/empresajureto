<?php

namespace App\Http\Controllers;

use App\Models\LicitacionFile;
use App\Models\ItemOriginal;
use App\Models\ItemGlobal;
use App\Services\LicitacionOpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
        $licitaciones = LicitacionFile::latest()->paginate(20);

        return view('licitaciones_ai.index', compact('licitaciones'));
    }

    /**
     * Formulario para subir archivo de licitación (módulo AI).
     */
    public function create()
    {
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
            ->orderByRaw('CAST(partida AS UNSIGNED) ASC')
            ->orderBy('id')
            ->get();

        return view('licitaciones_ai.show', compact('licitacionFile', 'items'));
    }

    /**
     * Procesar archivo: extraer items con OpenAI (vía servicio).
     * Si se acerca al límite de tiempo de PHP, se corta y se marca como procesado_parcial.
     */
    public function procesar(LicitacionFile $licitacionFile)
    {
        // Hasta 20 minutos para este proceso
        @set_time_limit(1200);

        $licitacionFile->update([
            'estado'        => 'procesando',
            'error_mensaje' => null,
        ]);

        $inicio     = microtime(true);
        $maxSeconds = 1100; // ~18 minutos, margen antes de 1200s

        try {
            // 1) Leer el archivo (PDF / Word) y sacar texto/tablas
            $rutaAbsoluta = Storage::disk('public')->path($licitacionFile->ruta);

            $bloquesTablas = $this->extraerTablasDesdeDocumento($rutaAbsoluta);

            // 2) Por cada bloque, llamar al servicio OpenAI para convertir a JSON estructurado
            $totalItems        = 0;
            $bloquesProcesados = 0;

            foreach ($bloquesTablas as $index => $bloque) {
                $elapsed = microtime(true) - $inicio;
                if ($elapsed > $maxSeconds) {
                    $licitacionFile->update([
                        'estado'        => 'procesado_parcial',
                        'total_items'   => $totalItems,
                        'error_mensaje' => 'Procesamiento detenido por límite de tiempo. Puedes reintentar para seguir procesando más bloques.',
                    ]);

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
        }
    }

    /**
     * Ver tabla global (todos los items fusionados).
     */
    public function tablaGlobal()
    {
        $itemsGlobales = ItemGlobal::with('itemsOriginales')
            ->orderBy('clave_verificacion')
            ->orderBy('descripcion_global')
            ->get();

        return view('licitaciones_ai.tabla-global', compact('itemsGlobales'));
    }

    /**
     * Regenerar tabla global desde cero.
     */
    public function regenerarTablaGlobal()
    {
        $this->fusionarItemsGlobales();

        return redirect()
            ->route('licitaciones-ai.tabla-global')
            ->with('success', 'Tabla global regenerada a partir de todos los items originales.');
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
     * Exportar a Excel TODOS los items originales de una licitación específica.
     * Incluye MARCA y MODELO tomados del ItemGlobal.
     */
    public function exportarExcel(LicitacionFile $licitacionFile)
    {
        $items = $licitacionFile->itemsOriginales()
            ->with('itemGlobal')
            ->orderBy('requisicion')
            ->orderByRaw('CAST(partida AS UNSIGNED) ASC')
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($items as $item) {
            $global = $item->itemGlobal;

            $rows[] = [
                'REQUISICION'        => $item->requisicion,
                'PARTIDA'            => $item->partida,
                'CLAVE_VERIFICACION' => $item->clave_verificacion,
                'DESCRIPCION'        => $item->descripcion_bien,
                'ESPECIFICACIONES'   => $item->especificaciones,
                'CANTIDAD'           => (int) $item->cantidad,
                'UNIDAD'             => $item->unidad_medida,
                'MARCA'              => $global->marca  ?? '',
                'MODELO'             => $global->modelo ?? '',
            ];
        }

        if (empty($rows)) {
            $rows[] = [
                'REQUISICION'        => '',
                'PARTIDA'            => '',
                'CLAVE_VERIFICACION' => '',
                'DESCRIPCION'        => '',
                'ESPECIFICACIONES'   => '',
                'CANTIDAD'           => 0,
                'UNIDAD'             => '',
                'MARCA'              => '',
                'MODELO'             => '',
            ];
        }

        $export = new class($rows) implements FromArray, WithHeadings, WithStyles, ShouldAutoSize {
            protected $rows;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return array_keys($this->rows[0] ?? []);
            }

            public function styles(Worksheet $sheet)
            {
                // Encabezado en negritas
                $highestColumn = $sheet->getHighestColumn();
                $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);

                return [];
            }
        };

        $fileName = 'items_licitacion_' . $licitacionFile->id . '.xlsx';

        return Excel::download($export, $fileName);
    }

    /**
     * Exportar a Excel la TABLA GLOBAL completa.
     * Incluye marca y modelo, encabezados en negritas y autosize.
     */
    public function exportarExcelGlobal()
    {
        $items = ItemGlobal::orderBy('clave_verificacion')
            ->orderBy('descripcion_global')
            ->get();

        $rows = [];

        foreach ($items as $item) {
            $requisiciones = is_array($item->requisiciones)
                ? $item->requisiciones
                : (array) json_decode($item->requisiciones ?? '[]', true);

            $rows[] = [
                'CLAVE_VERIFICACION' => $item->clave_verificacion,
                'DESCRIPCION'        => $item->descripcion_global,
                'ESPECIFICACIONES'   => $item->especificaciones_global,
                'CANTIDAD_TOTAL'     => (int) $item->cantidad_total,
                'UNIDAD'             => $item->unidad_medida,
                'REQUISICIONES'      => implode(', ', $requisiciones),
                'MARCA'              => $item->marca,
                'MODELO'             => $item->modelo,
            ];
        }

        if (empty($rows)) {
            $rows[] = [
                'CLAVE_VERIFICACION' => '',
                'DESCRIPCION'        => '',
                'ESPECIFICACIONES'   => '',
                'CANTIDAD_TOTAL'     => 0,
                'UNIDAD'             => '',
                'REQUISICIONES'      => '',
                'MARCA'              => '',
                'MODELO'             => '',
            ];
        }

        $export = new class($rows) implements FromArray, WithHeadings, WithStyles, ShouldAutoSize {
            protected $rows;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return array_keys($this->rows[0] ?? []);
            }

            public function styles(Worksheet $sheet)
            {
                $highestColumn = $sheet->getHighestColumn();
                $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);

                return [];
            }
        };

        $fileName = 'items_globales.xlsx';

        return Excel::download($export, $fileName);
    }

    /**
     * Fusionar todos los items originales en items globales.
     * - Recalcula SIEMPRE desde cero.
     * - Evita duplicar cantidades si existen renglones repetidos.
     */
    public function fusionarItemsGlobales()
    {
        DB::transaction(function () {
            // 1) Guardar marcas/modelos existentes para no perderlos
            $existentes = ItemGlobal::all()->keyBy(function (ItemGlobal $g) {
                return ($g->clave_verificacion ?? '') . '|' .
                    mb_strtoupper($g->descripcion_global ?? '') . '|' .
                    ($g->unidad_medida ?? '');
            });

            // 2) Romper relaciones y limpiar tabla global
            ItemOriginal::query()->update(['item_global_id' => null]);
            ItemGlobal::query()->delete();

            // 3) Recalcular desde todos los ItemOriginal
            $items = ItemOriginal::orderBy('clave_verificacion')
                ->orderBy('descripcion_bien')
                ->orderBy('requisicion')
                ->orderByRaw('CAST(partida AS UNSIGNED) ASC')
                ->orderBy('id')
                ->get();

            $seenOriginal = [];

            foreach ($items as $item) {
                $originalKey = implode('|', [
                    $item->licitacion_file_id,
                    $item->requisicion,
                    $item->partida,
                    $item->clave_verificacion,
                    mb_strtoupper($item->descripcion_bien ?? ''),
                ]);

                if (isset($seenOriginal[$originalKey])) {
                    $item->item_global_id = $seenOriginal[$originalKey];
                    $item->save();
                    continue;
                }

                $clave       = $item->clave_verificacion ?: null;
                $unidad      = $item->unidad_medida;
                $descripcion = $item->descripcion_bien;

                $groupKey = ($clave ?? '') . '|' . mb_strtoupper($descripcion) . '|' . $unidad;

                $query = ItemGlobal::query()->where('unidad_medida', $unidad);

                if ($clave) {
                    $query->where('clave_verificacion', $clave);
                } else {
                    $query->whereNull('clave_verificacion')
                          ->where('descripcion_global', $descripcion);
                }

                $global = $query->first();

                if (!$global) {
                    $marcaPrev  = null;
                    $modeloPrev = null;

                    if ($existentes->has($groupKey)) {
                        $marcaPrev  = $existentes[$groupKey]->marca ?? null;
                        $modeloPrev = $existentes[$groupKey]->modelo ?? null;
                    }

                    $global = ItemGlobal::create([
                        'clave_verificacion'      => $clave,
                        'descripcion_global'      => $descripcion,
                        'especificaciones_global' => $item->especificaciones,
                        'unidad_medida'           => $unidad,
                        'cantidad_total'          => 0,
                        'requisiciones'           => [],
                        'marca'                   => $marcaPrev,
                        'modelo'                  => $modeloPrev,
                    ]);
                }

                // Cantidad numérica segura
                $cantidad = $item->cantidad;
                if (!is_numeric($cantidad)) {
                    $cantidad = (float) str_replace([',', ' '], ['', ''], $cantidad);
                } else {
                    $cantidad = (float) $cantidad;
                }

                $global->cantidad_total = $global->cantidad_total + $cantidad;

                // Actualizar lista de requisiciones
                $reqs = $global->requisiciones ?: [];
                if (!in_array($item->requisicion, $reqs)) {
                    $reqs[] = $item->requisicion;
                }
                sort($reqs);
                $global->requisiciones = $reqs;

                $global->save();

                $item->item_global_id = $global->id;
                $item->save();
                $seenOriginal[$originalKey] = $global->id;
            }
        });
    }

    /**
     * Extrae bloques de tablas desde un PDF/Word.
     * Devuelve bloques pequeños (~40 líneas) por cada REQUISICIÓN.
     */
    protected function extraerTablasDesdeDocumento(string $path): array
    {
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

                $start = $matches[0][$i][1];
                $end   = ($i + 1 < $total)
                    ? $matches[0][$i + 1][1]
                    : strlen($text);

                $chunk = substr($text, $start, $end - $start);

                $posCabecera = mb_stripos($chunk, 'PARTIDA', 0, 'UTF-8');
                if ($posCabecera !== false) {
                    $chunk = mb_substr($chunk, $posCabecera);
                }

                $lines    = preg_split("/\r\n|\n|\r/", $chunk);
                $buffer   = [];
                $maxLines = 40;

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

                if (!empty($buffer)) {
                    $blocks[] = [
                        'requisicion' => $requisicion,
                        'texto_tabla' => implode("\n", $buffer),
                    ];
                }
            }
        } else {
            // Fallback: cortar todo el texto en bloques genéricos
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

            if (!empty($buffer)) {
                $blocks[] = [
                    'requisicion' => '',
                    'texto_tabla' => implode("\n", $buffer),
                ];
            }
        }

        return $blocks;
    }

    /**
     * Eliminar una licitación AI y todos sus ítems originales.
     */
    public function destroy(LicitacionFile $licitacionFile)
    {
        DB::transaction(function () use ($licitacionFile) {
            ItemOriginal::where('licitacion_file_id', $licitacionFile->id)->delete();
            $licitacionFile->delete();
        });

        return redirect()
            ->route('licitaciones-ai.index')
            ->with('success', 'Licitación eliminada correctamente. Puedes regenerar la tabla global si lo necesitas.');
    }

    /**
     * Actualizar un ítem original desde el modal de edición.
     */
    public function actualizarItemOriginal(ItemOriginal $itemOriginal, Request $request)
    {
        $data = $request->validate([
            'requisicion'        => ['nullable', 'string', 'max:255'],
            'partida'            => ['nullable', 'string', 'max:255'],
            'clave_verificacion' => ['nullable', 'string', 'max:255'],
            'descripcion_bien'   => ['required', 'string'],
            'especificaciones'   => ['nullable', 'string'],
            'cantidad'           => ['required', 'numeric', 'min:0'],
            // importantísimo: no mandar null a la BD si no se edita
            'unidad_medida'      => ['sometimes', 'string', 'max:255'],
        ]);

        // Si no viene unidad_medida o viene vacía, no la actualizamos
        if (!array_key_exists('unidad_medida', $data) || $data['unidad_medida'] === '') {
            unset($data['unidad_medida']);
        }

        $itemOriginal->update($data);

        return back()->with('success', 'Ítem actualizado correctamente.');
    }
    /**
 * Agregar un ítem original manualmente desde el modal
 */
public function storeItemOriginal(LicitacionFile $licitacionFile, Request $request)
{
    $data = $request->validate([
        'requisicion'        => ['nullable', 'string', 'max:255'],
        'partida'            => ['nullable', 'string', 'max:255'],
        'clave_verificacion' => ['nullable', 'string', 'max:255'],
        'descripcion_bien'   => ['required', 'string'],
        'especificaciones'   => ['nullable', 'string'],
        'cantidad'           => ['required', 'integer', 'min:0'],
        'unidad_medida'      => ['required', 'string', 'max:255'], // REQUIRED para evitar null
    ]);

    $data['licitacion_file_id'] = $licitacionFile->id;

    ItemOriginal::create($data);

    // Recalcula globales para que agarre relación/marca/modelo
    $this->fusionarItemsGlobales();

    return back()->with('success', 'Ítem agregado correctamente.');
}

/**
 * Eliminar ítem original (desde botón en fila)
 */
public function destroyItemOriginal(ItemOriginal $itemOriginal)
{
    $itemOriginal->delete();

    // Recalcula globales para quitarlo de la suma y relaciones
    $this->fusionarItemsGlobales();

    return back()->with('success', 'Ítem eliminado correctamente.');
}

}
