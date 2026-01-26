<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'uuid',
        'product_id',
        'sku_variant',
        'price',
        'attribute_combinations',
        'stock_alert',
        'current_stock',
        'reserved_stock',
        'activo',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'attribute_combinations' => 'array',
        'stock_alert' => 'integer',
        'current_stock' => 'decimal:4',
        'reserved_stock' => 'decimal:4',
        'activo' => 'boolean',
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
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_attribute',
            'product_variant_id',
            'attribute_value_id'
        )->withPivot('attribute_id');
    }

    public function designExports()
    {
        return $this->belongsToMany(
            DesignExport::class,
            'product_variant_design',
            'product_variant_id',
            'design_export_id'
        )->withPivot('application_type_id', 'notes')
            ->withTimestamps();
    }

    /**
     * Infraestructura mínima de stock v2.
     * Producciones para stock asociadas a esta variante.
     */
    public function stockProductions()
    {
        return $this->hasMany(StockProduction::class, 'product_variant_id');
    }

    /**
     * Infraestructura mínima de stock v2.
     * Movimientos de productos terminados de esta variante.
     */
    public function finishedGoodsMovements()
    {
        return $this->hasMany(FinishedGoodsMovement::class, 'product_variant_id');
    }

    /**
     * RESERVAS DE STOCK v2.2
     * Reservas de productos terminados para Orders.
     */
    public function stockReservations()
    {
        return $this->hasMany(ProductVariantReservation::class, 'product_variant_id');
    }

    /**
     * Reservas activas (status = 'reserved').
     */
    public function activeReservations()
    {
        return $this->stockReservations()->where('status', ProductVariantReservation::STATUS_RESERVED);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sku_variant', 'asc');
    }

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Variantes con stock bajo el nivel de alerta.
     * REGLA: current_stock <= stock_alert
     * USO: Solo para alertas operativas, NO bloquea ventas ni producción.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'stock_alert');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    public function getTotalWithExtrasAttribute(): float
    {
        $extrasTotal = $this->product->extras->sum('price_addition');
        return (float) $this->price + $extrasTotal;
    }

    public function getFormattedTotalWithExtrasAttribute(): string
    {
        return '$' . number_format($this->total_with_extras, 2);
    }

    public function getAttributesDisplayAttribute(): string
    {
        $attrs = [];
        foreach ($this->attributeValues as $attrValue) {
            $attrs[] = $attrValue->value;
        }
        return implode(' / ', $attrs);
    }

    public function getAttributesDetailedAttribute(): array
    {
        $result = [];
        foreach ($this->attributeValues as $attrValue) {
            $result[] = [
                'attribute' => $attrValue->attribute->name ?? 'N/A',
                'value' => $attrValue->value,
            ];
        }
        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS - STOCK v2
    |--------------------------------------------------------------------------
    */

    /**
     * Infraestructura mínima de stock v2.
     * Stock disponible = stock físico - stock reservado.
     */
    public function getAvailableStockAttribute(): float
    {
        return max(0, (float) $this->current_stock - (float) $this->reserved_stock);
    }

    /**
     * Infraestructura mínima de stock v2.
     * Indica si hay stock disponible.
     */
    public function getHasAvailableStockAttribute(): bool
    {
        return $this->available_stock > 0;
    }

    /**
     * Infraestructura mínima de stock v2.
     * Stock formateado para display.
     */
    public function getFormattedCurrentStockAttribute(): string
    {
        return number_format($this->current_stock, 2);
    }

    /**
     * Infraestructura mínima de stock v2.
     * Indica si el stock está bajo el nivel de alerta.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->stock_alert;
    }

    /**
     * Verifica si el stock está bajo el mínimo de alerta.
     *
     * DEFINICIÓN CANÓNICA:
     * - Es SOLO una alerta operativa
     * - NO bloquea ventas
     * - NO bloquea producción
     * - NO genera movimientos
     *
     * @return bool TRUE si current_stock <= stock_alert
     */
    public function isBelowMinStock(): bool
    {
        return $this->current_stock <= ($this->stock_alert ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS
    |--------------------------------------------------------------------------
    */

    public static function generateSkuVariant(Product $product, array $attributeValues): string
    {
        $productSku = $product->sku;
        $parts = [$productSku];

        foreach ($attributeValues as $value) {
            $parts[] = Str::upper(Str::substr(Str::slug($value, ''), 0, 3));
        }

        return implode('-', $parts);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE RESERVA DE STOCK v2.2
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si hay stock disponible suficiente para reservar.
     *
     * REGLA: available_stock = current_stock - reserved_stock
     *
     * @param float $quantity Cantidad a reservar
     * @return bool TRUE si hay stock disponible suficiente
     */
    public function canReserve(float $quantity): bool
    {
        return $this->available_stock >= $quantity;
    }

    /**
     * Reserva stock para un pedido.
     * REGLA: current_stock NO cambia, reserved_stock SÍ cambia.
     *
     * @param float $quantity Cantidad a reservar
     * @return void
     * @throws \Exception Si no hay stock disponible suficiente
     */
    public function reserveStock(float $quantity): void
    {
        if (!$this->canReserve($quantity)) {
            throw new \Exception(
                "Stock insuficiente para reservar. " .
                "Disponible: {$this->available_stock}, Requerido: {$quantity}"
            );
        }

        $this->reserved_stock = (float) $this->reserved_stock + $quantity;
        $this->save();
    }

    /**
     * Libera stock reservado (al cancelar un pedido).
     * REGLA: Devuelve la cantidad a available_stock reduciendo reserved_stock.
     *
     * @param float $quantity Cantidad a liberar
     * @return void
     */
    public function releaseReservedStock(float $quantity): void
    {
        $this->reserved_stock = max(0, (float) $this->reserved_stock - $quantity);
        $this->save();
    }

    /**
     * Consume stock reservado (al entregar un pedido).
     * REGLA: Reduce AMBOS current_stock Y reserved_stock.
     *
     * @param float $quantity Cantidad a consumir
     * @return void
     */
    public function consumeReservedStock(float $quantity): void
    {
        $this->current_stock = max(0, (float) $this->current_stock - $quantity);
        $this->reserved_stock = max(0, (float) $this->reserved_stock - $quantity);
        $this->save();
    }

    /**
     * Obtiene el total de stock reservado recalculado desde la tabla de reservas.
     * Útil para auditoría y reconciliación.
     *
     * @return float
     */
    public function getCalculatedReservedStock(): float
    {
        return (float) $this->activeReservations()->sum('quantity');
    }

    /**
     * Sincroniza reserved_stock con las reservas activas reales.
     * Útil para corregir inconsistencias.
     *
     * @return void
     */
    public function syncReservedStock(): void
    {
        $this->reserved_stock = $this->getCalculatedReservedStock();
        $this->save();
    }
}
