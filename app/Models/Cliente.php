<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    protected $table = 'clientes';
    protected $fillable = [
        'nombre',
        'apellidos',
        'telefono',
        'email',
        'rfc',
        'razon_social',
        'direccion',
        'ciudad',
        'codigo_postal',
        'activo',
        'fecha_baja',
        'motivo_baja',
        'observaciones',
        'estado_id',
        'recomendacion_id',
        'busto',
        'alto_cintura',
        'cintura',
        'cadera',
        'largo',
        'largo_vestido',
    ];
    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }
    public function recomendacion()
    {
        return $this->belongsTo(Recomendacion::class);
    }
}
