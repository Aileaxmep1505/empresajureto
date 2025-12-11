<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct(
        private ?string $q = null,
    ) {}

    /** Solo productos con nombre o SKU, respetando filtro q */
    public function query()
    {
        $query = Product::query()
            ->where(function ($qq) {
                $qq->whereNotNull('name')->where('name', '!=', '')
                   ->orWhere(function ($q2) {
                       $q2->whereNotNull('sku')->where('sku', '!=', '');
                   });
            });

        if ($this->q !== null && trim($this->q) !== '') {
            $q = trim($this->q);
            $query->where(function($qq) use ($q){
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('sku', 'like', "%{$q}%")
                   ->orWhere('brand', 'like', "%{$q}%")
                   ->orWhere('category', 'like', "%{$q}%")
                   ->orWhere('tags', 'like', "%{$q}%")
                   ->orWhere('clave_sat', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'SKU',
            'Supplier SKU',
            'Marca',
            'Categoría',
            'Unidad',
            'Color',
            'Peso',
            'Costo',
            'Precio',
            'Precio Mercado',
            'Precio Licitación',
            'Piezas por unidad',
            'Material',
            'Clave SAT',
            'Tags',
            'Descripción',
            'Notas',
            'image_path',
            'image_url',
            'Activo',
            'Creado',
        ];
    }

    public function map($p): array
    {
        return [
            $p->id,
            $p->name,
            $p->sku,
            $p->supplier_sku,
            $p->brand,
            $p->category,
            $p->unit,
            $p->color,
            $p->weight,
            $p->cost,
            $p->price,
            $p->market_price,
            $p->bid_price,
            $p->pieces_per_unit,
            $p->material,
            $p->clave_sat,
            $p->tags,
            $p->description,
            $p->notes,
            $p->image_path,
            $p->image_url,
            $p->active ? 'ACTIVO' : 'INACTIVO',
            optional($p->created_at)->format('Y-m-d'),
        ];
    }

    /** Ancho de columnas (en “caracteres” aprox.) */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 40,  // Nombre (más corto, con wrap)
            'C' => 16,  // SKU
            'D' => 18,  // Supplier SKU
            'E' => 16,  // Marca
            'F' => 18,  // Categoría
            'G' => 10,  // Unidad
            'H' => 10,  // Color
            'I' => 8,   // Peso
            'J' => 12,  // Costo
            'K' => 12,  // Precio
            'L' => 14,  // Precio Mercado
            'M' => 14,  // Precio Licitación
            'N' => 12,  // Piezas por unidad
            'O' => 14,  // Material
            'P' => 12,  // Clave SAT
            'Q' => 25,  // Tags
            'R' => 45,  // Descripción
            'S' => 35,  // Notas
            'T' => 30,  // image_path
            'U' => 30,  // image_url
            'V' => 10,  // Activo
            'W' => 12,  // Creado
        ];
    }

    /** Estilos: encabezado + wrapText en columnas largas */
    public function styles(Worksheet $sheet)
    {
        // Encabezados
        $sheet->getStyle('A1:W1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'fill' => [
                'fillType' => 'solid',
                'color'    => ['argb' => 'FFE5E7EB'],
            ],
        ]);

        // Wrap text para columnas con textos largos
        $sheet->getStyle('B:B')->getAlignment()->setWrapText(true); // Nombre
        $sheet->getStyle('Q:U')->getAlignment()->setWrapText(true); // Tags, desc, notas, paths, urls

        // Alinear al tope las celdas para que se vean “tipo ficha”
        $sheet->getStyle('A:W')->getAlignment()->setVertical('top');

        // Congelar encabezado
        $sheet->freezePane('A2');

        return [];
    }
}
