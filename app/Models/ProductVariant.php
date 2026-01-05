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
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'attribute_combinations' => 'array',
        'stock_alert' => 'integer',
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

    public function attributes()
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

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sku_variant', 'asc');
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
        foreach ($this->attributes as $attrValue) {
            $attrs[] = $attrValue->value;
        }
        return implode(' / ', $attrs);
    }

    public function getAttributesDetailedAttribute(): array
    {
        $result = [];
        foreach ($this->attributes as $attrValue) {
            $result[] = [
                'attribute' => $attrValue->attribute->name ?? 'N/A',
                'value' => $attrValue->value,
            ];
        }
        return $result;
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
}
