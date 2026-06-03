<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PropuestaAclaracionPregunta;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PropuestaAclaracionPreguntaController extends Controller
{
    public function suggest(Request $request, PropuestaComercialItem $item)
    {
        $data = $request->validate([
            'texto_usuario' => ['nullable', 'string'],
            'buscar_catalogo' => ['nullable', 'boolean'],
            'buscar_internet' => ['nullable', 'boolean'],
        ]);

        $textoUsuario = trim((string) ($data['texto_usuario'] ?? ''));

        $productoSugerido = null;
        $skuSugerido = null;
        $marcaSugerida = null;
        $precioSugerido = null;

        if ($request->boolean('buscar_catalogo', true)) {
            $producto = $this->buscarProductoCatalogo($item);

            if ($producto) {
                $productoSugerido = $producto['name'] ?? null;
                $skuSugerido = $producto['sku'] ?? null;
                $marcaSugerida = $producto['brand'] ?? null;
                $precioSugerido = $producto['price'] ?? null;
            }
        }

        $preguntaGenerada = $this->generarPreguntaFormal(
            textoUsuario: $textoUsuario,
            descripcionItem: (string) $item->descripcion_original,
            productoSugerido: $productoSugerido,
            skuSugerido: $skuSugerido,
            marcaSugerida: $marcaSugerida
        );

        return response()->json([
            'ok' => true,
            'question' => [
                'texto_usuario' => $textoUsuario,
                'pregunta_generada' => $preguntaGenerada,
                'producto_solicitado' => $item->descripcion_original,
                'producto_sugerido' => $productoSugerido,
                'sku_sugerido' => $skuSugerido,
                'marca_sugerida' => $marcaSugerida,
                'precio_sugerido' => $precioSugerido,
                'justificacion' => $productoSugerido
                    ? 'Se localizó una posible alternativa en el catálogo interno para someterla a aclaración.'
                    : 'No se cuenta con información técnica suficiente para asegurar equivalencia, por lo que se solicita aclaración formal.',
            ],
        ]);
    }

    public function save(Request $request, PropuestaComercialItem $item)
    {
        $data = $request->validate([
            'texto_usuario' => ['nullable', 'string'],
            'pregunta_generada' => ['required', 'string'],
            'producto_sugerido' => ['nullable', 'string'],
            'sku_sugerido' => ['nullable', 'string'],
            'marca_sugerida' => ['nullable', 'string'],
            'precio_sugerido' => ['nullable', 'numeric'],
            'justificacion' => ['nullable', 'string'],
        ]);

        $propuestaId = $item->propuesta_comercial_id;

        $pregunta = PropuestaAclaracionPregunta::create([
            'propuesta_comercial_id' => $propuestaId,
            'propuesta_comercial_item_id' => $item->id,
            'sort' => ((int) PropuestaAclaracionPregunta::where('propuesta_comercial_id', $propuestaId)->max('sort')) + 1,
            'tipo' => 'aclaracion',
            'estado' => 'borrador',
            'texto_usuario' => $data['texto_usuario'] ?? null,
            'pregunta_generada' => $data['pregunta_generada'],
            'producto_solicitado' => $item->descripcion_original,
            'producto_sugerido' => $data['producto_sugerido'] ?? null,
            'sku_sugerido' => $data['sku_sugerido'] ?? null,
            'marca_sugerida' => $data['marca_sugerida'] ?? null,
            'precio_sugerido' => $data['precio_sugerido'] ?? null,
            'justificacion' => $data['justificacion'] ?? null,
            'fuentes' => [],
            'meta' => [],
        ]);

        return response()->json([
            'ok' => true,
            'question' => $pregunta,
        ]);
    }

    public function delete(PropuestaComercialItem $item, PropuestaAclaracionPregunta $question)
    {
        if ((int) $question->propuesta_comercial_item_id !== (int) $item->id) {
            return response()->json([
                'ok' => false,
                'message' => 'La pregunta no pertenece a esta partida.',
            ], 403);
        }

        $question->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    public function pdf(PropuestaComercial $propuestaComercial)
    {
        $propuestaComercial->load([
            'aclaracionPreguntas.item',
        ]);

        $preguntas = $propuestaComercial->aclaracionPreguntas;

        $html = view('propuestas_comerciales.pdf-junta-aclaraciones', [
            'propuestaComercial' => $propuestaComercial,
            'preguntas' => $preguntas,
        ])->render();

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('letter', 'portrait');

            $folio = $propuestaComercial->folio ?: ('propuesta-' . $propuestaComercial->id);

            return $pdf->download('junta-aclaraciones-' . $folio . '.pdf');
        }

        return response($html);
    }

    private function generarPreguntaFormal(
        string $textoUsuario,
        string $descripcionItem,
        ?string $productoSugerido = null,
        ?string $skuSugerido = null,
        ?string $marcaSugerida = null
    ): string {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            return $this->fallbackPregunta(
                descripcionItem: $descripcionItem,
                textoUsuario: $textoUsuario,
                productoSugerido: $productoSugerido,
                skuSugerido: $skuSugerido,
                marcaSugerida: $marcaSugerida
            );
        }

        $productoAlternativo = $productoSugerido
            ? "{$productoSugerido}" .
                ($skuSugerido ? ", SKU {$skuSugerido}" : "") .
                ($marcaSugerida ? ", marca {$marcaSugerida}" : "")
            : "No se encontró una alternativa exacta en catálogo.";

        $prompt = <<<PROMPT
Redacta una pregunta formal para junta de aclaraciones de una licitación pública.

Producto solicitado:
{$descripcionItem}

Observación del usuario:
{$textoUsuario}

Producto alternativo del catálogo:
{$productoAlternativo}

Instrucciones:
- No inventes datos técnicos.
- Si no hay datos suficientes, pide confirmación de especificaciones.
- Si existe alternativa, pregunta si se puede ofertar como equivalente.
- Redacta en español formal, claro y profesional.
- Devuelve solo la pregunta, sin explicación adicional.
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres un experto en licitaciones públicas mexicanas y redacción de preguntas para juntas de aclaraciones.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.2,
                ]);

            if ($response->successful()) {
                $content = trim((string) data_get($response->json(), 'choices.0.message.content'));

                if ($content !== '') {
                    return $content;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->fallbackPregunta(
            descripcionItem: $descripcionItem,
            textoUsuario: $textoUsuario,
            productoSugerido: $productoSugerido,
            skuSugerido: $skuSugerido,
            marcaSugerida: $marcaSugerida
        );
    }

    private function fallbackPregunta(
        string $descripcionItem,
        string $textoUsuario,
        ?string $productoSugerido,
        ?string $skuSugerido,
        ?string $marcaSugerida
    ): string {
        $observacion = trim($textoUsuario);

        if ($productoSugerido) {
            return "Respecto de la partida correspondiente a “{$descripcionItem}”, solicitamos atentamente a la convocante confirmar si es posible ofertar como equivalente el producto “{$productoSugerido}”" .
                ($skuSugerido ? ", SKU {$skuSugerido}" : "") .
                ($marcaSugerida ? ", marca {$marcaSugerida}" : "") .
                ", siempre que cumpla con las características funcionales, técnicas y de calidad requeridas en las bases de la licitación.";
        }

        return "Respecto de la partida correspondiente a “{$descripcionItem}”, solicitamos atentamente a la convocante aclarar las especificaciones técnicas requeridas, toda vez que con la información proporcionada no es posible identificar con certeza un producto que cumpla exactamente con la descripción solicitada" .
            ($observacion ? ". Asimismo, se hace de su conocimiento la siguiente observación: {$observacion}" : "") .
            ".";
    }

    private function buscarProductoCatalogo(PropuestaComercialItem $item): ?array
    {
        $query = trim((string) $item->descripcion_original);

        if ($query === '') {
            return null;
        }

        if (!class_exists(Product::class)) {
            return null;
        }

        $words = collect(preg_split('/\s+/', mb_strtolower($query)))
            ->map(fn ($word) => trim($word, " \t\n\r\0\x0B.,;:()[]{}\"'"))
            ->filter(fn ($word) => mb_strlen($word) >= 4)
            ->reject(fn ($word) => in_array($word, [
                'para',
                'con',
                'sin',
                'color',
                'pieza',
                'piezas',
                'caja',
                'cajas',
                'paquete',
                'paquetes',
                'unidad',
                'unidades',
            ], true))
            ->take(8)
            ->values();

        if ($words->isEmpty()) {
            return null;
        }

        $productoQuery = Product::query();

        $productoQuery->where(function ($q) use ($words) {
            foreach ($words as $word) {
                $q->orWhere('name', 'like', '%' . $word . '%')
                    ->orWhere('sku', 'like', '%' . $word . '%')
                    ->orWhere('brand', 'like', '%' . $word . '%');
            }
        });

        $producto = $productoQuery->first();

        if (!$producto) {
            return null;
        }

        return [
            'id' => $producto->id,
            'name' => $producto->name ?? null,
            'sku' => $producto->sku ?? null,
            'brand' => $producto->brand ?? null,
            'price' => $producto->price
                ?? $producto->precio
                ?? $producto->sale_price
                ?? null,
        ];
    }
}