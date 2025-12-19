<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    use HasFactory;
    protected $table = 'estados';
    //protected
    protected $fillable = [
        'nombre',
        'activo',
        'fecha_baja',
        'motivo_baja',
    ];
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
    public function proveedores()
    {
        return $this->hasMany(Proveedor::class);
    }
}
