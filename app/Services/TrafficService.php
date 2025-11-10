<?php

namespace App\Services;

class TrafficService
{
    /**
     * Aplica demoras estimadas por tráfico a cada leg y añade severidad.
     * Si no hay proveedor externo, usa heurística:
     *   speed_kmh = (distance_m / duration_s) * 3.6
     *   speed < 10  => heavy (rojo)   +45%
     *   speed < 25  => moderate (amarillo) +20%
     *   else        => light (verde)  +0-5%
     *
     * @param array $legs  Cada leg: ['distance'=>int(m), 'duration'=>int(s), 'from'=>[lat,lng], 'to'=>[lat,lng]]
     * @return array       Mismos legs + adj_delay, adj_duration, severity
     */
    public function applyDelays(array $legs): array
    {
        $out = [];
        foreach ($legs as $leg) {
            $dist = max(1, (int) ($leg['distance'] ?? 0));
            $dur  = max(1, (int) ($leg['duration'] ?? 0));
            $speed = ($dist / $dur) * 3.6; // km/h

            $severity = 'light';
            $factor   = 1.03;   // +3% por semáforos y arranques
            if ($speed < 10) {  // tráfico pesado
                $severity = 'heavy';
                $factor   = 1.45;
            } elseif ($speed < 25) { // tráfico moderado
                $severity = 'moderate';
                $factor   = 1.20;
            }

            $adj = (int) round($dur * $factor);
            $out[] = array_merge($leg, [
                'speed_kmh'    => round($speed, 1),
                'severity'     => $severity,
                'adj_delay'    => max(0, $adj - $dur),
                'adj_duration' => $adj,
            ]);
        }
        return $out;
    }

    /**
     * Genera un resumen legible por humanos para IA/UX.
     */
    public function summarize(array $legs): array
    {
        $heavy = 0; $moderate = 0; $light = 0;
        foreach ($legs as $l) {
            if ($l['severity'] === 'heavy') $heavy++;
            elseif ($l['severity'] === 'moderate') $moderate++;
            else $light++;
        }
        return [
            'heavy_count'    => $heavy,
            'moderate_count' => $moderate,
            'light_count'    => $light,
        ];
    }
}
