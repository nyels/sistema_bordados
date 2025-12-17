<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proveedor extends Model
{
    use HasFactory;
    protected $table = 'proveedors';
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

    public function giro()
    {
        return $this->belongsTo(Giro::class);
    }
}
