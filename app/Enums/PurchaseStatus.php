<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case DRAFT = 'borrador';
    case PENDING = 'pendiente';
    case PARTIAL = 'parcial';
    case RECEIVED = 'recibido';
    case CANCELLED = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::PENDING => 'Pendiente',
            self::PARTIAL => 'Parcialmente Recibido',
            self::RECEIVED => 'Recibido',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::PENDING => 'warning',
            self::PARTIAL => 'info',
            self::RECEIVED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'fas fa-file',
            self::PENDING => 'fas fa-clock',
            self::PARTIAL => 'fas fa-truck-loading',
            self::RECEIVED => 'fas fa-check-circle',
            self::CANCELLED => 'fas fa-times-circle',
        };
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING]);
    }

    public function canReceive(): bool
    {
        return in_array($this, [self::PENDING, self::PARTIAL]);
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
