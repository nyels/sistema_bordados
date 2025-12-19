<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'width',
        'height',
        'alt_text',
        'order',
        'is_primary'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'order' => 'integer',
        'is_primary' => 'boolean'
    ];

    // Relación polimórfica inversa
    public function imageable()
    {
        return $this->morphTo();
    }
}
