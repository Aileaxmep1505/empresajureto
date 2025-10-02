<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Throwable;

class ProductsImport implements
    OnEachRow, WithHeadingRow, WithChunkReading,
    SkipsOnError, SkipsOnFailure, WithValidation
{
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    private bool $loggedHeaderOnce = false;

    /** Mapa: encabezado normalizado -> índice de columna (1-based) */
    private array $headerIndex = [];
    private ?int $imageUrlCol  = null;   // Columna de "Imagen URL"
    private ?int $imagePathCol = null;   // Columna de "Imagen" / "image_path"
    private bool $headerBuilt  = false;

    public function __construct(private bool $downloadImages = false) {} // no descargamos

    public function chunkSize(): int { return 1000; }
    public function headingRow(): int { return 1; }

    /* ==================== Utils ==================== */

    private function norm(?string $s): string
    {
        $s = (string)($s ?? '');
        $s = Str::of($s)->lower();
        $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
        $s = preg_replace('/[^a-z0-9]+/i', '_', $s);
        return trim($s, '_');
    }

    private function aliases(): array
    {
        return [
            'name'            => ['name','nombre','descripcion_del_producto','descripcion_de_producto','descripcion_producto','nombre_del_producto'],
            'description'     => ['description','descripcion','descripcion_larga','descripcion_del_producto','descripcion_de_producto','descripcion_producto'],

            'sku'             => ['sku','sku_codigo_del_producto','codigo_del_producto','codigo_de_producto','codigo_producto','codigo','skucodigo_del_producto'],
            'supplier_sku'    => ['supplier_sku','sku_prov','sku_proveedor','codigo_proveedor','sku_del_proveedor','sku_proveedor'],

            'brand'           => ['brand','marca'],
            'category'        => ['category','categoria','categoria_','categor_a'],
            'material'        => ['material'],
            'notes'           => ['notes','notas','notas_adicionales'],
            'tags'            => ['tags','etiquetas'],

            'price'           => ['price','precio'],
            'market_price'    => ['market_price','precio_mercado'],
            'bid_price'       => ['bid_price','precio_licitacion','precio_licitaci_n','precio_licitación'],
            'cost'            => ['cost','costo','costo_jureto','costo_jureto_'],
            'weight'          => ['weight','peso','peso_kg','peso__kg','peso_kg_','peso_kg__','pesokg'],
            'pieces_per_unit' => ['pieces_per_unit','piezas_por_unidad','pzs_u'],

            'unit'            => ['unit','unidad'],
            'dimensions'      => ['dimensions','dimensiones','dimensiones_centimetros','dimensiones_cm','dimensiones__centimetros_'],
            'color'           => ['color'],
            'active'          => ['active','activo','estado'],

            'image_url'       => ['image_url','imagen_url','url_imagen'],
            'image_path'      => ['image_path','imagen_path','image','imagen'],
        ];
    }

    private function get(array $r, array $keys, $default = null)
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $r) && $r[$k] !== '' && $r[$k] !== null) {
                return is_string($r[$k]) ? trim($r[$k]) : $r[$k];
            }
        }
        return $default;
    }

    private function num($v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (float)$v;
        $v = strtoupper((string)$v);
        $v = str_replace(['$', 'MXN', 'USD', ' ', ','], '', $v);
        $v = str_replace(['KG','G','CM','MM'], '', $v);
        $v = str_replace(['–','—'], '-', $v);
        $v = preg_replace('/[^0-9.\-]/', '', $v);
        return $v === '' || $v === '-' ? null : (float)$v;
    }

    private function boolish($v): ?bool
    {
        if ($v === null || $v === '') return null;
        $v = Str::lower((string)$v);
        if (in_array($v, ['1','true','si','sí','yes','y','activo','disponible','habilitado'], true)) return true;
        if (in_array($v, ['0','false','no','inactivo','descontinuado','agotado'], true)) return false;
        return null;
    }

    /** Detecta URL (incluye Drive sin esquema) */
    private function isUrl(?string $s): bool
    {
        if (!is_string($s) || $s === '') return false;
        $s = trim($s);
        if (preg_match('~^(https?:)?\/\/~i', $s)) return true;
        if (str_starts_with($s, 'drive.google.com') || str_starts_with($s, 'docs.google.com')) return true;
        return false;
    }

    /** Normaliza enlaces de Drive a uc?id= */
    private function normalizeDrive(?string $url): ?string
    {
        if (!$url) return null;
        $u = trim($url);

        if (preg_match('~^\/\/~', $u)) $u = 'https:'.$u;
        elseif (!preg_match('~^https?://~i', $u) && str_starts_with($u, 'drive.google.com')) $u = 'https://'.$u;

        if (preg_match('~drive\.google\.com\/file\/d\/([^\/]+)~i', $u, $m)) {
            return 'https://drive.google.com/uc?id='.$m[1];
        }
        if (preg_match('~drive\.google\.com\/open\?id=([^&]+)~i', $u, $m)) {
            return 'https://drive.google.com/uc?id='.$m[1];
        }
        if (preg_match('~google\.com\/uc\?id=([^&]+)~i', $u, $m)) {
            return 'https://drive.google.com/uc?id='.$m[1];
        }
        return $u;
    }

    public function rules(): array
    {
        return [
            '*.pieces_per_unit'   => ['nullable','integer','min:0'],
            '*.piezas_por_unidad' => ['nullable','integer','min:0'],
        ];
    }

    /* ===== Encabezados -> índices de columna ===== */
    private function buildHeaderIndex(Worksheet $ws): void
    {
        if ($this->headerBuilt) return;

        $hr = $this->headingRow();
        $lastColLetter = $ws->getHighestColumn();
        $range = "A{$hr}:{$lastColLetter}{$hr}";

        $row = $ws->rangeToArray($range, null, true, true, true)[$hr] ?? [];
        foreach ($row as $letter => $val) {
            $key = $this->norm(is_string($val) ? $val : (string)$val);
            if ($key !== '') {
                $this->headerIndex[$key] = Coordinate::columnIndexFromString($letter);
            }
        }

        // localizar columnas de imagen
        $aliases = $this->aliases();
        $findCol = function(array $names): ?int {
            foreach ($names as $n) {
                $k = $this->norm($n);
                if (isset($this->headerIndex[$k])) return $this->headerIndex[$k];
            }
            return null;
        };
        $this->imageUrlCol  = $findCol($aliases['image_url']);
        $this->imagePathCol = $findCol($aliases['image_path']);

        $this->headerBuilt = true;

        if (!$this->loggedHeaderOnce) {
            $this->loggedHeaderOnce = true;
            Log::debug('ProductsImport encabezados normalizados', [
                'keys' => array_keys($this->headerIndex),
                'imageUrlCol'  => $this->imageUrlCol,
                'imagePathCol' => $this->imagePathCol,
            ]);
        }
    }

    /* ==================== Import por fila (accede a hipervínculos) ==================== */
    public function onRow(Row $row): void
    {
        try {
            /** @var Worksheet $ws */
            $ws = $row->getDelegate()->getWorksheet();
            $this->buildHeaderIndex($ws);

            // Arr con claves normalizadas por encabezado
            $raw = [];
            foreach ($this->headerIndex as $key => $colIdx) {
                $letter = Coordinate::stringFromColumnIndex($colIdx);
                $val = $ws->getCellByColumnAndRow($colIdx, $row->getIndex())->getValue();
                $raw[$key] = is_string($val) ? trim($val) : $val;
            }

            // Re-alias a tus nombres destino
            $aliases = $this->aliases();
            $pick = function(string $target) use ($raw, $aliases) {
                foreach ($aliases[$target] as $alias) {
                    $k = $this->norm($alias);
                    if (array_key_exists($k, $raw) && $raw[$k] !== '' && $raw[$k] !== null) {
                        return is_string($raw[$k]) ? trim($raw[$k]) : $raw[$k];
                    }
                }
                return null;
            };

            // Datos base
            $name = $pick('name') ?: $pick('description');

            $price         = $this->num($pick('price'));
            $marketPrice   = $this->num($pick('market_price'));
            $bidPrice      = $this->num($pick('bid_price'));
            $cost          = $this->num($pick('cost'));
            $weight        = $this->num($pick('weight'));
            $piecesPerUnit = ($pick('pieces_per_unit') !== null && $pick('pieces_per_unit') !== '') ? (int)$pick('pieces_per_unit') : null;
            $active        = $this->boolish($pick('active')); if ($active === null) $active = true;

            // ==== Imagen: primero tomar HYPERLINK real, si existe ====
            $image_url  = null; $image_path = null;

            $readLink = function(?int $col) use ($ws, $row): ?string {
                if (!$col || $col < 1) return null;
                $cell = $ws->getCellByColumnAndRow($col, $row->getIndex());
                if (!$cell) return null;
                $link = $cell->getHyperlink();
                $url  = $link && $link->getUrl() ? (string)$link->getUrl() : null;
                if (!$url) {
                    $v = (string)$cell->getValue();
                    if ($v !== '' && preg_match('~^(https?:)?\/\/~i', $v)) $url = $v;
                }
                return $url ?: null;
            };

            $link = $readLink($this->imageUrlCol) ?: $readLink($this->imagePathCol);

            if ($link) {
                $image_url = $this->normalizeDrive($link);
            } else {
                // Si no hay hipervínculo, usar texto (URL o nombre de archivo)
                $rawImage = $pick('image_url') ?: $pick('image_path');
                if ($rawImage) {
                    $val = trim((string)$rawImage);
                    if ($this->isUrl($val)) {
                        $image_url = $this->normalizeDrive($val);
                    } else {
                        $image_path = ltrim($val, '/');
                    }
                }
            }

            $data = [
                'name'            => $name,
                'sku'             => $pick('sku'),
                'supplier_sku'    => $pick('supplier_sku'),
                'unit'            => $pick('unit'),
                'weight'          => $weight,
                'cost'            => $cost,
                'price'           => $price,
                'market_price'    => $marketPrice,
                'bid_price'       => $bidPrice,
                'dimensions'      => $pick('dimensions'),
                'color'           => $pick('color'),
                'pieces_per_unit' => $piecesPerUnit,
                'active'          => $active,
                'brand'           => $pick('brand'),
                'category'        => $pick('category'),
                'material'        => $pick('material'),
                'description'     => $pick('description'),
                'notes'           => $pick('notes'),
                'tags'            => $pick('tags'),
                'image_url'       => $image_url,
                'image_path'      => $image_path,
            ];

            // Si todos están vacíos, saltar
            if (!array_filter($data, fn($v) => !is_null($v) && $v !== '')) { $this->skipped++; return; }

            // UPSERT por SKU
            if (!empty($data['sku'])) {
                $existing = Product::where('sku', $data['sku'])->first();
                if ($existing) {
                    $clean = array_filter($data, fn($v) => !is_null($v) && $v !== '');
                    if (!empty($clean)) { $existing->fill($clean)->save(); $this->updated++; }
                    else { $this->skipped++; }
                } else {
                    Product::create($data);
                    $this->created++;
                }
            } else {
                Product::create($data);
                $this->created++;
            }
        } catch (\Throwable $e) {
            $this->skipped++;
            Log::error('ProductsImport onRow error', [
                'row' => $row->getIndex(),
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function onError(Throwable $e)  { Log::error('ProductsImport onError',   ['message' => $e->getMessage()]); }
    public function onFailure(Failure ...$failures) { Log::warning('ProductsImport onFailure', ['count' => count($failures)]); }
}
