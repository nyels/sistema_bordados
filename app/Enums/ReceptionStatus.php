<?php

namespace App\Enums;

enum ReceptionStatus: string
{
    case COMPLETED = 'completed';
    case VOIDED = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Completada',
            self::VOIDED => 'Anulada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::COMPLETED => 'success',
            self::VOIDED => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::COMPLETED => 'fas fa-check-circle',
            self::VOIDED => 'fas fa-ban',
        };
    }
}
