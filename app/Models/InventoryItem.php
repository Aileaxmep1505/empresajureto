<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
protected $fillable = [
    'inventory_category_id',
    'name',
    'type',
    'asset_status',
    'condition',
    'brand',
    'model',
    'serial_number',
    'internal_code',
    'stock',
    'stock_min',
    'stock_max',
    'unit',
    'location',
    'department',
    'supplier',
    'purchase_date',
    'purchase_cost',
    'warranty_until',
    'processor',
    'ram',
    'storage',
    'operating_system',
    'mac_address',
    'notes',
    'photo',
];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function assignments()
    {
        return $this->hasMany(InventoryAssignment::class);
    }

    public function getStockStatusAttribute()
    {
        if ($this->type !== 'consumible') {
            return 'normal';
        }

        if ($this->stock <= $this->stock_min) {
            return 'bajo';
        }

        if ($this->stock >= $this->stock_max) {
            return 'alto';
        }

        return 'normal';
    }

    public function getTypeLabelAttribute()
    {
        return $this->type === 'consumible' ? 'Consumible' : 'Activo Fijo';
    }

    public function getAssetStatusLabelAttribute()
    {
        return match ($this->asset_status) {
            'disponible' => 'Disponible',
            'asignado' => 'Asignado',
            'en_reparacion' => 'En reparación',
            'dado_de_baja' => 'Dado de baja',
            default => null,
        };
    }

    public function getConditionLabelAttribute()
    {
        return match ($this->condition) {
            'nuevo' => 'Nuevo',
            'bueno' => 'Bueno',
            'regular' => 'Regular',
            'malo' => 'Malo',
            default => null,
        };
    }
    public function maintenances()
{
    return $this->hasMany(\App\Models\Maintenance::class);
}

public function stockMovements()
{
    return $this->hasMany(\App\Models\StockMovement::class);
}
}
