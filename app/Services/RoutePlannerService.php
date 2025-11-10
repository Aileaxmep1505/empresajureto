<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Planificador de rutas sin dependencias externas.
 * - Asigna paradas al chofer más cercano (evita traslapes).
 * - Optimiza el orden de visita por chofer (Nearest Neighbor + 2-Opt).
 * - Estima tiempos con velocidad promedio urbana.
 */
class RoutePlannerService
{
    /**
     * @param array $drivers [
     *   ['id'=>1,'name'=>'Chofer A','lat'=>19.43,'lng'=>-99.13,'start_at'=>'2025-11-03 08:00:00'],
     *   ['id'=>2,'name'=>'Chofer B','lat'=>19.39,'lng'=>-99.17,'start_at'=>'2025-11-03 08:00:00'],
     * ]
     * @param array $stops [
     *   ['id'=>101,'name'=>'Proveedor X','lat'=>19.45,'lng'=>-99.12,'service_minutes'=>5],
     * ]
     * @param array $opts [
     *   'avg_speed_kmh' => 35,                 // velocidad promedio ciudad
     *   'tz'            => 'America/Mexico_City'
     * ]
     * @return array
     */
    public function plan(array $drivers, array $stops, array $opts = []): array
    {
        $avgSpeedKmh = max(10, (int)($opts['avg_speed_kmh'] ?? 35));
        $tz          = $opts['tz'] ?? 'America/Mexico_City';

        // Normalizaciones
        foreach ($stops as &$s) {
            $s['service_minutes'] = isset($s['service_minutes']) ? max(0, (int)$s['service_minutes']) : 5;
            $s['name'] = $s['name'] ?? ('Stop '.$s['id']);
        }
        unset($s);
        foreach ($drivers as &$d) {
            $d['name'] = $d['name'] ?? ('Driver '.$d['id']);
        }
        unset($d);

        // Construcción de puntos: drivers primero, luego stops
        $points = [];
        foreach ($drivers as $d) $points[] = ['type'=>'driver'] + $d;
        foreach ($stops as $s)   $points[] = ['type'=>'stop']   + $s;

        // Matriz de distancias & duraciones (Haversine + velocidad promedio)
        $matrix = $this->buildMatrix($points, $avgSpeedKmh);

        // Asignación: cada stop al chofer más cercano (sin traslapes)
        $clusters = $this->assignStopsToDrivers($drivers, $stops);

        // Mapas útiles
        $driversCount = count($drivers);
        $pointIndexByStopId = $this->mapStopIdToPointIndex($stops, $driversCount);
        $stopByPointIndex   = [];
        foreach ($stops as $i=>$s) { $stopByPointIndex[$driversCount+$i] = $s; }

        // Rutas por chofer
        $routes = [];
        foreach ($drivers as $di => $driver) {
            $driverIdx = $di; // en $points, drivers están al inicio
            $assignedStopIds = $clusters[$driver['id']] ?? [];
            if (empty($assignedStopIds)) {
                $routes[] = [
                    'driver'   => $driver,
                    'order'    => [],
                    'coords'   => [ $this->asCoord($driver) ],
                    'metrics'  => ['drive_minutes'=>0,'service_minutes'=>0,'total_minutes'=>0,'distance_km'=>0],
                    'schedule' => [],
                ];
                continue;
            }

            // Índices de puntos para los stops asignados
            $stopIdxs = [];
            foreach ($assignedStopIds as $sid) {
                if (isset($pointIndexByStopId[$sid])) $stopIdxs[] = $pointIndexByStopId[$sid];
            }

            // Orden de visita: driver -> paradas (NN + 2-Opt sobre la subruta)
            $routeIdx = $this->nearestNeighbor($matrix['durations'], $driverIdx, $stopIdxs);
            $routeIdx = $this->twoOpt($matrix['durations'], $routeIdx); // mantiene el primero fijo

            // Coordenadas para dibujar o depurar
            $coords = array_map(fn($pi)=> $this->asCoord($points[$pi]), $routeIdx);

            // Métricas (sólo las paradas de esta ruta)
            $metrics = $this->computeMetrics($matrix, $routeIdx, $stopByPointIndex);

            // Agenda (ETAs) desde start_at (o ahora)
            $schedule = $this->buildSchedule($matrix, $routeIdx, $stopByPointIndex, $driver, $tz);

            // Orden (ids de stop) excluyendo el primer índice (driver)
            $orderedStopIds = [];
            foreach (array_slice($routeIdx, 1) as $pi) {
                $orderedStopIds[] = $points[$pi]['id'];
            }

            $routes[] = [
                'driver'   => $driver,
                'order'    => $orderedStopIds,
                'coords'   => $coords,
                'metrics'  => $metrics,
                'schedule' => $schedule,
            ];
        }

        return [
            'routes'     => $routes,
            'unassigned' => $this->diffStops($stops, $clusters),
            'engine'     => ['avg_speed_kmh'=>$avgSpeedKmh,'tz'=>$tz],
            'warnings'   => [],
        ];
    }

    // ------------------------- Distancias / tiempos -------------------------

    private function buildMatrix(array $points, int $avgKmh): array
    {
        $n    = count($points);
        $dur  = array_fill(0, $n, array_fill(0, $n, 0.0)); // segundos
        $dist = array_fill(0, $n, array_fill(0, $n, 0.0)); // metros
        $mps  = max(1, ($avgKmh * 1000) / 3600); // metros/segundo

        for ($i=0; $i<$n; $i++) {
            for ($j=0; $j<$n; $j++) {
                if ($i === $j) continue;
                $km = $this->haversine($points[$i]['lat'], $points[$i]['lng'], $points[$j]['lat'], $points[$j]['lng']);
                $meters      = $km * 1000;
                $dist[$i][$j]= $meters;
                $dur[$i][$j] = $meters / $mps;
            }
        }
        return ['durations'=>$dur,'distances'=>$dist];
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c; // km
    }

