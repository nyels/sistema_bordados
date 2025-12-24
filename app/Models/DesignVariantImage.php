<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignVariantImage extends Model
{
    protected $fillable = [
        'design_variant_id',
        'path',
        'original_name',
        'mime_type',
        'size',
        'is_primary',
        'order'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'order' => 'integer',
        'size' => 'integer',
    ];

    public function variant()
    {
        return $this->belongsTo(DesignVariant::class, 'design_variant_id');
    }
}
