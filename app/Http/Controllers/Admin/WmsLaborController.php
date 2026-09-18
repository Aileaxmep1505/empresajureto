<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\PickWave;
use App\Models\User;
use App\Models\WmsCountLine;
use App\Models\WmsMovement;
use App\Models\WmsReception;
use App\Models\WmsReplenishmentTask;
use App\Models\WmsSetting;
use App\Models\WmsShipment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Productividad del personal (LMS).
 *
 * Junta lo que cada quien hizo en el almacén (picking, recepción, movimientos,
 * embarque, reabasto, conteos) en un periodo y lo compara contra las metas
 * por hora configuradas.
 */
class WmsLaborController extends Controller
{
    private const METAS = [
        'picking_uph' => 120,   // unidades surtidas por hora
        'embarque_uph' => 200,  // unidades cargadas por hora
    ];

    public function index(Request $request)
    {
        $desde = $this->fecha($request->get('desde'), now()->subDays(6))->startOfDay();
        $hasta = $this->fecha($request->get('hasta'), now())->endOfDay();

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        $metas = array_merge(self::METAS, (array) WmsSetting::get('labor_targets', []));
        $filas = [];   // por usuario
        $porDia = [];  // tareas por día, para la gráfica

        $sumar = function (?int $userId, string $area, int $tareas, int $unidades, int $minutos, $cuando) use (&$filas, &$porDia) {
            if (! $userId) {
                return;
            }

            $filas[$userId][$area] ??= ['tareas' => 0, 'unidades' => 0, 'minutos' => 0];
            $filas[$userId][$area]['tareas'] += $tareas;
            $filas[$userId][$area]['unidades'] += $unidades;
            $filas[$userId][$area]['minutos'] += $minutos;

            $dia = Carbon::parse($cuando)->format('Y-m-d');
            $porDia[$dia] = ($porDia[$dia] ?? 0) + $tareas;
        };

        // ---- Picking: olas terminadas en el periodo ----
        $olas = PickWave::whereNotNull('assigned_user_id')
            ->where(fn ($q) => $q->whereBetween('completed_at', [$desde, $hasta])->orWhereBetween('finished_at', [$desde, $hasta]))
            ->get();

        foreach ($olas as $ola) {
            $items = is_array($ola->items) ? $ola->items : (json_decode((string) $ola->items, true) ?: []);
            $fin = $ola->completed_at ?? $ola->finished_at;
            $min = $ola->started_at && $fin ? (int) Carbon::parse($ola->started_at)->diffInMinutes($fin) : 0;

            $sumar((int) $ola->assigned_user_id, 'picking', 1, (int) collect($items)->sum(fn ($i) => (int) ($i['quantity_picked'] ?? 0)), $min, $fin);
        }

        // ---- Recepción ----
        foreach (WmsReception::with('lines')->whereBetween('created_at', [$desde, $hasta])->get() as $r) {
            $sumar((int) ($r->receiver_user_id ?: $r->created_by), 'recepcion', 1, (int) $r->lines->sum('quantity'), 0, $r->created_at);
        }

        // ---- Entradas / salidas manuales ----
        foreach (WmsMovement::with('lines')->whereBetween('created_at', [$desde, $hasta])->get() as $m) {
            $sumar((int) $m->user_id, 'movimientos', 1, (int) $m->lines->sum('qty'), 0, $m->created_at);
        }

        // ---- Transferencias y ajustes sueltos (los de reabasto y conteo se cuentan aparte) ----
        foreach (InventoryMovement::whereBetween('created_at', [$desde, $hasta])->whereNotIn('type', ['replenishment', 'count_adjust'])->get() as $m) {
            $sumar((int) $m->user_id, 'movimientos', 1, (int) $m->qty, 0, $m->created_at);
        }

        // ---- Embarque ----
        $embarques = WmsShipment::where(fn ($q) => $q->whereBetween('loading_completed_at', [$desde, $hasta])->orWhereBetween('dispatched_at', [$desde, $hasta]))->get();

        foreach ($embarques as $e) {
            $min = $e->loading_started_at && $e->loading_completed_at
                ? (int) Carbon::parse($e->loading_started_at)->diffInMinutes($e->loading_completed_at) : 0;

            $sumar((int) ($e->operator_user_id ?: $e->created_by), 'embarque', 1, (int) $e->loaded_qty, $min, $e->loading_completed_at ?? $e->dispatched_at);
        }

        // ---- Reabastecimiento ----
        foreach (WmsReplenishmentTask::where('status', 'hecha')->whereBetween('completed_at', [$desde, $hasta])->get() as $t) {
            $sumar((int) $t->completed_by, 'reabasto', 1, (int) $t->qty_moved, 0, $t->completed_at);
        }

        // ---- Conteos ----
        foreach (WmsCountLine::whereNotNull('counted_by')->whereBetween('counted_at', [$desde, $hasta])->get() as $l) {
            $sumar((int) $l->counted_by, 'conteos', 1, (int) $l->counted_qty, 0, $l->counted_at);
        }

        $usuarios = User::whereIn('id', array_keys($filas))->get(['id', 'name'])->keyBy('id');
        $areas = ['picking', 'recepcion', 'movimientos', 'embarque', 'reabasto', 'conteos'];

        $tabla = collect($filas)->map(function ($porArea, $userId) use ($usuarios, $areas, $metas) {
            $fila = ['user' => $usuarios->get($userId), 'tareas' => 0, 'unidades' => 0];

            foreach ($areas as $a) {
                $fila[$a] = $porArea[$a] ?? ['tareas' => 0, 'unidades' => 0, 'minutos' => 0];
                $fila['tareas'] += $fila[$a]['tareas'];
                $fila['unidades'] += $fila[$a]['unidades'];
            }

            $fila['picking_uph'] = $this->porHora($fila['picking']);
            $fila['embarque_uph'] = $this->porHora($fila['embarque']);
            $fila['picking_ef'] = $fila['picking_uph'] !== null && $metas['picking_uph'] > 0 ? (int) round($fila['picking_uph'] / $metas['picking_uph'] * 100) : null;
            $fila['embarque_ef'] = $fila['embarque_uph'] !== null && $metas['embarque_uph'] > 0 ? (int) round($fila['embarque_uph'] / $metas['embarque_uph'] * 100) : null;

            return $fila;
        })->sortByDesc('tareas')->values();

        // Serie diaria completa (con ceros) para la gráfica.
        $serie = [];
        for ($d = $desde->copy(); $d->lte($hasta); $d->addDay()) {
            $serie[] = ['dia' => $d->format('d/m'), 'tareas' => (int) ($porDia[$d->format('Y-m-d')] ?? 0)];
        }

        return view('admin.wms.ops.labor', [
            'tabla' => $tabla,
            'serie' => $serie,
            'metas' => $metas,
            'desde' => $desde,
            'hasta' => $hasta,
            'totales' => [
                'personas' => $tabla->count(),
                'tareas' => (int) $tabla->sum('tareas'),
                'unidades' => (int) $tabla->sum('unidades'),
            ],
        ]);
    }

    public function saveTargets(Request $request)
    {
        $data = $request->validate([
            'picking_uph' => ['required', 'integer', 'min:1', 'max:100000'],
            'embarque_uph' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        WmsSetting::put('labor_targets', $data);

        return back()->with('ok', 'Metas de productividad actualizadas.');
    }

    /** Unidades por hora de un área; nulo si no hay tiempo medido. */
    private function porHora(array $area): ?int
    {
        return $area['minutos'] > 0 ? (int) round($area['unidades'] / ($area['minutos'] / 60)) : null;
    }

    private function fecha(?string $valor, Carbon $porOmision): Carbon
    {
        try {
            return $valor ? Carbon::createFromFormat('Y-m-d', $valor) : $porOmision->copy();
        } catch (\Throwable) {
            return $porOmision->copy();
        }
    }
}
