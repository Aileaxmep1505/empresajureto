<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;
use App\Models\LicitacionArchivo;
use App\Models\LicitacionEvento;
use App\Models\AgendaEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LicitacionWizardController extends Controller
{
    /**
     * Listado simple de licitaciones (opcional, para el menú principal).
     */
    public function index()
    {
        $licitaciones = Licitacion::orderByDesc('id')->paginate(15);

        return view('licitaciones.index', compact('licitaciones'));
    }

    /**
     * Detalle de una licitación (resumen).
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

    /**
     * PASO 1: Crear licitación (datos básicos)
     */
    public function createStep1()
    {
        return view('licitaciones.step1');
    }

    public function storeStep1(Request $request)
    {
        $data = $request->validate([
            'titulo'            => 'required|string|max:255',
            'descripcion'       => 'nullable|string',
            'fecha_convocatoria'=> 'required|date',
            'modalidad'         => 'required|in:presencial,en_linea',
        ]);

        $licitacion = Licitacion::create([
            ...$data,
            'estatus'      => 'borrador',
            'current_step' => 1,
            'created_by'   => Auth::id(),
        ]);

        return redirect()->route('licitaciones.edit.step2', $licitacion);
    }

    /**
     * PASO 2: Subir archivo de convocatoria
     */
    public function editStep2(Licitacion $licitacion)
    {
        return view('licitaciones.step2', compact('licitacion'));
    }

    public function updateStep2(Request $request, Licitacion $licitacion)
    {
        $request->validate([
            'archivo_convocatoria' => 'required|file|mimes:pdf,doc,docx,xls,xlsx',
        ]);

        $file = $request->file('archivo_convocatoria');
        $path = $file->store('licitaciones/'.$licitacion->id.'/convocatoria', 'public');

        LicitacionArchivo::create([
            'licitacion_id'   => $licitacion->id,
            'tipo'            => 'convocatoria',
            'path'            => $path,
            'nombre_original' => $file->getClientOriginalName(),
            'mime_type'       => $file->getClientMimeType(),
            'uploaded_by'     => Auth::id(),
        ]);

        $licitacion->update([
            'current_step' => 2,
        ]);

        return redirect()->route('licitaciones.edit.step3', $licitacion);
    }

    /**
     * PASO 3: Configurar junta de aclaraciones + recordatorio de preguntas
     */
    public function editStep3(Licitacion $licitacion)
    {
        return view('licitaciones.step3', compact('licitacion'));
    }

    public function updateStep3(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'fecha_junta_aclaraciones' => 'required|date_format:Y-m-d\TH:i',
            'fecha_limite_preguntas'   => 'nullable|date_format:Y-m-d\TH:i',
            'lugar_junta'              => 'nullable|string|max:255',
            'link_junta'               => 'nullable|string|max:255',
            'recordatorio_emails'      => 'required|array', // ['correo1','correo2']
        ]);

        $fechaJunta = Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_junta_aclaraciones']);

        $fechaLimite = !empty($data['fecha_limite_preguntas'])
            ? Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_limite_preguntas'])
            : $fechaJunta;

        $licitacion->update([
            'fecha_junta_aclaraciones' => $fechaJunta,
            'fecha_limite_preguntas'   => $fechaLimite,
            'lugar_junta'              => $data['lugar_junta'] ?? null,
            'link_junta'               => $data['link_junta'] ?? null,
            'current_step'             => 3,
        ]);

        // Evento principal de junta de aclaraciones
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

        // Recordatorio 2 días antes para agregar preguntas
        $emails = implode(',', $data['recordatorio_emails'] ?? []);
        $startRecordatorio = $fechaJunta->copy()->subDays(2);

        $eventRecordatorio = new AgendaEvent([
            'title'       => 'Agregar preguntas: '.$licitacion->titulo,
            'description' => 'Favor de agregar preguntas para la junta de aclaraciones de la licitación '.$licitacion->titulo,
            'start_at'    => $startRecordatorio,
            'remind_offset_minutes' => 0,
            'repeat_rule' => 'none',
            'timezone'    => config('app.timezone'),
            'attendee_name'  => null,
            'attendee_email' => $emails,
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

        // El paso 4 es gestión de preguntas (otro controlador), aquí saltamos al paso 5
        return redirect()->route('licitaciones.edit.step5', $licitacion);
    }

    /**
     * PASO 5: Fecha de apertura + muestras
     */
    public function editStep5(Licitacion $licitacion)
    {
        return view('licitaciones.step5', compact('licitacion'));
    }

    public function updateStep5(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'fecha_apertura_propuesta' => 'required|date_format:Y-m-d\TH:i',
            'requiere_muestras'        => 'nullable|boolean',
            'fecha_entrega_muestras'   => 'nullable|date_format:Y-m-d\TH:i',
            'lugar_entrega_muestras'   => 'nullable|string|max:255',
        ]);

        $fechaApertura = Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_apertura_propuesta']);

        $licitacion->update([
            'fecha_apertura_propuesta' => $fechaApertura,
            'requiere_muestras'        => $data['requiere_muestras'] ?? false,
            'fecha_entrega_muestras'   => !empty($data['fecha_entrega_muestras'])
                                            ? Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_entrega_muestras'])
                                            : null,
            'lugar_entrega_muestras'   => $data['lugar_entrega_muestras'] ?? null,
            'current_step'             => 5,
        ]);

        // Evento de apertura de propuesta
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

        // Evento de entrega de muestras, si aplica
        if ($licitacion->requiere_muestras && $licitacion->fecha_entrega_muestras) {
            $eventMuestras = new AgendaEvent([
                'title'       => 'Entrega de muestras: '.$licitacion->titulo,
                'description' => 'Entrega de muestras en '.$licitacion->lugar_entrega_muestras,
                'start_at'    => $licitacion->fecha_entrega_muestras,
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

        // Siguiente: paso 6 (acta de antecedente)
        return redirect()->route('licitaciones.edit.step6', $licitacion);
    }

    /**
     * PASO 6: Subir acta de antecedente (PDF)
     */
    public function editStep6(Licitacion $licitacion)
    {
        return view('licitaciones.step6', compact('licitacion'));
    }

    public function updateStep6(Request $request, Licitacion $licitacion)
    {
        $request->validate([
            'acta_antecedente' => 'required|file|mimes:pdf',
        ]);

        $file = $request->file('acta_antecedente');
        $path = $file->store('licitaciones/'.$licitacion->id.'/acta_antecedente', 'public');

        LicitacionArchivo::create([
            'licitacion_id'   => $licitacion->id,
            'tipo'            => 'acta_antecedente',
            'path'            => $path,
            'nombre_original' => $file->getClientOriginalName(),
            'mime_type'       => $file->getClientMimeType(),
            'uploaded_by'     => Auth::id(),
        ]);

        $licitacion->update([
            'current_step' => 6,
        ]);

        return redirect()->route('licitaciones.edit.step7', $licitacion);
    }

    /**
     * PASO 7: Acta de apertura + registro del fallo (ganado / no_ganado)
     */
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

        // Subir acta de apertura si viene
        if ($request->hasFile('acta_apertura')) {
            $file = $request->file('acta_apertura');
            $path = $file->store('licitaciones/'.$licitacion->id.'/acta_apertura', 'public');

            LicitacionArchivo::create([
                'licitacion_id'   => $licitacion->id,
                'tipo'            => 'acta_apertura',
                'path'            => $path,
                'nombre_original' => $file->getClientOriginalName(),
                'mime_type'       => $file->getClientMimeType(),
                'uploaded_by'     => Auth::id(),
            ]);
        }

        // Subir fallo si viene
        if ($request->hasFile('archivo_fallo')) {
            $file = $request->file('archivo_fallo');
            $path = $file->store('licitaciones/'.$licitacion->id.'/fallo', 'public');

            LicitacionArchivo::create([
                'licitacion_id'   => $licitacion->id,
                'tipo'            => 'fallo',
                'path'            => $path,
                'nombre_original' => $file->getClientOriginalName(),
                'mime_type'       => $file->getClientMimeType(),
                'uploaded_by'     => Auth::id(),
            ]);
        }

        $licitacion->update([
            'resultado'           => $data['resultado'],
            'fecha_fallo'         => $data['fecha_fallo'],
            'observaciones_fallo' => $data['observaciones_fallo'] ?? null,
            'current_step'        => 7,
            'estatus'             => $data['resultado'] === 'ganado' ? 'en_proceso' : 'cerrado',
        ]);

        // Si no se ganó, aquí termina el flujo
        if ($data['resultado'] === 'no_ganado') {
            return redirect()
                ->route('licitaciones.show', $licitacion)
                ->with('info', 'La licitación no se ganó, flujo cerrado.');
        }

        // Si se ganó, pasar al paso 8
        return redirect()->route('licitaciones.edit.step8', $licitacion);
    }

    /**
     * PASO 8: Si se ganó: fecha/hora, lugar y documentos para presentar el fallo
     */
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

        // Evento en agenda para presentación de fallo
        $event = new AgendaEvent([
            'title'       => 'Presentar fallo: '.$licitacion->titulo,
            'description' => "Presentación del fallo en {$data['lugar_presentacion_fallo']}. Docs: ".$data['docs_presentar_fallo'],
            'start_at'    => $fechaPresentacion,
            'remind_offset_minutes' => 120, // 2 horas antes
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

    /**
     * PASO 9: Subir contrato + fechas de emisión y fianza
     */
    public function editStep9(Licitacion $licitacion)
    {
        abort_unless($licitacion->resultado === 'ganado', 403);

        return view('licitaciones.step9', compact('licitacion'));
    }

    public function updateStep9(Request $request, Licitacion $licitacion)
    {
        abort_unless($licitacion->resultado === 'ganado', 403);

        $data = $request->validate([
            'contrato'              => 'required|file|mimes:pdf',
            'fecha_emision_contrato'=> 'required|date',
            'fecha_fianza'          => 'required|date',
        ]);

        $file = $request->file('contrato');
        $path = $file->store('licitaciones/'.$licitacion->id.'/contrato', 'public');

        LicitacionArchivo::create([
            'licitacion_id'   => $licitacion->id,
            'tipo'            => 'contrato',
            'path'            => $path,
            'nombre_original' => $file->getClientOriginalName(),
            'mime_type'       => $file->getClientMimeType(),
            'uploaded_by'     => Auth::id(),
        ]);

        $licitacion->update([
            'fecha_emision_contrato' => $data['fecha_emision_contrato'],
            'fecha_fianza'           => $data['fecha_fianza'],
            'current_step'           => 9,
        ]);

        // Evento recordatorio para fianza
        $fechaFianza = Carbon::parse($data['fecha_fianza']);

        $eventFianza = new AgendaEvent([
            'title'       => 'Entrega de fianza: '.$licitacion->titulo,
            'description' => 'Recordatorio para revisar/entregar fianza del contrato de la licitación '.$licitacion->titulo,
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

        // De aquí pasas al checklist de compras (paso 10)
        return redirect()->route('licitaciones.checklist.compras.edit', $licitacion);
    }
}
