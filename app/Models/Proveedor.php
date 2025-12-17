<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    //protected
    protected $fillable = [
        'nombre',
        'tipo_proveedor',
        'direccion',
        'codigo_postal',
        'telefono',
        'email',
        'ciudad',
        'persona_contacto',
        'telefono_contacto',
        'correo_contacto',
        'activo',
        'fecha_baja',
        'motivo_baja',
        'estado_id',
    ];

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }
}
