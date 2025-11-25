<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;
use App\Models\LicitacionPregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LicitacionPreguntaController extends Controller
{
    /**
     * Paso 4 (Preguntas): listado + formulario
     */
    public function index(Licitacion $licitacion)
    {
        $licitacion->load(['preguntas.usuario', 'archivos']);

        // Límite = fecha límite o, si no hay, fecha junta
        $limite = $licitacion->fecha_limite_preguntas
            ? Carbon::parse($licitacion->fecha_limite_preguntas)
            : ($licitacion->fecha_junta_aclaraciones ? Carbon::parse($licitacion->fecha_junta_aclaraciones) : null);

        $puedePreguntar = !$limite || now()->lte($limite);

        // ✅ Si entró al Paso 4, lo marcamos como hecho
        if (($licitacion->current_step ?? 0) < 4) {
            $licitacion->update(['current_step' => 4]);
        }

        $preguntas = $licitacion->preguntas()
            ->with('usuario')
            ->orderByDesc('fecha_pregunta')
            ->orderByDesc('id')
            ->get();

        return view('licitaciones.preguntas', compact(
            'licitacion',
            'preguntas',
            'limite',
            'puedePreguntar'
        ));
    }

    /**
     * Guardar una pregunta
     */
    public function store(Request $request, Licitacion $licitacion)
    {
        // Recalcular límite
        $limite = $licitacion->fecha_limite_preguntas
            ? Carbon::parse($licitacion->fecha_limite_preguntas)
            : ($licitacion->fecha_junta_aclaraciones ? Carbon::parse($licitacion->fecha_junta_aclaraciones) : null);

        if ($limite && now()->gt($limite)) {
            return back()->withErrors([
                'texto_pregunta' => 'La fecha límite para enviar preguntas ya pasó.'
            ]);
        }

        $data = $request->validate([
            'texto_pregunta' => 'required|string|max:2000',
            'notas_internas' => 'nullable|string|max:1000',
        ]);

        LicitacionPregunta::create([
            'licitacion_id'   => $licitacion->id,
            'texto_pregunta'  => $data['texto_pregunta'],
            'notas_internas'  => $data['notas_internas'] ?? null,
            'fecha_pregunta'  => now(),
            'user_id'         => Auth::id(), // ✅ debe coincidir con $pregunta->usuario
        ]);

        // ✅ Paso 4 hecho
        if (($licitacion->current_step ?? 0) < 4) {
            $licitacion->update(['current_step' => 4]);
        }

        return back()->with('success', 'Pregunta guardada correctamente.');
    }
}
