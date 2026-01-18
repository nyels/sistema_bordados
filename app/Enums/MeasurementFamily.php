<?php

namespace App\Enums;

/**
 * ============================================================================
 * ENUM: Familia de Medición
 * ============================================================================
 *
 * Define las familias de medición para filtrado semántico de unidades.
 *
 * REGLAS:
 * - Empaques "linear" son compatibles con inventarios en METRO
 * - Empaques "discrete" son compatibles con inventarios en PIEZA
 * - Empaques "universal" son compatibles con cualquier inventario
 * - Unidades "time" no deberían usarse para materiales físicos
 */
enum MeasurementFamily: string
{
    case LINEAR = 'linear';       // Longitud: metro, cono, rollo, carrete
    case DISCRETE = 'discrete';   // Conteo: pieza, paquete, bolsa
    case TIME = 'time';           // Tiempo: minuto, hora (no para materiales)
    case UNIVERSAL = 'universal'; // Universal: caja (compatible con todo)

    /**
     * Etiqueta para mostrar en UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::LINEAR => 'Lineal (metros)',
            self::DISCRETE => 'Discreto (piezas)',
            self::TIME => 'Tiempo',
            self::UNIVERSAL => 'Universal',
        };
    }

    /**
     * Descripción para tooltips.
     */
    public function description(): string
    {
        return match ($this) {
            self::LINEAR => 'Para materiales medidos en longitud (hilos, cintas, telas)',
            self::DISCRETE => 'Para materiales contados por unidad (botones, bases)',
            self::TIME => 'Para servicios medidos en tiempo (no aplicable a materiales)',
            self::UNIVERSAL => 'Contenedores genéricos compatibles con cualquier tipo',
        };
    }

    /**
     * Obtener familias compatibles para una familia de inventario dada.
     *
     * @return array<MeasurementFamily>
     */
    public static function getCompatibleFamilies(self $inventoryFamily): array
    {
        return match ($inventoryFamily) {
            self::LINEAR => [self::LINEAR, self::UNIVERSAL],
            self::DISCRETE => [self::DISCRETE, self::UNIVERSAL],
            self::TIME => [self::UNIVERSAL], // Solo universal para tiempo
            self::UNIVERSAL => [self::LINEAR, self::DISCRETE, self::UNIVERSAL],
        };
    }

    /**
     * Verificar si esta familia es compatible con otra.
     */
    public function isCompatibleWith(self $other): bool
    {
        // Universal es compatible con todo
        if ($this === self::UNIVERSAL || $other === self::UNIVERSAL) {
            return true;
        }

        return $this === $other;
    }
}
