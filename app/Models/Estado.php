<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    //protected
    protected $fillable = [
        'nombre',
        'activo',
        'fecha_baja',
        'motivo_baja',
    ];
    public function proveedores()
    {
        return $this->hasMany(Proveedor::class);
    }
}
