<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesignExport extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'design_id',
        'design_variant_id',
        'image_id',
        'application_type',
        'application_label',
        'placement_description',
        'file_name',
        'file_path',
        'file_format',
        'file_size',
        'mime_type',
        'stitches_count',
        'width_mm',
        'height_mm',
        'colors_count',
        'colors_detected',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'auto_read_success',
        'svg_content',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approved_at' => 'datetime',
        'stitches_count' => 'integer',
        'width_mm' => 'integer',
        'height_mm' => 'integer',
        'colors_count' => 'integer',
        'file_size' => 'integer',
        'auto_read_success' => 'boolean',
        'colors_detected' => 'array',
    ];

    /**
     * Los atributos que deben ser transformados a fechas.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'approved_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Relación con el diseño.
     */
    public function design()
    {
        return $this->belongsTo(Design::class);
    }

    /**
     * Relación con la variante (opcional).
     */
    public function variant()
    {
        return $this->belongsTo(DesignVariant::class, 'design_variant_id');
    }

    /**
     * Relación con la imagen específica (opcional).
     * Permite vincular una producción a una imagen específica de la galería.
     */
    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * Relación con el creador.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el aprobador (opcional).
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relación con el historial de cambios de estado.
     */
    public function statusHistory()
    {
        return $this->hasMany(DesignExportStatusHistory::class, 'design_export_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Accesor para las dimensiones formateadas.
     * IMPORTANTE: Este accesor ya existe, pero se verifica que funcione correctamente.
     * Formato esperado por el frontend: "100x150 mm"
     */
    public function getFormattedDimensionsAttribute()
    {
        if ($this->width_mm && $this->height_mm) {
            return "{$this->width_mm}×{$this->height_mm} mm";
        }
        return 'N/A';
    }

    /**
     * Accesor para el tamaño del archivo formateado.
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) return 'N/A';

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Accesor para el estado traducido.
     */
    public function getTranslatedStatusAttribute()
    {
        $statuses = [
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'archivado' => 'Archivado',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Accesor para la clase CSS del estado.
     */
    public function getStatusClassAttribute()
    {
        $classes = [
            'borrador' => 'secondary',
            'pendiente' => 'warning',
            'aprobado' => 'success',
            'archivado' => 'dark',
        ];

        return $classes[$this->status] ?? 'secondary';
    }

    /**
     * NUEVO ACCESOR: Obtiene las dimensiones en formato compatible con el frontend.
     * Este accesor asegura que el formato sea exactamente el que espera el JavaScript.
     * Formato: "100x150 mm" (sin el símbolo × especial, usando 'x' normal)
     */
    public function getDimensionsForFrontendAttribute()
    {
        if ($this->width_mm && $this->height_mm) {
            return $this->width_mm . 'x' . $this->height_mm . ' mm';
        }
        return 'N/A';
    }
    public function productVariants()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_design');
    }
}