    // ------------------------- Clustering (asignación) -------------------------

    private function assignStopsToDrivers(array $drivers, array $stops): array
    {
        $clusters = [];
        foreach ($drivers as $d) $clusters[$d['id']] = [];

        foreach ($stops as $s) {
            $bestDriver = null; $bestKm = INF;
            foreach ($drivers as $d) {
                $km = $this->haversine($d['lat'], $d['lng'], $s['lat'], $s['lng']);
                if ($km < $bestKm) { $bestKm = $km; $bestDriver = $d['id']; }
            }
            $clusters[$bestDriver][] = $s['id'];
        }
        return $clusters;
    }

    private function mapStopIdToPointIndex(array $stops, int $driversCount): array
    {
        $map = [];
        foreach ($stops as $i=>$s) $map[$s['id']] = $driversCount + $i;
        return $map;
    }

    private function asCoord(array $p): array
    {
        return ['lat'=>(float)$p['lat'],'lng'=>(float)$p['lng']];
    }

    private function diffStops(array $stops, array $clusters): array
    {
        $all = array_column($stops, 'id');
        $assigned = [];
        foreach ($clusters as $a) $assigned = array_merge($assigned, $a);
        return array_values(array_diff($all, $assigned));
    }

    // ------------------------- TSP Heuristics -------------------------

    /**
     * Ruta inicial: [driverIndex, ...stopIndexes...]
     */
    private function nearestNeighbor(array $durations, int $driverIndex, array $stopIndexes): array
    {
        $unvisited = $stopIndexes;
        $route = [$driverIndex];
        $current = $driverIndex;

        while (!empty($unvisited)) {
            $bestKey = null; $bestD = INF;
            foreach ($unvisited as $k=>$idx) {
                $d = $durations[$current][$idx] ?? INF;
                if ($d < $bestD) { $bestD = $d; $bestKey = $k; }
            }
            $current = $unvisited[$bestKey];
            $route[] = $current;
            array_splice($unvisited, $bestKey, 1);
        }
        return $route;
    }

    /**
     * 2-Opt sobre la subruta (mantiene el primer nodo fijo).
     */
    private function twoOpt(array $dur, array $route): array
    {
        $n = count($route);
        if ($n <= 3) return $route;

        $improved = true;
        while ($improved) {
            $improved = false;
            for ($i=1; $i<$n-2; $i++) {
                for ($k=$i+1; $k<$n; $k++) {
                    $new = $this->twoOptSwap($route, $i, $k);
                    if ($this->routeCost($dur, $new) < $this->routeCost($dur, $route)) {
                        $route = $new; $improved = true;
                    }
                }
            }
        }
        return $route;
    }

    private function twoOptSwap(array $route, int $i, int $k): array
    {
        $start = array_slice($route, 0, $i);
        $mid   = array_slice($route, $i, $k-$i+1);
        $end   = array_slice($route, $k+1);
        return array_merge($start, array_reverse($mid), $end);
    }

    private function routeCost(array $d, array $route): float
    {
        $c = 0.0;
        for ($i=0; $i<count($route)-1; $i++) {
            $c += $d[$route[$i]][$route[$i+1]] ?? INF;
        }
        return $c;
    }

    // ------------------------- Métricas & Schedule -------------------------

    private function computeMetrics(array $matrix, array $routeIdx, array $stopByPointIndex): array
    {
        $dist = 0.0; $driveSec = 0.0; $svcMin = 0;

        for ($i=0; $i<count($routeIdx)-1; $i++) {
            $a = $routeIdx[$i]; $b = $routeIdx[$i+1];
            $dist     += $matrix['distances'][$a][$b] ?? 0.0;
            $driveSec += $matrix['durations'][$a][$b] ?? 0.0;

            // si b es un stop, suma su tiempo de servicio
            if (isset($stopByPointIndex[$b])) {
                $svcMin += (int)($stopByPointIndex[$b]['service_minutes'] ?? 0);
            }
        }

        return [
            'drive_minutes'   => (int)round($driveSec / 60),
            'service_minutes' => (int)$svcMin,
            'total_minutes'   => (int)round($driveSec / 60) + (int)$svcMin,
            'distance_km'     => round($dist / 1000, 2),
        ];
    }

    private function buildSchedule(array $matrix, array $routeIdx, array $stopByPointIndex, array $driver, string $tz): array
    {
        $startAt = isset($driver['start_at'])
            ? Carbon::parse($driver['start_at'], $tz)
            : Carbon::now($tz)->setTime(8,0);

        $time = $startAt->copy();
        $schedule = [];

        for ($i=0; $i<count($routeIdx)-1; $i++) {
            $a = $routeIdx[$i]; $b = $routeIdx[$i+1];
            $drive = (int)round($matrix['durations'][$a][$b] ?? 0);
            $time->addSeconds($drive);

            $entry = [
                'point_index' => $b,
                'eta'         => $time->format('Y-m-d H:i:s'),
            ];

            if (isset($stopByPointIndex[$b])) {
                $sid = $stopByPointIndex[$b]['id'];
                $entry['stop_id']   = $sid;
                $entry['stop_name'] = $stopByPointIndex[$b]['name'] ?? ('Stop '.$sid);
                $svcMin = (int)($stopByPointIndex[$b]['service_minutes'] ?? 0);
                $time->addMinutes($svcMin);
                $entry['depart_at'] = $time->format('Y-m-d H:i:s');
            }
            $schedule[] = $entry;
        }
        return $schedule;
    }
}
