<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductMaterial extends Pivot
{
    protected $table = 'product_materials';

    public $incrementing = true;

    protected $fillable = [
        'product_id',
        'material_variant_id',
        'quantity',
        'unit_cost',
        'is_primary',
        'notes',
        'active_for_variants',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'total_cost' => 'decimal:6',
        'is_primary' => 'boolean',
        'active_for_variants' => 'array',
    ];

    /**
     * Relación con el material (Variante)
     */
    public function materialVariant()
    {
        return $this->belongsTo(MaterialVariant::class, 'material_variant_id');
    }

    /**
     * Relación con el producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
