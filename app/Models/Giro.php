<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Giro extends Model
{
    use HasFactory;
    protected $table = 'giros';
    protected $fillable = [
        'nombre_giro',
        'descripcion',
        'activo',
        'fecha_baja',
    ];

    public function proveedores()
    {
        return $this->hasMany(Proveedor::class);
    }
}
