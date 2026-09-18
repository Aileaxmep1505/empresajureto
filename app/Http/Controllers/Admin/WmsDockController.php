<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WmsDockAppointment;
use App\Models\WmsSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Citas de andén (gestión de patio).
 *
 * Agenda por andén de los vehículos que llegan a cargar o descargar, con su
 * llegada, entrada y salida, para medir puntualidad y tiempo en andén.
 */
class WmsDockController extends Controller
{
    private const ANDENES = ['Andén 1', 'Andén 2'];

    public function index(Request $request)
    {
        try {
            $dia = $request->get('dia') ? Carbon::createFromFormat('Y-m-d', $request->get('dia')) : now();
        } catch (\Throwable) {
            $dia = now();
        }

        $andenes = $this->andenes();

        $citas = WmsDockAppointment::whereDate('scheduled_at', $dia->toDateString())->orderBy('scheduled_at')->get();

        // Un andén borrado de la lista no debe esconder sus citas.
        foreach ($citas->pluck('dock')->unique() as $a) {
            if (! in_array($a, $andenes, true)) {
                $andenes[] = $a;
            }
        }

        // Indicadores de los últimos 30 días.
        $recientes = WmsDockAppointment::where('scheduled_at', '>=', now()->subDays(30))->get();
        $conLlegada = $recientes->whereNotNull('arrived_at');
        $terminadas = $recientes->whereNotNull('started_at')->whereNotNull('finished_at');

        return view('admin.wms.ops.docks', [
            'dia' => $dia,
            'andenes' => $andenes,
            'citas' => $citas->groupBy('dock'),
            'totalHoy' => $citas->count(),
            'bodegas' => Warehouse::orderBy('name')->get(['id', 'name']),
            'kpis' => [
                'puntualidad' => $conLlegada->count() ? (int) round($conLlegada->filter->puntual->count() / $conLlegada->count() * 100) : null,
                'min_anden' => $terminadas->count() ? (int) round($terminadas->avg(fn ($c) => $c->minutos_en_anden)) : null,
                'no_llegaron' => $recientes->where('status', 'no_llego')->count(),
                'en_curso' => WmsDockAppointment::whereIn('status', ['llego', 'en_anden'])->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'dock' => ['required', 'string', 'max:60'],
            'type' => ['required', 'in:entrada,salida'],
            'carrier' => ['nullable', 'string', 'max:120'],
            'vehicle_plate' => ['nullable', 'string', 'max:30'],
            'driver_name' => ['nullable', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:120'],
            'scheduled_at' => ['required', 'date'],
            'duration_min' => ['required', 'integer', 'min:5', 'max:1440'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $inicio = Carbon::parse($data['scheduled_at']);
        $fin = $inicio->copy()->addMinutes((int) $data['duration_min']);

        // Dos citas activas no pueden traslaparse en el mismo andén.
        $choque = WmsDockAppointment::where('dock', $data['dock'])
            ->whereNotIn('status', ['cancelada', 'no_llego', 'terminada'])
            ->whereDate('scheduled_at', $inicio->toDateString())
            ->get()
            ->first(fn ($c) => $c->scheduled_at->lt($fin) && $c->scheduled_at->copy()->addMinutes($c->duration_min)->gt($inicio));

        if ($choque) {
            return back()->withInput()->with('error', "Ese horario se traslapa con otra cita en {$data['dock']} ({$choque->scheduled_at->format('H:i')}, {$choque->carrier}).");
        }

        WmsDockAppointment::create($data + ['status' => 'programada', 'created_by' => $request->user()->id]);

        return redirect()->route('admin.wms.docks.index', ['dia' => $inicio->toDateString()])->with('ok', 'Cita programada.');
    }

    /** Avanza o marca la cita: llegó, en andén, terminada, no llegó, cancelada. */
    public function status(Request $request, WmsDockAppointment $appointment)
    {
        $data = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(WmsDockAppointment::STATUSES))]]);

        $cambios = ['status' => $data['status']];

        if ($data['status'] === 'llego') {
            $cambios['arrived_at'] = $appointment->arrived_at ?? now();
        }
        if ($data['status'] === 'en_anden') {
            $cambios['arrived_at'] = $appointment->arrived_at ?? now();
            $cambios['started_at'] = $appointment->started_at ?? now();
        }
        if ($data['status'] === 'terminada') {
            $cambios['arrived_at'] = $appointment->arrived_at ?? now();
            $cambios['started_at'] = $appointment->started_at ?? now();
            $cambios['finished_at'] = now();
        }

        $appointment->update($cambios);

        return back()->with('ok', 'Cita marcada como ' . mb_strtolower($appointment->status_label) . '.');
    }

    public function destroy(WmsDockAppointment $appointment)
    {
        $appointment->delete();

        return back()->with('ok', 'Cita eliminada.');
    }

    /** Guarda la lista de andenes (uno por línea). */
    public function saveDocks(Request $request)
    {
        $data = $request->validate(['andenes' => ['required', 'string', 'max:1000']]);

        $lista = collect(preg_split('/\r\n|\r|\n|,/', $data['andenes']))
            ->map(fn ($a) => trim($a))->filter()->unique()->take(30)->values()->all();

        if (! $lista) {
            return back()->with('error', 'Escribe al menos un andén.');
        }

        WmsSetting::put('docks', $lista);

        return back()->with('ok', 'Lista de andenes actualizada.');
    }

    private function andenes(): array
    {
        $lista = (array) WmsSetting::get('docks', self::ANDENES);

        return $lista ?: self::ANDENES;
    }
}
