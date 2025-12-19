<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recomendacion extends Model
{
    use HasFactory;
    protected $table = 'recomendacion';

    protected $fillable = [
        'nombre_recomendacion',
        'detalles_recomendacion',
        'activo',
        'fecha_baja',
        'motivo_baja',
    ];
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
}
