<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignExportStatusHistory extends Model
{
    protected $table = 'design_export_status_history';

    protected $fillable = [
        'design_export_id',
        'previous_status',
        'new_status',
        'changed_by',
        'notes',
    ];

    /**
     * Relación con el export.
     */
    public function export()
    {
        return $this->belongsTo(DesignExport::class, 'design_export_id');
    }

    /**
     * Relación con el usuario que hizo el cambio.
     */
    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Accesor para el estado traducido (previous).
     */
    public function getPreviousStatusLabelAttribute()
    {
        return $this->getStatusLabel($this->previous_status);
    }

    /**
     * Accesor para el estado traducido (new).
     */
    public function getNewStatusLabelAttribute()
    {
        return $this->getStatusLabel($this->new_status);
    }

    /**
     * Helper para traducir estados.
     */
    private function getStatusLabel(?string $status): string
    {
        $labels = [
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'archivado' => 'Archivado',
        ];

        return $labels[$status] ?? $status ?? 'Creación';
    }

    /**
     * Accesor para el color del estado.
     */
    public function getStatusColorAttribute(): string
    {
        $colors = [
            'borrador' => '#6b7280',
            'pendiente' => '#d97706',
            'aprobado' => '#059669',
            'archivado' => '#374151',
        ];

        return $colors[$this->new_status] ?? '#3b82f6';
    }

    /**
     * Accesor para el icono del estado.
     */
    public function getStatusIconAttribute(): string
    {
        $icons = [
            'borrador' => 'fa-pencil-alt',
            'pendiente' => 'fa-clock',
            'aprobado' => 'fa-check-circle',
            'archivado' => 'fa-archive',
        ];

        return $icons[$this->new_status] ?? 'fa-exchange-alt';
    }
}
