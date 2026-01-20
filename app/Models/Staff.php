<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'name',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relacion con Usuario (opcional)
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'staff_id');
    }

    /**
     * Scope: Solo activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor: Tiene usuario vinculado
     */
    public function getHasUserAttribute(): bool
    {
        return $this->user !== null;
    }
}
