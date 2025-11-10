<?php

namespace App\Services;

class RouteAiAdvisor
{
    public function advise(array $routes, array $context = []): string
    {
        // routes[]: [
        //   'label','total_sec','total_m','legs'=>[{severity,adj_duration,distance,speed_kmh...}],
        //   'fuel'=>['mxn',...], 'toll'=>['has_toll','estimated_mxn'...]
        // ]

        if (empty($routes)) return 'No se encontraron rutas para analizar.';

        // Selección naive: menor tiempo ajustado
        usort($routes, fn($a,$b) => ($a['total_sec']??PHP_INT_MAX) <=> ($b['total_sec']??PHP_INT_MAX));
        $best = $routes[0];

        $mk = [];
        $mk[] = "### 🚗 Ruta sugerida: **{$best['label']}**";
        $mk[] = sprintf("- **Tiempo total**: ~%d min", round(($best['total_sec'] ?? 0)/60));
        $mk[] = sprintf("- **Distancia**: %.1f km", ($best['total_m'] ?? 0)/1000);
        if (!empty($best['fuel'])) {
            $mk[] = sprintf("- **Combustible**: ~$%s MXN (%.1f km · %.2f L · $%.2f/L %s)",
                number_format($best['fuel']['mxn'] ?? 0, 2),
                $best['fuel']['km'] ?? 0,
                $best['fuel']['liters'] ?? 0,
                $best['fuel']['price'] ?? 0,
                $best['fuel']['fuel'] === 'diesel' ? 'diésel' : 'gasolina'
            );
        }
        if (!empty($best['toll'])) {
            $mk[] = sprintf("- **Peajes**: %s (estimado: $%s MXN)",
                $best['toll']['has_toll'] ? 'con casetas' : 'libre',
                number_format($best['toll']['estimated_mxn'] ?? 0, 2)
            );
        }

        $heavy = array_values(array_filter($best['legs'] ?? [], fn($l)=>($l['severity']??'')==='heavy'));
        $moder = array_values(array_filter($best['legs'] ?? [], fn($l)=>($l['severity']??'')==='moderate'));
        if (count($heavy) || count($moder)) {
            $mk[] = "- **Tráfico**:";
            if (count($heavy))  $mk[] = "  - 🟥 Tramos con retención pesada: **".count($heavy)."** (se pintan en rojo).";
            if (count($moder)) $mk[] = "  - 🟨 Tramos con congestión moderada: **".count($moder)."** (se pintan en amarillo).";
        } else {
            $mk[] = "- **Tráfico**: fluido en la mayor parte del trayecto.";
        }

        $mk[] = "";
        $mk[] = "#### 💡 Recomendación";
        $mk[] = "- Se prioriza **menor tiempo estimado**.";
        $mk[] = "- Considera la alternativa verde si prefieres **evitar zonas rojas** aunque tarde un poco más.";
        $mk[] = "- Si necesitas **ruta libre de casetas**, elige una marcada como *libre* en el comparativo.";

        // ===== (Opcional) LLM: si tienes AiService configurable, puedes enriquecer:
        // $extra = app(\App\Services\AiService::class)->shortExplain($routes, $context);
        // if ($extra) $mk[] = "\n".$extra;

        return implode("\n", $mk);
    }
}
