<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Infraestructura mínima de stock v2.
 * Representa una orden de producción para stock (sin cliente).
 *
 * SOLO ESTRUCTURA - Sin lógica de negocio.
 */
class StockProduction extends Model
{
    protected $table = 'stock_productions';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PRODUCTION = 'in_production';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uuid',
        'production_number',
        'product_variant_id',
        'quantity',
        'quantity_completed',
        'status',
        'notes',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'quantity_completed' => 'decimal:4',
        'completed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // GATE v2.1: Detectar transición a completed
        static::updating(function (self $model): void {
            $model->handleCompletionGate();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | GATE DE FINALIZACIÓN v2.1
    |--------------------------------------------------------------------------
    */

    /**
     * Gate de finalización: cuando status transiciona a completed,
     * genera entrada de inventario para el ProductVariant.
     *
     * IDEMPOTENTE: No ejecuta si ya existe movimiento para esta producción.
     */
    protected function handleCompletionGate(): void
    {
        // Solo procesar si hay cambio de status hacia completed
        if (!$this->isDirty('status')) {
            return;
        }

        $oldStatus = $this->getOriginal('status');
        $newStatus = $this->status;

        // Gate: solo activar en transición → completed
        if ($newStatus !== self::STATUS_COMPLETED) {
            return;
        }

        // Protección: no reprocesar si ya estaba completed
        if ($oldStatus === self::STATUS_COMPLETED) {
            return;
        }

        // Ejecutar entrada de stock
        $this->executeStockEntry();
    }

    /**
     * Ejecuta la entrada de stock al completar producción.
     * Operación atómica con idempotencia garantizada.
     */
    protected function executeStockEntry(): void
    {
        DB::transaction(function () {
            // IDEMPOTENCIA: Verificar que no exista movimiento previo
            $existingMovement = FinishedGoodsMovement::where('reference_type', self::class)
                ->where('reference_id', $this->id)
                ->where('type', FinishedGoodsMovement::TYPE_PRODUCTION_ENTRY)
                ->lockForUpdate()
                ->exists();

            if ($existingMovement) {
                Log::warning("StockProduction #{$this->id}: Movimiento ya existente, gate ignorado.");
                return;
            }

            // Obtener variante con lock para evitar race conditions
            $variant = ProductVariant::lockForUpdate()->find($this->product_variant_id);

            if (!$variant) {
                Log::error("StockProduction #{$this->id}: ProductVariant #{$this->product_variant_id} no encontrado.");
                return;
            }

            $quantityToAdd = (float) $this->quantity_completed;
            $stockBefore = (float) $variant->current_stock;
            $stockAfter = $stockBefore + $quantityToAdd;

            // Crear movimiento de entrada
            FinishedGoodsMovement::create([
                'product_variant_id' => $this->product_variant_id,
                'type' => FinishedGoodsMovement::TYPE_PRODUCTION_ENTRY,
                'reference_type' => self::class,
                'reference_id' => $this->id,
                'quantity' => $quantityToAdd,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => "Producción {$this->production_number} completada",
            ]);

            // Incrementar stock en variante
            $variant->current_stock = $stockAfter;
            $variant->save();

            // Setear completed_at si no está definido
            if (empty($this->completed_at)) {
                $this->completed_at = now();
            }

            Log::info("StockProduction #{$this->id}: Entrada de {$quantityToAdd} unidades a ProductVariant #{$this->product_variant_id}. Stock: {$stockBefore} → {$stockAfter}");
        });
    }

    /**
     * Método público para completar la producción.
     * Setea quantity_completed = quantity si no está definido.
     *
     * @return bool
     */
    public function completeProduction(?float $quantityCompleted = null): bool
    {
        // No permitir completar si ya está completed o cancelled
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return false;
        }

        // Setear cantidad completada
        if ($quantityCompleted !== null) {
            $this->quantity_completed = $quantityCompleted;
        } elseif ($this->quantity_completed <= 0) {
            $this->quantity_completed = $this->quantity;
        }

        $this->status = self::STATUS_COMPLETED;

        return $this->save();
    }

    /**
     * Verifica si esta producción ya generó entrada de inventario.
     */
    public function hasStockEntry(): bool
    {
        return $this->finishedGoodsMovement()->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Movimiento de inventario generado al completar esta producción.
     */
    public function finishedGoodsMovement(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FinishedGoodsMovement::class, 'reference_id')
            ->where('reference_type', self::class)
            ->where('type', FinishedGoodsMovement::TYPE_PRODUCTION_ENTRY);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeInProduction(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_PRODUCTION);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_IN_PRODUCTION]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_IN_PRODUCTION => 'En Producción',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_IN_PRODUCTION => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    public function getQuantityPendingAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->quantity_completed);
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->quantity <= 0) {
            return 0;
        }
        return min(100, ($this->quantity_completed / $this->quantity) * 100);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS GENERADOR
    |--------------------------------------------------------------------------
    */

    public static function generateProductionNumber(): string
    {
        $year = date('Y');
        $prefix = "PROD-{$year}-";

        $last = self::where('production_number', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(production_number, -4) AS UNSIGNED) DESC')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->production_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
