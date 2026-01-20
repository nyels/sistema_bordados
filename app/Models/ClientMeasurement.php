<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ClientMeasurement extends Model
{
    protected $table = 'client_measurements';

    protected $fillable = [
        'uuid',
        'cliente_id',
        'busto',
        'cintura',
        'cadera',
        'alto_cintura',
        'largo',
        'largo_vestido',
        'hombro',
        'espalda',
        'largo_manga',
        'label',
        'is_primary',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'busto' => 'decimal:2',
        'cintura' => 'decimal:2',
        'cadera' => 'decimal:2',
        'alto_cintura' => 'decimal:2',
        'largo' => 'decimal:2',
        'largo_vestido' => 'decimal:2',
        'hombro' => 'decimal:2',
        'espalda' => 'decimal:2',
        'largo_manga' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    // === BOOT ===
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // === RELACIONES ===

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // === SCOPES ===

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    // === ACCESSORS ===

    public function getSummaryAttribute(): string
    {
        $parts = [];
        if ($this->busto) $parts[] = "B:{$this->busto}";
        if ($this->cintura) $parts[] = "C:{$this->cintura}";
        if ($this->cadera) $parts[] = "Ca:{$this->cadera}";
        return implode(' | ', $parts) ?: 'Sin medidas';
    }
}
