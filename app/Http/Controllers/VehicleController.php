<?php

namespace App\Http\Controllers;

use App\Models\AgendaEvent;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(
                Vehicle::orderBy('plate')->get()
            );
        }

        return view('accounting.vehicles.index');
    }

    public function create()
    {
        return view('accounting.vehicles.create');
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $vehicle->load('documents');

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($vehicle);
        }

        return view('accounting.vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        $vehicle->load('documents');
        return view('accounting.vehicles.edit', compact('vehicle'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        // Limpiamos los inputs de documentos vacíos
        $this->cleanFileInputs($data);

        $vehicle = Vehicle::create($data);

        // imágenes
        $this->saveImages($request, $vehicle);

        // docs
        $this->saveDocuments($request, $vehicle);

        // agenda
        $this->syncAgendaDates($vehicle);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($vehicle->fresh('documents'), 201);
        }

        return redirect()->route('vehicles.index')->with('ok', 'Camioneta creada correctamente');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $this->validated($request, $vehicle->id);

        // 1. IMPORTANTE: Quitamos las imágenes y docs del array $data si vienen vacíos.
        // Esto evita que Laravel sobrescriba la ruta en la BD con null (borrando la foto).
        $this->cleanFileInputs($data);

        // 2. Actualizamos textos y fechas
        $vehicle->update($data);

        // 3. Procesamos archivos solo si se subieron nuevos
        $this->saveImages($request, $vehicle);
        $this->saveDocuments($request, $vehicle);
        
        // 4. Actualizamos agenda
        $this->syncAgendaDates($vehicle);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($vehicle->fresh('documents'));
        }

        // Redirigimos con mensaje 'ok' para activar el Toast en la vista
        return redirect()->route('vehicles.edit', $vehicle)->with('ok', 'La camioneta se actualizó correctamente');
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {
        $vehicle->load('documents');

        if ($vehicle->image_left)  Storage::disk('public')->delete($vehicle->image_left);
        if ($vehicle->image_right) Storage::disk('public')->delete($vehicle->image_right);

        foreach ($vehicle->documents as $d) {
            Storage::disk('public')->delete($d->path);
        }

        $this->deleteAgendaIfAny($vehicle->agenda_verification_id);
        $this->deleteAgendaIfAny($vehicle->agenda_service_id);
        $this->deleteAgendaIfAny($vehicle->agenda_tenencia_id);
        $this->deleteAgendaIfAny($vehicle->agenda_circulation_id);
        $this->deleteAgendaIfAny($vehicle->agenda_insurance_id);

        $vehicle->delete();

        return $request->expectsJson() || $request->wantsJson()
            ? response()->json(['ok' => true])
            : redirect()->route('vehicles.index')->with('ok', 'Camioneta eliminada');
    }

    // ========= DOCS endpoints =========
    public function uploadDocuments(Request $request, Vehicle $vehicle)
    {
        $this->saveDocuments($request, $vehicle);
        return response()->json(['ok' => true, 'docs' => $vehicle->fresh('documents')->documents]);
    }

    public function deleteDocument(Vehicle $vehicle, VehicleDocument $doc)
    {
        abort_unless($doc->vehicle_id === $vehicle->id, 404);

        Storage::disk('public')->delete($doc->path);
        $doc->delete();

        return response()->json(['ok' => true]);
    }

    // ========= helpers =========

    /**
     * Elimina las claves de archivos del array de datos para evitar
     * que se sobrescriban con NULL en la base de datos si no se sube nada nuevo.
     */
    private function cleanFileInputs(array &$data): void
    {
        $files = [
            'image_left', 'image_right',
            'doc_tarjeta', 'doc_seguro', 'doc_tenencia', 'doc_verificacion', 'doc_factura'
        ];

        foreach ($files as $key) {
            unset($data[$key]);
        }
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $uniquePlate = ['required', 'string', 'max:20', 'unique:vehicles,plate'];
        if ($ignoreId) $uniquePlate = ['required', 'string', 'max:20', 'unique:vehicles,plate,' . $ignoreId];

        return $request->validate([
            'plate' => $uniquePlate,
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:120'],
            'year'  => ['required', 'integer', 'min:1950', 'max:2100'],
            'vin'   => ['nullable', 'string', 'max:60'],
            'nickname' => ['nullable', 'string', 'max:80'],

            'last_verification_at' => ['nullable', 'date'],
            'last_service_at' => ['nullable', 'date'],
            'next_verification_due_at' => ['nullable', 'date'],
            'next_service_due_at' => ['nullable', 'date'],
            'tenencia_due_at' => ['nullable', 'date'],
            'circulation_card_due_at' => ['nullable', 'date'],
            'insurance_due_at' => ['nullable', 'date'],

            'notes' => ['nullable', 'string'],

            'image_left' => ['nullable', 'file'],
            'image_right' => ['nullable', 'file'],

            'doc_tarjeta' => ['nullable', 'file'],
            'doc_seguro' => ['nullable', 'file'],
            'doc_tenencia' => ['nullable', 'file'],
            'doc_verificacion' => ['nullable', 'file'],
            'doc_factura' => ['nullable', 'file'],
        ]);
    }

    private function saveImages(Request $request, Vehicle $vehicle): void
    {
        if ($request->hasFile('image_left')) {
            if ($vehicle->image_left) Storage::disk('public')->delete($vehicle->image_left);
            $vehicle->image_left = $request->file('image_left')->store("vehicles/{$vehicle->id}", 'public');
            $vehicle->save();
        }

        if ($request->hasFile('image_right')) {
            if ($vehicle->image_right) Storage::disk('public')->delete($vehicle->image_right);
            $vehicle->image_right = $request->file('image_right')->store("vehicles/{$vehicle->id}", 'public');
            $vehicle->save();
        }
    }

    private function saveDocuments(Request $request, Vehicle $vehicle): void
    {
        $map = [
            'doc_tarjeta' => 'tarjeta_circulacion',
            'doc_seguro' => 'seguro',
            'doc_tenencia' => 'tenencia',
            'doc_verificacion' => 'verificacion',
            'doc_factura' => 'factura',
        ];

        foreach ($map as $input => $type) {
            if (!$request->hasFile($input)) continue;

            $file = $request->file($input);
            $path = $file->store("vehicles/{$vehicle->id}/docs", 'public');

            VehicleDocument::create([
                'vehicle_id' => $vehicle->id,
                'type' => $type,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
            ]);
        }
    }

    private function syncAgendaDates(Vehicle $vehicle): void
    {
        $tz = 'America/Mexico_City';
        $userId = auth()->id() ?: 1;
        $vehicle->refresh();

        $vehicle->agenda_verification_id = $this->upsertAgendaEvent($vehicle->agenda_verification_id, $vehicle->next_verification_due_at, "Verificación · {$vehicle->plate}", "Próxima verificación de {$vehicle->brand} {$vehicle->model} {$vehicle->year}", $userId, $tz);
        $vehicle->agenda_service_id = $this->upsertAgendaEvent($vehicle->agenda_service_id, $vehicle->next_service_due_at, "Servicio · {$vehicle->plate}", "Próximo servicio de {$vehicle->brand} {$vehicle->model} {$vehicle->year}", $userId, $tz);
        $vehicle->agenda_tenencia_id = $this->upsertAgendaEvent($vehicle->agenda_tenencia_id, $vehicle->tenencia_due_at, "Tenencia · {$vehicle->plate}", "Vencimiento de tenencia de {$vehicle->plate}", $userId, $tz);
        $vehicle->agenda_circulation_id = $this->upsertAgendaEvent($vehicle->agenda_circulation_id, $vehicle->circulation_card_due_at, "Tarjeta Circulación · {$vehicle->plate}", "Vencimiento de tarjeta de circulación de {$vehicle->plate}", $userId, $tz);

        if (!is_null($vehicle->insurance_due_at)) {
            $vehicle->agenda_insurance_id = $this->upsertAgendaEvent($vehicle->agenda_insurance_id, $vehicle->insurance_due_at, "Seguro · {$vehicle->plate}", "Vencimiento de seguro de {$vehicle->plate}", $userId, $tz);
        } else {
            $vehicle->agenda_insurance_id = $this->upsertAgendaEvent($vehicle->agenda_insurance_id, null, '', '', $userId, $tz);
        }

        $vehicle->save();
    }

    private function upsertAgendaEvent(?int $agendaId, $date, string $title, string $desc, int $userId, string $tz): ?int
    {
        if (!$date) {
            $this->deleteAgendaIfAny($agendaId);
            return null;
        }

        $startAt = Carbon::parse($date, $tz)->setTime(9, 0, 0)->format('Y-m-d H:i:s');
        $event = $agendaId ? AgendaEvent::find($agendaId) : null;
        if (!$event) $event = new AgendaEvent();

        $event->title = $title;
        $event->description = $desc;
        $event->timezone = $tz;
        $event->start_at = $startAt;
        $event->repeat_rule = 'none';
        $event->remind_offset_minutes = 1440;
        $event->user_ids = [$userId];
        $event->send_email = true;
        $event->send_whatsapp = true;

        if (method_exists($event, 'computeNextReminder')) {
            $event->computeNextReminder();
        }

        $event->save();
        return $event->id;
    }

    private function deleteAgendaIfAny(?int $agendaId): void
    {
        if (!$agendaId) return;
        $e = AgendaEvent::find($agendaId);
        if ($e) $e->delete();
    }
}