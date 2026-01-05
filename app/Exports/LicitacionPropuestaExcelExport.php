<?php

namespace App\Exports;

use App\Models\LicitacionPropuesta;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class LicitacionPropuestaExcelExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private LicitacionPropuesta $propuesta) {}

    public function title(): string
    {
        return 'Propuesta';
    }

    public function headings(): array
    {
        return [
            '#',
            'Solicitado',
            'Página',
            'SKU',
            'Producto ofertado',
            'Marca',
            'Unidad',
            'Cantidad',
            'Precio unitario',
            'Utilidad %',
            'Utilidad $',
            'Subtotal base',
            'Subtotal + utilidad',
        ];
    }

    public function array(): array
    {
        $p = $this->propuesta->load([
            'items.requestItem.page',
            'items.product',
        ]);

        $rows = [];
        foreach ($p->items as $idx => $item) {
            $req  = $item->requestItem;
            $prod = $item->product;

            $renglon = $req?->renglon ?? ($idx + 1);

            $solicitado = (string)($req?->line_raw ?: ($item->descripcion_raw ?: ''));
            $pagina = $req?->page?->page_number ?? null;

            $sku   = $prod?->sku ?? '';
            $name  = $prod?->name ?? '';
            $brand = $prod?->brand ?? '';
            $unit  = $item->unidad_propuesta ?? ($prod?->unit ?? '');

            $cantidad = (float)($item->cantidad_propuesta ?? ($req?->cantidad ?? 0));
            $precio   = (float)($item->precio_unitario ?? 0);

            $utilPct  = (float)($item->utilidad_pct ?? 0);
            $utilMonto = (float)($item->utilidad_monto ?? 0);

            // OJO: en tu vista usas subtotal/subtotal_base/subtotal_con_utilidad.
            // Aquí lo hacemos “tolerante”:
            $subtotalBase = (float)($item->subtotal_base ?? $item->subtotal ?? 0);
            $subtotalConUtil = (float)($item->subtotal_con_utilidad ?? ($subtotalBase + $utilMonto));

            $rows[] = [
                $renglon,
                $solicitado,
                $pagina,
                $sku,
                $name,
                $brand,
                $unit,
                $cantidad,
                $precio,
                $utilPct,
                $utilMonto,
                $subtotalBase,
                $subtotalConUtil,
            ];
        }

        // (Opcional) fila vacía + totales al final
        $rows[] = [];
        $rows[] = [
            '', 'TOTALES', '', '', '', '', '',
            '',
            '',
            '',
            (float)($p->utilidad_total ?? 0),
            (float)($p->subtotal_base ?? $p->items->sum('subtotal') ?? 0),
            (float)($p->subtotal ?? 0),
        ];

        return $rows;
    }
}
