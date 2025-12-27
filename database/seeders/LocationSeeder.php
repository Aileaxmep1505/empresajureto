<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // Crea ubicaciones demo para 2 bodegas,
        // PERO con code único global (prefijo por bodega).
        $this->seedWarehouse(1, 'WH1');
        $this->seedWarehouse(2, 'WH2');
    }

    private function seedWarehouse(int $warehouseId, string $prefix): void
    {
        // Layout simple: 1 pasillo, 1 stand, 2 racks, 2 niveles, 3 bins
        $aisle = 'A';
        $stand = '01';
        $rackCount = 2;
        $levels = 2;
        $bins = 3;

        // Posicionamiento (grid units)
        $startX = 8;
        $startY = 8;
        $cellW  = 8;
        $cellH  = 6;
        $gapX   = 3;
        $gapY   = 3;
        $binInnerGap = 1;

        // Ancho total de un rack en X (bins)
        $rackSpanX = ($bins * $cellW) + (($bins - 1) * $binInnerGap);

        $created = 0;

        for ($r = 1; $r <= $rackCount; $r++) {
            $rackNo = str_pad((string)$r, 2, '0', STR_PAD_LEFT);

            // Cada rack se mueve en X
            $rackBaseX = $startX + (($r - 1) * ($rackSpanX + $gapX));
            $rackBaseY = $startY;

            for ($lvl = 1; $lvl <= $levels; $lvl++) {
                $lvlNo = str_pad((string)$lvl, 2, '0', STR_PAD_LEFT);

                for ($b = 1; $b <= $bins; $b++) {
                    $binNo = str_pad((string)$b, 2, '0', STR_PAD_LEFT);

                    // ✅ code único global (prefijo por bodega)
                    // Ej: WH1-A-S01-R01-L01-B01
                    $code = "{$prefix}-{$aisle}-S{$stand}-R{$rackNo}-L{$lvlNo}-B{$binNo}";

                    $x = $rackBaseX + (($b - 1) * ($cellW + $binInnerGap));
                    $y = $rackBaseY + (($lvl - 1) * ($cellH + $gapY));

                    // Idempotente: si existe el code, actualiza; si no, crea
                    $loc = Location::where('code', $code)->first();

                    $data = [
                        'warehouse_id' => $warehouseId,
                        'type'         => 'bin',
                        'code'         => $code,

                        'aisle'   => $aisle,
                        'section' => null, // si usas section puedes poner '01'
                        'stand'   => $stand,
                        'rack'    => $rackNo,
                        'level'   => $lvlNo,
                        'bin'     => $binNo,

                        'name' => "Bin {$code}",
                        'meta' => [
                            'x' => $x,
                            'y' => $y,
                            'w' => $cellW,
                            'h' => $cellH,
                        ],
                    ];

                    if ($loc) {
                        $loc->update($data);
                    } else {
                        Location::create($data);
                        $created++;
                    }
                }
            }
        }

        $this->command?->info("Locations seeded for WH {$warehouseId} ({$prefix}). Created: {$created}");
    }
}
