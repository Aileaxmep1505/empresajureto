<?php

namespace App\Http\Controllers;

use App\Models\PropuestaAclaracionPregunta;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PropuestaAclaracionPreguntaController extends Controller
{
    /**
     * Mientras esté en true, la pregunta generada se antepone con una etiqueta
     * que indica si la redactó la IA o el texto por defecto (respaldo).
     */
    private const DEBUG_FUENTE = false;

    public function suggest(Request $request, PropuestaComercialItem $item)
    {
        $data = $request->validate([
            'texto_usuario' => ['nullable', 'string'],
            'buscar_catalogo' => ['nullable', 'boolean'],
            'buscar_internet' => ['nullable', 'boolean'],
        ]);

        $textoUsuario = trim((string) ($data['texto_usuario'] ?? ''));
        $tieneIdea = $textoUsuario !== '';

        $resultado = $this->generarPreguntaFormal(
            textoUsuario: $textoUsuario,
            tieneIdea: $tieneIdea,
            descripcionItem: (string) $item->descripcion_original,
            unidadSolicitada: (string) ($item->unidad_solicitada ?? ''),
            numeroPartida: $this->numeroPartida($item),
            referencia: $this->referenciaPartida($item)
        );

        $preguntaGenerada = $resultado['texto'];

        if (self::DEBUG_FUENTE) {
            $preguntaGenerada = '⟦ORIGEN: ' . $resultado['fuente'] . '⟧ ' . $preguntaGenerada;
        }

        return response()->json([
            'ok' => true,
            'fuente' => $resultado['fuente'],
            'debug' => $resultado['debug'],
            'question' => [
                'texto_usuario' => $textoUsuario,
                'pregunta_generada' => $preguntaGenerada,
                'producto_solicitado' => $item->descripcion_original,
                'producto_sugerido' => null,
                'sku_sugerido' => null,
                'marca_sugerida' => null,
                'precio_sugerido' => null,
                'justificacion' => $tieneIdea
                    ? 'La pregunta se redactó a partir de la idea proporcionada.'
                    : 'No se proporcionó idea; se solicita a la convocante aclarar las características requeridas de la partida.',
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

        // Por si se guarda con la etiqueta de depuración puesta, se limpia.
        $preguntaLimpia = preg_replace('/^⟦ORIGEN:[^⟧]*⟧\s*/u', '', $data['pregunta_generada']);

        $propuestaId = $item->propuesta_comercial_id;

        $pregunta = PropuestaAclaracionPregunta::create([
            'propuesta_comercial_id' => $propuestaId,
            'propuesta_comercial_item_id' => $item->id,
            'sort' => ((int) PropuestaAclaracionPregunta::where('propuesta_comercial_id', $propuestaId)->max('sort')) + 1,
            'tipo' => 'aclaracion',
            'estado' => 'borrador',
            'texto_usuario' => $data['texto_usuario'] ?? null,
            'pregunta_generada' => $preguntaLimpia,
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

    /**
     * Solo el número de partida (ej. "Partida 4").
     */
    private function numeroPartida(PropuestaComercialItem $item): string
    {
        return !empty($item->sort)
            ? 'Partida ' . (int) $item->sort
            : 'la partida señalada';
    }

    /**
     * Referencia legible de la partida (ej. "Partida 4 · Unidad: PIEZA").
     */
    private function referenciaPartida(PropuestaComercialItem $item): string
    {
        $partes = [];

        if (!empty($item->sort)) {
            $partes[] = 'Partida ' . (int) $item->sort;
        }

        if (!empty($item->unidad_solicitada)) {
            $partes[] = 'Unidad: ' . $item->unidad_solicitada;
        }

        return implode(' · ', $partes);
    }

    /**
     * @return array{texto:string, fuente:string, debug:?string}
     */
    private function generarPreguntaFormal(
        string $textoUsuario,
        bool $tieneIdea,
        string $descripcionItem,
        string $unidadSolicitada = '',
        string $numeroPartida = '',
        string $referencia = ''
    ): array {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            Log::warning('PreguntaAclaracion: no hay services.openai.api_key configurado; usando respaldo.');

            return [
                'texto' => $this->fallbackPregunta($descripcionItem, $textoUsuario, $tieneIdea, $numeroPartida),
                'fuente' => 'RESPALDO · SIN API KEY',
                'debug' => 'config(services.openai.api_key) está vacío.',
            ];
        }

        $systemPrompt = <<<SYS
Eres un especialista en compras públicas y licitaciones en México que redacta observaciones y preguntas para la JUNTA DE ACLARACIONES en nombre del LICITANTE. El giro principal es PAPELERÍA, artículos de oficina y consumibles.

REGLA PRINCIPAL: la redacción debe basarse EXCLUSIVAMENTE en la "Idea de la pregunta" que escribe el usuario y en la descripción de la partida. NUNCA propongas ofertar otro producto, ni inventes marcas, modelos, SKU, equivalencias ni características que el usuario no haya mencionado.

DEBES:
- CORREGIR toda falta de ortografía, acentuación y errores de dedo de la idea del usuario (por ejemplo "eciste" → "existe", "arrillo" → "arillo").
- INTERPRETAR la idea (suele estar escrita de forma informal y breve) y REFORMULARLA con un tono institucional, formal y técnico, propio de un oficio dirigido a una convocante.
- NO copiar la idea literal.

FORMATO DE INICIO OBLIGATORIO:
Toda redacción debe COMENZAR citando el número de partida y la descripción de la partida, con este formato exacto:
«En relación con la {NUMERO_PARTIDA}, referente a "{DESCRIPCION_DE_LA_PARTIDA}", ...»
y a partir de ahí continúa con la observación o la pregunta.

Tienes DOS modos:

MODO A — El usuario SÍ escribió una idea:
- Si la idea es una afirmación o aclaración (por ejemplo, que cierta medida/característica no existe o no está disponible), redáctala como una OBSERVACIÓN formal, usando fórmulas como "se precisa que…", "se hace de su conocimiento que…", y de ser pertinente cierra solicitando la confirmación o el ajuste correspondiente.
- Si la idea es una duda, redáctala como una pregunta de aclaración formal.
- Respeta estrictamente la intención del usuario; no agregues alternativas que él no haya planteado (salvo, si él mismo menciona una medida/dato, puedes citarlo).

MODO B — El usuario NO escribió ninguna idea:
- Redacta una observación/pregunta pidiendo a la convocante que PRECISE las características técnicas mínimas requeridas del artículo (medidas, material, gramaje, color, presentación/contenido del empaque, marca de referencia o si acepta equivalente), porque con la descripción actual no es posible cotizar con certeza. No sugieras ningún producto, marca ni modelo.

Reglas de redacción:
- Español formal, impersonal, en tercera persona, tono institucional.
- UNA sola redacción clara (una o dos oraciones). Nunca una lista ni varias preguntas mezcladas.
- No inventes datos técnicos, cantidades ni números que no estén en la información dada.
- Devuelve ÚNICAMENTE el texto final. Sin encabezados, sin viñetas, sin comillas envolventes, sin explicación.

EJEMPLO de transformación (MODO A):
Número de partida: "Partida 4"
Descripción de la partida: "ARILLO METÁLICO PARA ENGARGOLAR DE 3/8\" DE DIÁMETRO COLOR NEGRO, EN PRESENTACIÓN DE CAJA C/20 PIEZAS."
Idea del usuario: "no eciste arrillo metalico para engargolar de 3/8 solo de 1/4"
Salida esperada: "En relación con la Partida 4, referente a \"ARILLO METÁLICO PARA ENGARGOLAR DE 3/8\" DE DIÁMETRO COLOR NEGRO, EN PRESENTACIÓN DE CAJA C/20 PIEZAS\", se precisa que el arillo metálico para engargolar no se encuentra disponible en el mercado en la medida de 3/8\" solicitada, siendo la medida comercial disponible la de 1/4\". Por lo anterior, se solicita atentamente a la convocante confirmar si se acepta ofertar dicha medida equivalente que cumpla con la función requerida."
SYS;

        $modo = $tieneIdea ? 'MODO A' : 'MODO B';

        $userPrompt = <<<USR
Modo a aplicar: {$modo}

NUMERO_PARTIDA: {$numeroPartida}
Referencia adicional: {$referencia}
Unidad solicitada: {$unidadSolicitada}

DESCRIPCION_DE_LA_PARTIDA (tal como aparece en las bases):
{$descripcionItem}

Idea de la pregunta escrita por el usuario (puede estar vacía, puede tener errores de ortografía):
{$textoUsuario}

Redacta el texto formal para la junta de aclaraciones. COMIENZA con el formato obligatorio "En relación con la {NUMERO_PARTIDA}, referente a \"{DESCRIPCION_DE_LA_PARTIDA}\", ...", corrige la ortografía e interpreta la idea con tono institucional; no la copies literal.
USR;

        $model = config('services.openai.model', 'gpt-4.1-nano-2025-04-14');

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    // No se envía 'temperature': algunos modelos solo aceptan el valor por defecto (1).
                ]);

            if ($response->successful()) {
                $content = trim((string) data_get($response->json(), 'choices.0.message.content'));
                $content = trim($content, " \t\n\r\0\x0B\"'“”");

                if ($content !== '') {
                    return [
                        'texto' => $content,
                        'fuente' => 'IA',
                        'debug' => 'Modelo: ' . $model,
                    ];
                }

                Log::warning('PreguntaAclaracion: OpenAI respondió vacío.', ['body' => $response->json()]);

                return [
                    'texto' => $this->fallbackPregunta($descripcionItem, $textoUsuario, $tieneIdea, $numeroPartida),
                    'fuente' => 'RESPALDO · RESPUESTA VACÍA',
                    'debug' => 'Modelo: ' . $model . '. La API respondió 200 pero sin contenido.',
                ];
            }

            Log::warning('PreguntaAclaracion: OpenAI no exitoso.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'texto' => $this->fallbackPregunta($descripcionItem, $textoUsuario, $tieneIdea, $numeroPartida),
                'fuente' => 'RESPALDO · ERROR HTTP ' . $response->status(),
                'debug' => 'Modelo: ' . $model . '. Respuesta: ' . mb_substr($response->body(), 0, 300),
            ];
        } catch (\Throwable $e) {
            Log::error('PreguntaAclaracion: excepción al llamar OpenAI.', ['error' => $e->getMessage()]);
            report($e);

            return [
                'texto' => $this->fallbackPregunta($descripcionItem, $textoUsuario, $tieneIdea, $numeroPartida),
                'fuente' => 'RESPALDO · EXCEPCIÓN',
                'debug' => 'Modelo: ' . $model . '. Error: ' . $e->getMessage(),
            ];
        }
    }

    private function fallbackPregunta(
        string $descripcionItem,
        string $textoUsuario,
        bool $tieneIdea,
        string $numeroPartida = ''
    ): string {
        $observacion = trim($textoUsuario);
        $partida = $numeroPartida !== '' ? $numeroPartida : 'la partida señalada';
        $prefijo = "En relación con la {$partida}, referente a “{$descripcionItem}”";

        if ($tieneIdea && $observacion !== '') {
            return "{$prefijo}, se solicita atentamente a la convocante aclarar lo siguiente: {$observacion}. Por lo anterior, se solicita confirmar las especificaciones aplicables o la alternativa equivalente que deba considerarse para cotizar correctamente.";
        }

        return "{$prefijo}, se solicita atentamente a la convocante precisar las características técnicas mínimas requeridas (según corresponda: medidas, material, gramaje, color, presentación y contenido del empaque, así como marca de referencia o si se acepta equivalente), toda vez que con la descripción proporcionada no es posible identificar con certeza el artículo solicitado y cotizar correctamente.";
    }
}