<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;
use App\Models\LicitacionArchivo;
use App\Models\LicitacionEvento;
use App\Models\AgendaEvent;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

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

            // antecedente ahora aquí
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

        $licitacion->update([
            'requiere_muestras'      => $requiere,
            'fecha_entrega_muestras' => $fechaMuestras,
            'lugar_entrega_muestras' => $data['lugar_entrega_muestras'] ?? null,
            'current_step'           => 2,
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

        $licitacion->update([
            'fecha_junta_aclaraciones' => $fechaJunta,
            'fecha_limite_preguntas'   => $fechaLimite,
            'lugar_junta'              => $data['lugar_junta'] ?? null,
            'link_junta'               => $data['link_junta'] ?? null,
            'recordatorio_emails'      => $emailsArr,   // ✅ se guardan en la licitación
            'current_step'             => 3,
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

        // paso 4 es preguntas (otro controlador), brincamos al 5
        return redirect()->route('licitaciones.edit.step5', $licitacion);
    }

    /* ============================================================
     * PASO 5: Apertura de propuesta
     * ============================================================ */

    public function editStep5(Licitacion $licitacion)
    {
        return view('licitaciones.step5', compact('licitacion'));
    }

    public function updateStep5(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'fecha_apertura_propuesta' => 'required|date_format:Y-m-d\TH:i',
        ]);

        $fechaApertura = Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_apertura_propuesta']);

        $licitacion->update([
            'fecha_apertura_propuesta' => $fechaApertura,
            'current_step'             => 5,
        ]);

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
     * PASO 6: solo avanzar (ya no hay archivo aquí)
     * ============================================================ */

    public function editStep6(Licitacion $licitacion)
    {
        return view('licitaciones.step6', compact('licitacion'));
    }

    public function updateStep6(Request $request, Licitacion $licitacion)
    {
        $licitacion->update([
            'current_step' => 6,
        ]);

        return redirect()->route('licitaciones.edit.step7', $licitacion);
    }

    /* ============================================================
     * PASO 7: Acta apertura + fallo + resultado
     * ============================================================ */

    public function editStep7(Licitacion $licitacion)
    {
        return view('licitaciones.step7', compact('licitacion'));
    }

    public function updateStep7(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'acta_apertura'       => 'nullable|file|mimes:pdf',
            'archivo_fallo'       => 'nullable|file|mimes:pdf',
            'resultado'           => 'required|in:ganado,no_ganado',
            'fecha_fallo'         => 'required|date',
            'observaciones_fallo' => 'nullable|string',
        ]);

        if ($request->hasFile('acta_apertura')) {
            $this->replaceArchivo($licitacion, 'acta_apertura', $request->file('acta_apertura'), 'acta_apertura');
        }

        if ($request->hasFile('archivo_fallo')) {
            $this->replaceArchivo($licitacion, 'fallo', $request->file('archivo_fallo'), 'fallo');
        }

        $licitacion->update([
            'resultado'           => $data['resultado'],
            'fecha_fallo'         => $data['fecha_fallo'],
            'observaciones_fallo' => $data['observaciones_fallo'] ?? null,
            'current_step'        => 7,
            'estatus'             => $data['resultado'] === 'ganado' ? 'en_proceso' : 'cerrado',
        ]);

        if ($data['resultado'] === 'no_ganado') {
            return redirect()
                ->route('licitaciones.show', $licitacion)
                ->with('info', 'La licitación no se ganó, flujo cerrado.');
        }

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

        $licitacion->update([
            'fecha_presentacion_fallo' => $fechaPresentacion,
            'lugar_presentacion_fallo' => $data['lugar_presentacion_fallo'],
            'docs_presentar_fallo'     => $data['docs_presentar_fallo'] ?? null,
            'current_step'             => 8,
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
     * PASO 9: Contrato + fechas emisión y fianza
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
        ]);

        $this->replaceArchivo($licitacion, 'contrato', $request->file('contrato'), 'contrato');

        $licitacion->update([
            'fecha_emision_contrato' => $data['fecha_emision_contrato'],
            'fecha_fianza'           => $data['fecha_fianza'],
            'current_step'           => 9,
        ]);

        $fechaFianza = Carbon::parse($data['fecha_fianza']);

        $eventFianza = new AgendaEvent([
            'title'       => 'Entrega de fianza: '.$licitacion->titulo,
            'description' => 'Recordatorio para revisar/entregar fianza del contrato de la licitación '.$licitacion->titulo,
            'start_at'    => $fechaFianza,
            'remind_offset_minutes' => 1440,
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

        return redirect()->route('licitaciones.checklist.compras.edit', $licitacion);
    }
}
