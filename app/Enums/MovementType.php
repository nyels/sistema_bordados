<?php

namespace App\Enums;

enum MovementType: string
{
    case ENTRY = 'entrada';
    case EXIT = 'salida';
    case ADJUSTMENT_POSITIVE = 'ajuste_positivo';
    case ADJUSTMENT_NEGATIVE = 'ajuste_negativo';
    case RETURN_SUPPLIER = 'devolucion_proveedor';
    case RETURN_PRODUCTION = 'devolucion_produccion';

    public function label(): string
    {
        return match ($this) {
            self::ENTRY => 'Entrada',
            self::EXIT => 'Salida',
            self::ADJUSTMENT_POSITIVE => 'Ajuste Positivo',
            self::ADJUSTMENT_NEGATIVE => 'Ajuste Negativo',
            self::RETURN_SUPPLIER => 'Devolución a Proveedor',
            self::RETURN_PRODUCTION => 'Devolución de Producción',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ENTRY, self::ADJUSTMENT_POSITIVE, self::RETURN_PRODUCTION => 'success',
            self::EXIT, self::ADJUSTMENT_NEGATIVE, self::RETURN_SUPPLIER => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ENTRY => 'fas fa-arrow-down',
            self::EXIT => 'fas fa-arrow-up',
            self::ADJUSTMENT_POSITIVE => 'fas fa-plus-circle',
            self::ADJUSTMENT_NEGATIVE => 'fas fa-minus-circle',
            self::RETURN_SUPPLIER => 'fas fa-undo',
            self::RETURN_PRODUCTION => 'fas fa-redo',
        };
    }

    public function affectsStock(): int
    {
        return match ($this) {
            self::ENTRY, self::ADJUSTMENT_POSITIVE, self::RETURN_PRODUCTION => 1,
            self::EXIT, self::ADJUSTMENT_NEGATIVE, self::RETURN_SUPPLIER => -1,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
