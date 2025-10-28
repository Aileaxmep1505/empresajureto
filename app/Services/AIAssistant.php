<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AIAssistant
{
    /**
     * Responde SIEMPRE con IA (sin BD). Si el texto pide humano, devuelve null y
     * tu controlador escalará el ticket.
     */
    public static function answer(string $userMessage, array $ctx = []): ?string
    {
        // Si el usuario pide humano, no contestamos (deja que el controlador escale).
        if (self::wantsHuman($userMessage)) {
            return null;
        }

        $apiKey = config('services.openai.key');
        $model  = config('services.openai.model', 'gpt-4o-mini');

        if (!$apiKey) {
            // Sin API key no hay IA real. Manda un texto cortesía para no quedar mudo.
            return "Puedo ayudarte con cambios, devoluciones, envíos, existencias y compras de papelería, equipo de cómputo y mobiliario. Cuéntame tu caso (pedido, artículo y qué ocurrió) y te daré los pasos exactos. Si prefieres, puedo ponerte con un asesor humano.";
        }

        // Instrucciones del asistente (especializado en retail papelería/cómputo/muebles)
        $system = trim("
Eres un asesor de soporte y ventas de una tienda en línea de PAPELERÍA, EQUIPO DE CÓMPUTO y MUEBLES DE OFICINA.
Objetivo: resolver rápido, con lenguaje claro, pasos concretos y empatía. No dependes de base de datos interna.
Si falta información, pide SOLO lo imprescindible (máximo 1-2 preguntas), luego propone solución.
Estructura SIEMPRE:
1) Resumen en 1 línea.
2) Solución/pasos accionables (bullets cortos).
3) Opciones (cambio, reembolso, envío, seguimiento, etc.) si aplica.
4) ¿Necesitas algo más? (cierre proactivo).

Políticas guía (genéricas, no cites reglas internas):
- Cambios/devoluciones: ventana razonable; si artículo incorrecto/dañado, tienda cubre el retorno.
- Envíos: ofrece verificar tiempos y rastreo; si retraso anormal, abre gestión con paquetería.
- Facturación: se puede emitir si el cliente comparte datos correctos en tiempo.
- Stock/compatibilidad: orienta y sugiere alternativas si algo no aplica.
- Muebles: considera medidas, peso y armado; sugiere paquetería/instalación cuando corresponda.

Tono: profesional, cercano, concreto. Evita muros de texto y tecnicismos inútiles.
NO inventes números de pedido. NO prometas fechas exactas; da rangos y siguientes pasos.
Si el usuario pide humano, responde con una sola línea: [ESCALAR_HUMANO].
        ");

        // Contexto opcional del ticket (título/categoría ayudan a orientar el tono)
        $contextText = '';
        if (!empty($ctx['ticket_subject'])) $contextText .= "Asunto: {$ctx['ticket_subject']}\n";
        if (!empty($ctx['category']))       $contextText .= "Categoría: {$ctx['category']}\n";

        $user = trim($contextText . "Mensaje: " . $userMessage);

        $resp = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model'      => $model,
            'temperature'=> 0.2,
            'messages'   => [
                ['role'=>'system', 'content'=>$system],
                ['role'=>'user',   'content'=>$user],
            ],
        ]);

        if (!$resp->ok()) {
            return "Estoy teniendo dificultades para generar la respuesta. ¿Deseas que te contacte un asesor humano?";
        }

        $text = trim((string) data_get($resp->json(), 'choices.0.message.content', ''));

        // Si el modelo detectó que hay que escalar a humano
        if (Str::contains($text, '[ESCALAR_HUMANO]')) {
            return null;
        }

        // Limpieza mínima (evita dobles saltos excesivos)
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return $text ?: "¿Podrías contarme un poco más para ayudarte mejor? (ej.: número de pedido o qué ocurrió con el artículo)";
    }

    protected static function wantsHuman(string $msg): bool
    {
        $m = Str::of($msg)->lower();
        return $m->contains([
            'asesor', 'humano', 'agente', 'ejecutivo', 'quiero hablar con alguien',
            'contactar a un humano', 'contactar humano', 'quiero un asesor', 'pasar a humano'
        ]);
    }
}
