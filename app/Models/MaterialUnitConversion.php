<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialUnitConversion extends Model
{
    protected $table = 'material_unit_conversions';

    protected $fillable = [
        'material_id',
        'from_unit_id',
        'to_unit_id',
        'conversion_factor',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function fromUnit()
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    public function toUnit()
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getDisplayConversionAttribute(): string
    {
        $from = $this->fromUnit?->name ?? '';
        $to = $this->toUnit?->symbol ?? '';
        return "1 {$from} = {$this->conversion_factor} {$to}";
    }
}
