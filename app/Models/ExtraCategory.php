<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraCategory extends Model
{
    use HasFactory;

    protected $table = 'extra_categories';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Extras que pertenecen a esta categoría.
     */
    public function extras(): HasMany
    {
        return $this->hasMany(ProductExtra::class, 'extra_category_id');
    }

    /**
     * Scope: Solo activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Accessor: Nombre en mayúsculas.
     */
    public function getNombreAttribute($value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }
}
