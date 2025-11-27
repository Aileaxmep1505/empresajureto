<?php 

namespace App\Http\Controllers;

use App\Models\Licitacion;
use App\Models\LicitacionArchivo;
use App\Models\LicitacionEvento;
use App\Models\AgendaEvent;
use App\Models\LicitacionContabilidad;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class LicitacionWizardController extends Controller
{
    /**
     * Listado simple de licitaciones.
     */
    public function index()
    {
        $licitaciones = Licitacion::orderByDesc('id')->paginate(15);
        return view('licitaciones.index', compact('licitaciones'));
    }

    /**
     * Resumen / detalle de licitación.
     */
    public function show(Licitacion $licitacion)
    {
        $licitacion->load([
            'archivos',
            'preguntas',
            'checklistCompras',
            'checklistFacturacion',
            'contabilidad',
        ]);

        return view('licitaciones.show', compact('licitacion'));
    }

    /* ============================================================
     * PASO 1: Crear licitación (datos básicos)
     * ============================================================ */

    public function createStep1()
    {
        return view('licitaciones.step1');
    }

    public function storeStep1(Request $request)
    {
        $data = $request->validate([
            'titulo'                   => 'required|string|max:255',
            'descripcion'              => 'nullable|string',

            // muchas fechas
            'fechas_convocatoria'      => 'required|array|min:1',
            'fechas_convocatoria.*'    => 'date_format:Y-m-d',

            // modalidad con mixta
            'modalidad'                => 'required|in:presencial,en_linea,mixta',
        ]);

        $fechas = collect($data['fechas_convocatoria'])
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $fechaPrincipal = $fechas[0] ?? null;

        $licitacion = Licitacion::create([
            'titulo'              => $data['titulo'],
            'descripcion'         => $data['descripcion'] ?? null,
            'fechas_convocatoria' => $fechas,
            'fecha_convocatoria'  => $fechaPrincipal, // compatibilidad
            'modalidad'           => $data['modalidad'],
            'estatus'             => 'borrador',
            'current_step'        => 1,
            'created_by'          => Auth::id(),
        ]);

        return redirect()->route('licitaciones.edit.step2', $licitacion);
    }

    /**
     * Helper: reemplaza archivo por tipo (borra el anterior y guarda el nuevo)
     */
    protected function replaceArchivo(
        Licitacion $licitacion,
        string $tipo,
        UploadedFile $file,
        string $folder
    ): LicitacionArchivo {
        $prev = $licitacion->archivos()->where('tipo', $tipo)->latest()->first();

        if ($prev) {
            if ($prev->path && Storage::disk('public')->exists($prev->path)) {
                Storage::disk('public')->delete($prev->path);
            }
            $prev->delete();
        }

        $path = $file->store("licitaciones/{$licitacion->id}/{$folder}", 'public');

        return LicitacionArchivo::create([
            'licitacion_id'   => $licitacion->id,
            'tipo'            => $tipo,
            'path'            => $path,
            'nombre_original' => $file->getClientOriginalName(),
            'mime_type'       => $file->getClientMimeType(),
            'uploaded_by'     => Auth::id(),
        ]);
    }

    /* ============================================================
     * PASO 2: Convocatoria + Acta antecedente + Muestras
     * ============================================================ */

    public function editStep2(Licitacion $licitacion)
    {
        $licitacion->load('archivos');

        $convocatoria = $licitacion->archivos
            ->where('tipo', 'convocatoria')
            ->sortByDesc('id')
            ->first();

        $antecedente = $licitacion->archivos
            ->where('tipo', 'acta_antecedente')
            ->sortByDesc('id')
            ->first();

        return view('licitaciones.step2', compact('licitacion', 'convocatoria', 'antecedente'));
    }

    public function updateStep2(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            // convocatoria: requerida solo si no existe previa
            'archivo_convocatoria'   => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx',

            // antecedente
            'acta_antecedente'       => 'nullable|file|mimes:pdf',

            // muestras
            'requiere_muestras'      => 'nullable|boolean',
            'fecha_entrega_muestras' => 'nullable|date_format:Y-m-d\TH:i',
            'lugar_entrega_muestras' => 'nullable|string|max:255',
        ]);

        $yaHayConv = $licitacion->archivos()->where('tipo', 'convocatoria')->exists();
        if (!$request->hasFile('archivo_convocatoria') && !$yaHayConv) {
            return back()
                ->withErrors(['archivo_convocatoria' => 'Debes subir el documento de convocatoria.'])
                ->withInput();
        }

        $yaHayAnte = $licitacion->archivos()->where('tipo', 'acta_antecedente')->exists();
        if (!$request->hasFile('acta_antecedente') && !$yaHayAnte) {
            return back()
                ->withErrors(['acta_antecedente' => 'Debes subir el acta de antecedente en PDF.'])
                ->withInput();
        }

        // 1) convocatoria (si viene)
        if ($request->hasFile('archivo_convocatoria')) {
            $this->replaceArchivo(
                $licitacion,
                'convocatoria',
                $request->file('archivo_convocatoria'),
                'convocatoria'
            );
        }

        // 2) antecedente (si viene)
        if ($request->hasFile('acta_antecedente')) {
            $this->replaceArchivo(
                $licitacion,
                'acta_antecedente',
                $request->file('acta_antecedente'),
                'acta_antecedente'
            );
        }

        // 3) muestras
        $requiere = (bool)($data['requiere_muestras'] ?? false);

        $fechaMuestras = !empty($data['fecha_entrega_muestras'])
            ? Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_entrega_muestras'])
            : null;

        $current = (int)($licitacion->current_step ?? 1);

        $licitacion->update([
            'requiere_muestras'      => $requiere,
            'fecha_entrega_muestras' => $fechaMuestras,
            'lugar_entrega_muestras' => $data['lugar_entrega_muestras'] ?? null,
            'current_step'           => max($current, 2),
        ]);

        // limpiar evento previo de muestras
        $prev = $licitacion->eventos()->where('tipo', 'entrega_muestras')->get();
        foreach ($prev as $e) {
            if ($e->agenda_event_id) {
                AgendaEvent::where('id', $e->agenda_event_id)->delete();
            }
            $e->delete();
        }

        // crear evento muestras si aplica
        if ($requiere && $fechaMuestras) {
            $eventMuestras = new AgendaEvent([
                'title'       => 'Entrega de muestras: '.$licitacion->titulo,
                'description' => 'Entrega de muestras'.($licitacion->lugar_entrega_muestras ? ' en '.$licitacion->lugar_entrega_muestras : ''),
                'start_at'    => $fechaMuestras,
                'remind_offset_minutes' => 120,
                'repeat_rule' => 'none',
                'timezone'    => config('app.timezone'),
            ]);
            $eventMuestras->computeNextReminder();
            $eventMuestras->save();

            LicitacionEvento::create([
                'licitacion_id'   => $licitacion->id,
                'agenda_event_id' => $eventMuestras->id,
                'tipo'            => 'entrega_muestras',
            ]);
        }

        return redirect()->route('licitaciones.edit.step3', $licitacion);
    }

    /* ============================================================
     * PASO 3: Junta de aclaraciones + correos recordatorio
     * ============================================================ */

    public function editStep3(Licitacion $licitacion)
    {
        // emails guardados en licitación
        $recordatorioEmails = $licitacion->recordatorio_emails ?? [];

        // fallback: si no hay guardados pero existe evento previo
        if (empty($recordatorioEmails)) {
            $link = $licitacion->eventos()
                ->where('tipo', 'recordatorio_preguntas')
                ->latest('id')
                ->first();

            $agenda = $link ? AgendaEvent::find($link->agenda_event_id) : null;

            if ($agenda && $agenda->attendee_email) {
                $recordatorioEmails = collect(explode(',', $agenda->attendee_email))
                    ->map(fn($e) => trim($e))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        return view('licitaciones.step3', compact('licitacion', 'recordatorioEmails'));
    }

    public function updateStep3(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'fecha_junta_aclaraciones' => 'required|date_format:Y-m-d\TH:i',
            'fecha_limite_preguntas'   => 'nullable|date_format:Y-m-d\TH:i',
            'lugar_junta'              => 'nullable|string|max:255',
            'link_junta'               => 'nullable|string|max:255',

            // correos recordatorio
            'recordatorio_emails'      => 'required|array|min:1',
            'recordatorio_emails.*'    => 'nullable|email',
        ]);

        $fechaJunta = Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_junta_aclaraciones']);

        $fechaLimite = !empty($data['fecha_limite_preguntas'])
            ? Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_limite_preguntas'])
            : $fechaJunta;

        $emailsArr = collect($data['recordatorio_emails'])
            ->map(fn($e) => trim((string)$e))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $current = (int)($licitacion->current_step ?? 1);

        $licitacion->update([
            'fecha_junta_aclaraciones' => $fechaJunta,
            'fecha_limite_preguntas'   => $fechaLimite,
            'lugar_junta'              => $data['lugar_junta'] ?? null,
            'link_junta'               => $data['link_junta'] ?? null,
            'recordatorio_emails'      => $emailsArr,   // ✅ se guardan en la licitación
            'current_step'             => max($current, 3),
        ]);

        // limpiar eventos viejos
        $tipos = ['junta_aclaraciones', 'recordatorio_preguntas'];
        $prev = $licitacion->eventos()->whereIn('tipo', $tipos)->get();
        foreach ($prev as $e) {
            if ($e->agenda_event_id) {
                AgendaEvent::where('id', $e->agenda_event_id)->delete();
            }
            $e->delete();
        }

        // evento principal junta
        $eventJunta = new AgendaEvent([
            'title'       => 'Junta de aclaraciones: '.$licitacion->titulo,
            'description' => 'Junta de aclaraciones de la licitación '.$licitacion->titulo,
            'start_at'    => $fechaJunta,
            'remind_offset_minutes' => 60,
            'repeat_rule' => 'none',
            'timezone'    => config('app.timezone'),
        ]);
        $eventJunta->computeNextReminder();
        $eventJunta->save();

        LicitacionEvento::create([
            'licitacion_id'   => $licitacion->id,
            'agenda_event_id' => $eventJunta->id,
            'tipo'            => 'junta_aclaraciones',
        ]);

        // recordatorio preguntas
        $emailsStr = implode(',', $emailsArr);
        $startRecordatorio = $fechaJunta->copy()->subDays(2);

        $eventRecordatorio = new AgendaEvent([
            'title'       => 'Agregar preguntas: '.$licitacion->titulo,
            'description' => 'Favor de agregar preguntas para la junta de aclaraciones de la licitación '.$licitacion->titulo,
            'start_at'    => $startRecordatorio,
            'remind_offset_minutes' => 0,
            'repeat_rule' => 'none',
            'timezone'    => config('app.timezone'),
            'attendee_name'  => null,
            'attendee_email' => $emailsStr,
            'send_email'     => true,
            'send_whatsapp'  => false,
        ]);
        $eventRecordatorio->computeNextReminder();
        $eventRecordatorio->save();

        LicitacionEvento::create([
            'licitacion_id'   => $licitacion->id,
            'agenda_event_id' => $eventRecordatorio->id,
            'tipo'            => 'recordatorio_preguntas',
        ]);

        // paso 4 es preguntas (otro controlador), aquí brincamos al 5
        return redirect()->route('licitaciones.edit.step5', $licitacion);
    }

    /* ============================================================
     * PASO 5: Apertura de propuesta + Acta junta de aclaraciones
     * ============================================================ */

    public function editStep5(Licitacion $licitacion)
    {
        // Cargamos archivos para saber si ya hay un acta de junta de aclaraciones
        $licitacion->load('archivos');

        $actaJunta = $licitacion->archivos()
            ->where('tipo', 'acta_junta_aclaraciones')
            ->latest()
            ->first();

        return view('licitaciones.step5', compact('licitacion', 'actaJunta'));
    }

    public function updateStep5(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'fecha_apertura_propuesta'   => 'required|date_format:Y-m-d\TH:i',
            'acta_junta_aclaraciones'    => 'nullable|file|mimes:pdf',
        ]);

        $fechaApertura = Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_apertura_propuesta']);

        $current = (int)($licitacion->current_step ?? 1);

        $licitacion->update([
            'fecha_apertura_propuesta' => $fechaApertura,
            'current_step'             => max($current, 5),
        ]);

        // Si subieron acta de junta de aclaraciones, la guardamos (reemplaza la anterior)
        if ($request->hasFile('acta_junta_aclaraciones')) {
            $this->replaceArchivo(
                $licitacion,
                'acta_junta_aclaraciones',
                $request->file('acta_junta_aclaraciones'),
                'acta_junta_aclaraciones'
            );
        }

        // Limpiar eventos previos de apertura (para no duplicar en agenda)
        $prev = $licitacion->eventos()->where('tipo', 'apertura_propuesta')->get();
        foreach ($prev as $e) {
            if ($e->agenda_event_id) {
                AgendaEvent::where('id', $e->agenda_event_id)->delete();
            }
            $e->delete();
        }

        // Crear evento de apertura
        $eventApertura = new AgendaEvent([
            'title'       => 'Apertura de propuesta: '.$licitacion->titulo,
            'description' => 'Apertura de propuesta de la licitación '.$licitacion->titulo,
            'start_at'    => $fechaApertura,
            'remind_offset_minutes' => 60,
            'repeat_rule' => 'none',
            'timezone'    => config('app.timezone'),
        ]);
        $eventApertura->computeNextReminder();
        $eventApertura->save();

        LicitacionEvento::create([
            'licitacion_id'   => $licitacion->id,
            'agenda_event_id' => $eventApertura->id,
            'tipo'            => 'apertura_propuesta',
        ]);

        return redirect()->route('licitaciones.edit.step6', $licitacion);
    }

    /* ============================================================
     * PASO 6: Acta de apertura (opcional) + avanzar
     * ============================================================ */

    public function editStep6(Licitacion $licitacion)
    {
        // Para tener ya el archivo cargado y evitar N+1
        $actaApertura = $licitacion->archivos()
            ->where('tipo', 'acta_apertura')
            ->latest()
            ->first();

        return view('licitaciones.step6', compact('licitacion', 'actaApertura'));
    }

    public function updateStep6(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'fecha_acta_apertura' => 'nullable|date',          // fecha opcional tipo date (Y-m-d)
            'acta_apertura'       => 'nullable|file|mimes:pdf' // archivo opcional
        ]);

        // Si sube un nuevo PDF, reemplazamos el anterior
        if ($request->hasFile('acta_apertura')) {
            $this->replaceArchivo(
                $licitacion,
                'acta_apertura',                          // tipo
                $request->file('acta_apertura'),
                'acta_apertura'                           // carpeta
            );
        }

        // Guardar la fecha en la licitación
        $licitacion->update([
            'fecha_acta_apertura' => $data['fecha_acta_apertura'] ?? $licitacion->fecha_acta_apertura,
            'current_step'        => 6,
        ]);

        return redirect()->route('licitaciones.edit.step7', $licitacion);
    }

    /* ============================================================
     * PASO 7: Fallo + resultado
     * ============================================================ */

    public function editStep7(Licitacion $licitacion)
    {
        return view('licitaciones.step7', compact('licitacion'));
    }

    public function updateStep7(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            // Solo el archivo de fallo en este paso
            'archivo_fallo'       => 'nullable|file|mimes:pdf',

            // Resultado oficial del procedimiento
            'resultado'           => 'required|in:ganado,no_ganado',

            // Fecha del fallo (la que viene en el acta oficial)
            'fecha_fallo'         => 'required|date',

            // Notas internas / motivos / comentarios
            'observaciones_fallo' => 'nullable|string',
        ]);

        // Si sube el PDF de fallo, reemplazamos el anterior
        if ($request->hasFile('archivo_fallo')) {
            $this->replaceArchivo(
                $licitacion,
                'fallo',                           // tipo
                $request->file('archivo_fallo'),
                'fallo'                            // carpeta
            );
        }

        // Asegurar que no retroceda el current_step
        $current = (int) ($licitacion->current_step ?? 1);

        $licitacion->update([
            'resultado'           => $data['resultado'],
            'fecha_fallo'         => $data['fecha_fallo'],
            'observaciones_fallo' => $data['observaciones_fallo'] ?? null,
            'current_step'        => max($current, 7),
            'estatus'             => $data['resultado'] === 'ganado'
                                        ? 'en_proceso'   // sigue el flujo (contrato, etc.)
                                        : 'cerrado',     // se terminó la licitación
        ]);

        // Si no se ganó, regresamos al show y cerramos flujo
        if ($data['resultado'] === 'no_ganado') {
            return redirect()
                ->route('licitaciones.show', $licitacion)
                ->with('info', 'La licitación no se ganó, flujo cerrado.');
        }

        // Si se ganó, seguimos al paso 8 (presentación del fallo / contrato, etc.)
        return redirect()->route('licitaciones.edit.step8', $licitacion);
    }

    /* ============================================================
     * PASO 8: Presentación del fallo
     * ============================================================ */

    public function editStep8(Licitacion $licitacion)
    {
        abort_unless($licitacion->resultado === 'ganado', 403);
        return view('licitaciones.step8', compact('licitacion'));
    }

    public function updateStep8(Request $request, Licitacion $licitacion)
    {
        abort_unless($licitacion->resultado === 'ganado', 403);

        $data = $request->validate([
            'fecha_presentacion_fallo' => 'required|date_format:Y-m-d\TH:i',
            'lugar_presentacion_fallo' => 'required|string|max:255',
            'docs_presentar_fallo'     => 'nullable|string',
        ]);

        $fechaPresentacion = Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_presentacion_fallo']);

        $current = (int)($licitacion->current_step ?? 1);

        $licitacion->update([
            'fecha_presentacion_fallo' => $fechaPresentacion,
            'lugar_presentacion_fallo' => $data['lugar_presentacion_fallo'],
            'docs_presentar_fallo'     => $data['docs_presentar_fallo'] ?? null,
            'current_step'             => max($current, 8),
        ]);

        $event = new AgendaEvent([
            'title'       => 'Presentar fallo: '.$licitacion->titulo,
            'description' => "Presentación del fallo en {$data['lugar_presentacion_fallo']}. Docs: ".$data['docs_presentar_fallo'],
            'start_at'    => $fechaPresentacion,
            'remind_offset_minutes' => 120,
            'repeat_rule' => 'none',
            'timezone'    => config('app.timezone'),
        ]);
        $event->computeNextReminder();
        $event->save();

        LicitacionEvento::create([
            'licitacion_id'   => $licitacion->id,
            'agenda_event_id' => $event->id,
            'tipo'            => 'presentacion_fallo',
        ]);

        return redirect()->route('licitaciones.edit.step9', $licitacion);
    }

    /* ============================================================
     * PASO 9: Contrato + fechas emisión, fianza y cobros
     * ============================================================ */

    public function editStep9(Licitacion $licitacion)
    {
        abort_unless($licitacion->resultado === 'ganado', 403);
        return view('licitaciones.step9', compact('licitacion'));
    }

    public function updateStep9(Request $request, Licitacion $licitacion)
    {
        abort_unless($licitacion->resultado === 'ganado', 403);

        $data = $request->validate([
            'contrato'               => 'required|file|mimes:pdf',
            'fecha_emision_contrato' => 'required|date',
            'fecha_fianza'           => 'required|date',

            // Nuevo: tipo de fianza
            'tipo_fianza'            => 'nullable|in:cumplimiento,vicios_ocultos',

            // Observaciones contrato/fianza
            'observaciones_contrato' => 'nullable|string',

            // Fechas de cobro múltiples
            'fechas_cobro'           => 'nullable|array',
            'fechas_cobro.*'         => 'nullable|date',
        ]);

        // Guardar / reemplazar contrato
        $this->replaceArchivo($licitacion, 'contrato', $request->file('contrato'), 'contrato');

        $current = (int)($licitacion->current_step ?? 1);

        // Normalizar fechas de cobro (limpia vacíos, ordena, deja Y-m-d)
        $fechasCobro = collect($data['fechas_cobro'] ?? [])
            ->filter()
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Actualizar licitación con los nuevos campos
        $licitacion->update([
            'fecha_emision_contrato' => $data['fecha_emision_contrato'],
            'fecha_fianza'           => $data['fecha_fianza'],
            'tipo_fianza'            => $data['tipo_fianza'] ?? null,
            'observaciones_contrato' => $data['observaciones_contrato'] ?? null,
            'fechas_cobro'           => $fechasCobro,
            'current_step'           => max($current, 9),
        ]);

        // Limpiar eventos previos de fianza / cobro para no duplicar
        $prevEventos = $licitacion->eventos()
            ->whereIn('tipo', ['fianza', 'cobro'])
            ->get();

        foreach ($prevEventos as $e) {
            if ($e->agenda_event_id) {
                AgendaEvent::where('id', $e->agenda_event_id)->delete();
            }
            $e->delete();
        }

        // Evento de fianza
        $fechaFianza = Carbon::parse($data['fecha_fianza']);

        $descripcionFianza = 'Recordatorio para revisar/entregar fianza del contrato de la licitación '.$licitacion->titulo;
        if (!empty($data['tipo_fianza'])) {
            $descripcionFianza .= ' (tipo: '.str_replace('_', ' ', $data['tipo_fianza']).')';
        }

        $eventFianza = new AgendaEvent([
            'title'       => 'Entrega de fianza: '.$licitacion->titulo,
            'description' => $descripcionFianza,
            'start_at'    => $fechaFianza,
            'remind_offset_minutes' => 1440, // 1 día antes
            'repeat_rule' => 'none',
            'timezone'    => config('app.timezone'),
        ]);
        $eventFianza->computeNextReminder();
        $eventFianza->save();

        LicitacionEvento::create([
            'licitacion_id'   => $licitacion->id,
            'agenda_event_id' => $eventFianza->id,
            'tipo'            => 'fianza',
        ]);

        // Crear eventos de cobro en agenda (uno por fecha)
        foreach ($fechasCobro as $fechaCobro) {
            $fecha = Carbon::parse($fechaCobro);

            $eventCobro = new AgendaEvent([
                'title'       => 'Cobro contrato: '.$licitacion->titulo,
                'description' => 'Fecha objetivo de cobro de la licitación '.$licitacion->titulo,
                'start_at'    => $fecha,
                'remind_offset_minutes' => 1440, // 1 día antes
                'repeat_rule' => 'none',
                'timezone'    => config('app.timezone'),
            ]);
            $eventCobro->computeNextReminder();
            $eventCobro->save();

            LicitacionEvento::create([
                'licitacion_id'   => $licitacion->id,
                'agenda_event_id' => $eventCobro->id,
                'tipo'            => 'cobro',
            ]);
        }

        return redirect()->route('licitaciones.checklist.compras.edit', $licitacion);
    }

    /* ============================================================
     * PASO 12: Contabilidad (estado financiero final)
     * ============================================================ */

    /**
     * Editar contabilidad de la licitación (vista paso 12).
     */
    public function contabilidadEdit(Licitacion $licitacion)
    {
        $contabilidad = $licitacion->contabilidad; // puede ser null
        return view('licitaciones.contabilidad', compact('licitacion', 'contabilidad'));
    }

    /**
     * Guardar contabilidad de la licitación.
     * Ruta sugerida: licitaciones.contabilidad.store
     */
    public function contabilidadStore(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'monto_inversion_estimado'   => 'nullable|numeric|min:0',
            'costo_total'                => 'nullable|numeric|min:0',
            'utilidad_estimada'          => 'nullable|numeric',

            'detalle_costos'             => 'nullable|array',
            'detalle_costos.productos'   => 'nullable|numeric|min:0',
            'detalle_costos.renta'       => 'nullable|numeric|min:0',
            'detalle_costos.luz'         => 'nullable|numeric|min:0',
            'detalle_costos.agua'        => 'nullable|numeric|min:0',
            'detalle_costos.nominas'     => 'nullable|numeric|min:0',
            'detalle_costos.imss'        => 'nullable|numeric|min:0',
            'detalle_costos.gasolina'    => 'nullable|numeric|min:0',
            'detalle_costos.viaticos'    => 'nullable|numeric|min:0',
            'detalle_costos.casetas'     => 'nullable|numeric|min:0',
            'detalle_costos.pagos_gobierno'          => 'nullable|numeric|min:0',
            'detalle_costos.mantenimiento_camionetas'=> 'nullable|numeric|min:0',
            'detalle_costos.libre_1'     => 'nullable|numeric|min:0',
            'detalle_costos.libre_2'     => 'nullable|numeric|min:0',
            'detalle_costos.libre_1_label' => 'nullable|string|max:255',
            'detalle_costos.libre_2_label' => 'nullable|string|max:255',

            'notas'                      => 'nullable|string',
        ]);

        $detalle = $data['detalle_costos'] ?? [];

        // Helper para leer monto numérico
        $num = function ($key) use ($detalle) {
            if (!array_key_exists($key, $detalle)) {
                return 0.0;
            }
            return is_numeric($detalle[$key]) ? (float)$detalle[$key] : 0.0;
        };

        $montoLicitado = isset($data['monto_inversion_estimado'])
            ? (float)$data['monto_inversion_estimado']
            : 0.0;

        $gastoProductos = $num('productos');

        $keysOperativos = [
            'renta',
            'luz',
            'agua',
            'nominas',
            'imss',
            'gasolina',
            'viaticos',
            'casetas',
            'pagos_gobierno',
            'mantenimiento_camionetas',
            'libre_1',
            'libre_2',
        ];

        $gastosOperativos = 0.0;
        foreach ($keysOperativos as $k) {
            $gastosOperativos += $num($k);
        }

        $costoTotal = $gastoProductos + $gastosOperativos;
        $utilidad   = $montoLicitado - $costoTotal;

        // Guardar / actualizar registro de contabilidad
        /** @var LicitacionContabilidad $cont */
        $cont = $licitacion->contabilidad()->firstOrNew([]);

        $cont->monto_inversion_estimado = $montoLicitado;
        $cont->costo_total              = $costoTotal;
        $cont->utilidad_estimada        = $utilidad;
        $cont->detalle_costos           = $detalle;
        $cont->notas                    = $data['notas'] ?? null;
        $cont->save();

        // Actualizar licitación como cerrada (último paso del flujo)
        $current = (int)($licitacion->current_step ?? 1);

        $licitacion->update([
            'current_step' => max($current, 12),
            'estatus'      => 'cerrado',
        ]);

        return redirect()
            ->route('licitaciones.show', $licitacion)
            ->with('success', 'Estado financiero guardado correctamente. La licitación se marcó como cerrada.');
    }

    /**
     * PDF de estado financiero (resumen contable).
     * Ruta sugerida: licitaciones.contabilidad.pdf
     */
    public function contabilidadPdf(Licitacion $licitacion)
    {
        $licitacion->load(['contabilidad']);

        $contabilidad = $licitacion->contabilidad;

        $pdf = PDF::loadView('licitaciones.pdf.contabilidad', [
                'licitacion'   => $licitacion,
                'contabilidad' => $contabilidad,
            ])
            ->setPaper('letter', 'portrait');

        $filename = 'Licitacion_'.$licitacion->id.'_estado_financiero.pdf';

        return $pdf->download($filename);
    }

    /* ============================================================
     * RESUMEN GENERAL EN PDF
     * ============================================================ */

    public function resumenPdf(Licitacion $licitacion)
    {
        // Cargar relaciones para el resumen
        $licitacion->load([
            'archivos',
            'preguntas.usuario',
            'contabilidad',
        ]);

        $pdf = PDF::loadView('licitaciones.pdf.resumen', [
                'licitacion' => $licitacion,
            ])
            ->setPaper('letter', 'portrait');

        $filename = 'Licitacion_'.$licitacion->id.'_resumen.pdf';

        return $pdf->download($filename);
    }
}
