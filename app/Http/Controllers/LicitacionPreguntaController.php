<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;
use App\Models\LicitacionPregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LicitacionPreguntaController extends Controller
{
    public function index(Licitacion $licitacion)
    {
        $preguntas = $licitacion->preguntas()
            ->with('usuario') // para poder usar ->usuario->name en la vista
            ->latest('created_at')
            ->get();

        // calculas límite y puedePreguntar como lo estés haciendo:
        $limite = $licitacion->fecha_limite_preguntas;
        $puedePreguntar = ! $limite || now()->lte($limite);

        return view('licitaciones.preguntas.index', compact(
            'licitacion',
            'preguntas',
            'limite',
            'puedePreguntar'
        ));
    }

    public function store(Request $request, Licitacion $licitacion)
    {
        $data = $request->validate([
            'texto_pregunta' => 'required|string|max:2000',
            'notas_internas' => 'nullable|string|max:2000',
        ]);

        // Validar fecha límite
        if ($licitacion->fecha_limite_preguntas && now()->gt($licitacion->fecha_limite_preguntas)) {
            return back()->withErrors([
                'texto_pregunta' => 'La fecha límite para enviar preguntas ya venció.',
            ])->withInput();
        }

        LicitacionPregunta::create([
            'licitacion_id'  => $licitacion->id,
            // ⬅⬅ ESTE ES EL CAMBIO IMPORTANTE:
            'user_id'        => Auth::id(),  // tiene que coincidir con el nombre de tu columna
            'texto_pregunta' => $data['texto_pregunta'],
            'notas_internas' => $data['notas_internas'] ?? null,
            'fecha_pregunta' => now(),
        ]);

        return redirect()
            ->route('licitaciones.preguntas.index', $licitacion)
            ->with('success', 'Pregunta registrada correctamente.');
    }
}
