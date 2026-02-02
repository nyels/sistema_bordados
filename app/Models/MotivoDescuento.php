<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoDescuento extends Model
{
    use HasFactory;

    protected $table = 'motivos_descuento';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'fecha_baja',
        'motivo_baja',
    ];

    /**
     * Scope para obtener solo los activos ordenados alfabéticamente
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('nombre', 'asc');
    }
}
