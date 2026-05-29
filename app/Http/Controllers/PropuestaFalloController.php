<?php

namespace App\Http\Controllers;

use App\Models\Adjudicacion;
use App\Models\AdjudicacionItem;
use App\Models\Client;
use App\Models\PropuestaComercial;
use App\Models\PropuestaFallo;
use App\Models\PropuestaFalloOferta;
use App\Models\PropuestaFalloPartida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PropuestaFalloController extends Controller
{
    public function show(PropuestaComercial $propuestaComercial)
    {
        $fallo = PropuestaFallo::firstOrCreate(
            ['propuesta_comercial_id' => $propuestaComercial->id],
            ['resultado' => 'pending']
        );

        $fallo->load(['partidas.ofertas', 'partidas.item']);
        $propuestaComercial->load('items');

        return view('propuestas_comerciales.fallo', [
            'propuestaComercial' => $propuestaComercial,
            'fallo' => $fallo,
            'adjudicaciones' => $propuestaComercial->adjudicaciones()->latest()->get(),
        ]);
    }

    public function uploadActa(Request $request, PropuestaComercial $propuestaComercial)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:25600'],
        ]);

        $fallo = PropuestaFallo::firstOrCreate(
            ['propuesta_comercial_id' => $propuestaComercial->id],
            ['resultado' => 'pending']
        );

        if ($fallo->file_path) {
            Storage::disk('public')->delete($fallo->file_path);
        }

        $fallo->file_path = $request->file('file')->store('actas_fallo', 'public');
        $fallo->ocr_status = 'pending'; // Azure OCR se conecta en la siguiente wave
        $fallo->save();

        return back()->with('status', 'Acta de fallo subida correctamente.');
    }

    public function updateHeader(Request $request, PropuestaFallo $fallo)
    {
        $data = $request->validate([
            'numero_acta' => ['nullable', 'string', 'max:255'],
            'fecha_fallo' => ['nullable', 'date'],
            'resultado' => ['nullable', 'in:pending,won,lost,partial'],
        ]);

        $fallo->update($data);

        return back()->with('status', 'Datos del acta actualizados.');
    }

    /**
     * Genera partidas del acta a partir de las partidas cotizadas (para captura manual rápida).
     */
    public function seedPartidas(Request $request, PropuestaFallo $fallo)
    {
        $propuesta = $fallo->propuesta()->with('items')->first();

        DB::transaction(function () use ($fallo, $propuesta, $request) {
            if ($request->boolean('reset')) {
                $fallo->partidas()->each(function ($p) {
                    $p->ofertas()->delete();
                    $p->delete();
                });
            }

            foreach ($propuesta->items->sortBy('sort') as $item) {
                $exists = $fallo->partidas()
                    ->where('propuesta_comercial_item_id', $item->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $cantidad = $item->cantidad_cotizada ?: $item->cantidad_maxima ?: 1;

                $partida = PropuestaFalloPartida::create([
                    'propuesta_fallo_id' => $fallo->id,
                    'propuesta_comercial_item_id' => $item->id,
                    'partida_label' => $item->partida_numero ?? $item->sort,
                    'descripcion' => $item->descripcion_original,
                    'cantidad' => $cantidad,
                    'nuestro_precio' => $item->precio_unitario,
                    'ganador' => null,
                    'source' => 'manual',
                ]);

                PropuestaFalloOferta::create([
                    'propuesta_fallo_partida_id' => $partida->id,
                    'empresa' => 'JURETO S.A. DE C.V.',
                    'es_jureto' => true,
                    'gano' => false,
                    'precio' => $item->precio_unitario,
                    'cantidad' => $cantidad,
                ]);
            }
        });

        return back()->with('status', 'Partidas generadas desde la cotización.');
    }

    public function storePartida(Request $request, PropuestaFallo $fallo)
    {
        $data = $request->validate([
            'descripcion' => ['required', 'string'],
            'partida_label' => ['nullable', 'string', 'max:255'],
            'cantidad' => ['nullable', 'numeric', 'min:0'],
            'nuestro_precio' => ['nullable', 'numeric', 'min:0'],
            'propuesta_comercial_item_id' => ['nullable', 'integer', 'exists:propuesta_comercial_items,id'],
        ]);

        $data['propuesta_fallo_id'] = $fallo->id;
        $data['source'] = 'manual';

        PropuestaFalloPartida::create($data);

        return back()->with('status', 'Partida agregada.');
    }

    public function updatePartida(Request $request, PropuestaFalloPartida $partida)
    {
        $data = $request->validate([
            'descripcion' => ['nullable', 'string'],
            'partida_label' => ['nullable', 'string', 'max:255'],
            'cantidad' => ['nullable', 'numeric', 'min:0'],
            'ganador' => ['nullable', 'in:jureto,competidor,desierto'],
            'empresa_ganadora' => ['nullable', 'string', 'max:255'],
            'precio_ganador' => ['nullable', 'numeric', 'min:0'],
            'nuestro_precio' => ['nullable', 'numeric', 'min:0'],
            'motivo' => ['nullable', 'string'],
        ]);

        $partida->fill($data);
        $partida->source = 'manual'; // revertir/editar a manual
        $this->recalcDiferencia($partida);
        $partida->save();

        $this->recalcResultado($partida->fallo);

        return back()->with('status', 'Partida actualizada.');
    }

    public function destroyPartida(PropuestaFalloPartida $partida)
    {
        $fallo = $partida->fallo;
        $partida->ofertas()->delete();
        $partida->delete();

        if ($fallo) {
            $this->recalcResultado($fallo);
        }

        return back()->with('status', 'Partida eliminada.');
    }

    public function storeOferta(Request $request, PropuestaFalloPartida $partida)
    {
        $data = $request->validate([
            'empresa' => ['required', 'string', 'max:255'],
            'es_jureto' => ['nullable', 'boolean'],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'cantidad' => ['nullable', 'numeric', 'min:0'],
            'gano' => ['nullable', 'boolean'],
        ]);

        $data['propuesta_fallo_partida_id'] = $partida->id;
        $data['es_jureto'] = $request->boolean('es_jureto');
        $data['gano'] = $request->boolean('gano');

        $oferta = PropuestaFalloOferta::create($data);

        $this->applyWinner($oferta);

        return back()->with('status', 'Oferta agregada.');
    }

    public function updateOferta(Request $request, PropuestaFalloOferta $oferta)
    {
        $data = $request->validate([
            'empresa' => ['required', 'string', 'max:255'],
            'es_jureto' => ['nullable', 'boolean'],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'cantidad' => ['nullable', 'numeric', 'min:0'],
            'gano' => ['nullable', 'boolean'],
        ]);

        $data['es_jureto'] = $request->boolean('es_jureto');
        $data['gano'] = $request->boolean('gano');
        $oferta->update($data);

        $this->applyWinner($oferta);

        return back()->with('status', 'Oferta actualizada.');
    }

    public function destroyOferta(PropuestaFalloOferta $oferta)
    {
        $partida = $oferta->partida;
        $oferta->delete();

        if ($partida) {
            $this->recalcResultado($partida->fallo);
        }

        return back()->with('status', 'Oferta eliminada.');
    }

    protected function applyWinner(PropuestaFalloOferta $oferta): void
    {
        $partida = $oferta->partida;
        if (!$partida) {
            return;
        }

        if ($oferta->es_jureto && $oferta->precio !== null) {
            $partida->nuestro_precio = $oferta->precio;
        }

        if ($oferta->gano) {
            PropuestaFalloOferta::where('propuesta_fallo_partida_id', $partida->id)
                ->where('id', '!=', $oferta->id)
                ->update(['gano' => false]);

            $partida->ganador = $oferta->es_jureto ? 'jureto' : 'competidor';
            $partida->empresa_ganadora = $oferta->empresa;
            $partida->precio_ganador = $oferta->precio;
        }

        $this->recalcDiferencia($partida);
        $partida->save();

        $this->recalcResultado($partida->fallo);
    }

    protected function recalcDiferencia(PropuestaFalloPartida $partida): void
    {
        if ($partida->nuestro_precio !== null && $partida->precio_ganador !== null) {
            $partida->diferencia = (float) $partida->nuestro_precio - (float) $partida->precio_ganador;
        } else {
            $partida->diferencia = null;
        }
    }

    protected function recalcResultado(?PropuestaFallo $fallo): void
    {
        if (!$fallo) {
            return;
        }

        $fallo->load('partidas');
        $total = $fallo->partidas->count();

        if ($total === 0) {
            $fallo->update(['resultado' => 'pending']);
            return;
        }

        $ganadas = $fallo->partidas->where('ganador', 'jureto')->count();

        if ($ganadas === 0) {
            $fallo->update(['resultado' => 'lost']);
        } elseif ($ganadas === $total) {
            $fallo->update(['resultado' => 'won']);
        } else {
            $fallo->update(['resultado' => 'partial']);
        }
    }

    /**
     * Convierte SOLO las partidas ganadas por JURETO en una nueva Adjudicación.
     */
    public function convertToAdjudicacion(Request $request, PropuestaFallo $fallo)
    {
        $fallo->load(['partidas.item', 'propuesta']);
        $propuesta = $fallo->propuesta;

        $ganadas = $fallo->partidas->where('ganador', 'jureto');

        if ($ganadas->isEmpty()) {
            return back()->with('error', 'No hay partidas ganadas por JURETO para convertir.');
        }

        $clienteNombre = $propuesta->cliente;
        $client = null;

        if ($clienteNombre) {
            $client = Client::where('razon_social', 'like', "%{$clienteNombre}%")
                ->orWhere('nombre', 'like', "%{$clienteNombre}%")
                ->first();
        }

        $impuestoPct = (float) ($propuesta->porcentaje_impuesto ?: 16);

        $adjudicacion = null;

        DB::transaction(function () use ($fallo, $propuesta, $ganadas, $client, $clienteNombre, $impuestoPct, &$adjudicacion) {
            $adjudicacion = Adjudicacion::create([
                'propuesta_comercial_id' => $propuesta->id,
                'propuesta_fallo_id' => $fallo->id,
                'client_id' => optional($client)->id,
                'cliente_nombre' => $clienteNombre,
                'fecha' => now(),
                'porcentaje_impuesto' => $impuestoPct,
                'status' => 'borrador',
            ]);

            $sort = 0;
            $subtotal = 0;

            foreach ($ganadas as $p) {
                $sort++;
                $item = $p->item;

                $descripcion = $p->descripcion ?: (optional($item)->descripcion_original ?: 'Partida');
                $unidad = optional($item)->unidad_solicitada;
                $cantidad = (float) ($p->cantidad ?: (optional($item)->cantidad_cotizada ?: 1));
                $precio = (float) ($p->nuestro_precio ?: (optional($item)->precio_unitario ?: 0));
                $costo = (float) optional($item)->costo_unitario;
                $sub = round($precio * $cantidad, 2);
                $subtotal += $sub;

                AdjudicacionItem::create([
                    'adjudicacion_id' => $adjudicacion->id,
                    'propuesta_comercial_item_id' => optional($item)->id,
                    'sort' => $sort,
                    'descripcion' => $descripcion,
                    'unidad' => $unidad,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costo,
                    'precio_unitario' => $precio,
                    'subtotal' => $sub,
                ]);
            }

            $impuesto = round($subtotal * ($impuestoPct / 100), 2);

            $adjudicacion->update([
                'folio' => 'ADJ-' . str_pad((string) $adjudicacion->id, 6, '0', STR_PAD_LEFT),
                'subtotal' => round($subtotal, 2),
                'impuesto_total' => $impuesto,
                'total' => round($subtotal + $impuesto, 2),
            ]);
        });

        return redirect()
            ->route('adjudicaciones.show', $adjudicacion)
            ->with('status', 'Adjudicación creada con las partidas ganadas por JURETO.');
    }
        public function runOcr(Request $request, PropuestaFallo $fallo)
    {
        if (!$fallo->file_path) {
            return back()->with('error', 'Primero sube el PDF del acta.');
        }

        $absPath = \Illuminate\Support\Facades\Storage::disk('public')->path($fallo->file_path);

        if (!is_file($absPath)) {
            return back()->with('error', 'No se encontró el archivo del acta en el servidor.');
        }

        $bin = config('services.python_ai.bin') ?: 'python';
        $script = config('services.python_ai.acta_script') ?: base_path('python/acta_fallo_cli.py');

        if (!is_file($script)) {
            return back()->with('error', 'No se encontró el script de extracción: ' . $script);
        }

        $fallo->update(['ocr_status' => 'processing']);

        try {
            $process = new \Symfony\Component\Process\Process([
                $bin,
                $script,
                '--file', $absPath,
                '--pages-per-chunk', '5',
            ]);
            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput() ?: 'Falló el proceso de extracción.');
            }

            $out = trim($process->getOutput());
            $data = json_decode($out, true);

            if (!is_array($data) || empty($data['ok'])) {
                $msg = is_array($data) ? ($data['message'] ?? '') : '';
                throw new \RuntimeException($msg ?: ('Salida no válida del extractor: ' . mb_substr($out, 0, 400)));
            }
        } catch (\Throwable $e) {
            $fallo->update(['ocr_status' => 'failed']);
            return back()->with('error', 'No se pudo extraer el acta: ' . $e->getMessage());
        }

        $partidas = $data['partidas'] ?? [];

        DB::transaction(function () use ($fallo, $data, $partidas, $request) {
            if ($request->boolean('reset')) {
                // solo borra las que vienen de IA; respeta las manuales
                $fallo->partidas()->where('source', 'ai')->each(function ($p) {
                    $p->ofertas()->delete();
                    $p->delete();
                });
            }

            foreach ($partidas as $row) {
                $partida = PropuestaFalloPartida::create([
                    'propuesta_fallo_id' => $fallo->id,
                    'partida_label' => $row['partida_label'] ?? null,
                    'descripcion' => $row['descripcion'] ?? null,
                    'cantidad' => $row['cantidad'] ?? null,
                    'source' => 'ai',
                ]);

                foreach (($row['ofertas'] ?? []) as $of) {
                    PropuestaFalloOferta::create([
                        'propuesta_fallo_partida_id' => $partida->id,
                        'empresa' => $of['empresa'] ?? 'N/D',
                        'es_jureto' => !empty($of['es_jureto']),
                        'gano' => !empty($of['gano']),
                        'precio' => $of['precio'] ?? null,
                    ]);
                }

                $this->syncPartidaFromOfertas($partida);
            }

            $update = [
                'ocr_status' => 'done',
                'ocr_text' => $data['content'] ?? null,
            ];

            if (empty($fallo->numero_acta) && !empty($data['numero_acta'])) {
                $update['numero_acta'] = $data['numero_acta'];
            }
            if (empty($fallo->fecha_fallo) && !empty($data['fecha_fallo'])) {
                $update['fecha_fallo'] = $data['fecha_fallo'];
            }

            $fallo->update($update);
        });

        $this->recalcResultado($fallo->fresh());

        return back()->with('status', 'Extracción del acta completada. Revisa y ajusta las partidas marcadas como IA.');
    }

    protected function syncPartidaFromOfertas(PropuestaFalloPartida $partida): void
    {
        $partida->load('ofertas');

        $jureto = $partida->ofertas->firstWhere('es_jureto', true);
        if ($jureto) {
            $partida->nuestro_precio = $jureto->precio;
        }

        $winner = $partida->ofertas->firstWhere('gano', true);
        if ($winner) {
            $partida->ganador = $winner->es_jureto ? 'jureto' : 'competidor';
            $partida->empresa_ganadora = $winner->empresa;
            $partida->precio_ganador = $winner->precio;
        }

        $this->recalcDiferencia($partida);
        $partida->save();
    }
}