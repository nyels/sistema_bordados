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
        'label',
        'intermediate_unit_id',
        'intermediate_qty',
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

    public function intermediateUnit()
    {
        return $this->belongsTo(Unit::class, 'intermediate_unit_id');
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

    /**
     * Obtiene el desglose detallado (Auto-deducción si es necesario)
     */
    public function getBreakdownData(): array
    {
        $qty = $this->intermediate_qty;
        $interUnit = $this->intermediateUnit;

        // Si no hay cantidad pero hay label con número (ej: caja24)
        if (!$qty && $this->label) {
            if (preg_match('/(\d+)/', $this->label, $matches)) {
                $qty = (float) $matches[1];
            }
        }

        if ($qty > 0) {
            $eachValue = (float) $this->conversion_factor / $qty;

            // Si no hay unidad intermedia, buscar una que coincida con el factor
            if (!$interUnit && $this->material) {
                $match = $this->material->unitConversions
                    ->where('id', '!=', $this->id)
                    ->filter(function ($c) use ($eachValue) {
                        return abs((float)$c->conversion_factor - $eachValue) < 0.001;
                    })->first();

                if ($match) {
                    $interUnit = $match->fromUnit;
                }
            }

            return [
                'has_breakdown' => true,
                'qty' => $qty,
                'each_value' => $eachValue,
                'unit_symbol' => 'pz', // Forzamos 'pz' por solicitud del usuario para el desglose
                'total' => (float)$this->conversion_factor
            ];
        }

        return ['has_breakdown' => false];
    }
}
