<?php

namespace App\Enums;

/**
 * =============================================================================
 * ENUM: TIPOS DE UNIDAD DE MEDIDA
 * =============================================================================
 *
 * Clasificación semántica explícita para el modelo de unidades del ERP.
 *
 * TIPOS:
 * - CANONICAL   : Unidad canónica de consumo (metro, litro, pieza)
 * - METRIC_PACK : Presentación métrica derivada (rollo 25m, caja 100pz)
 * - LOGISTIC    : Unidad logística pura de compra (cono, saco, paquete)
 *
 * USO EN VALIDACIONES:
 * - Categoría ↔ Unidad : SOLO logistic
 * - Material base_unit : SOLO logistic
 * - Conversiones from  : logistic
 * - Conversiones to    : canonical
 *
 * @see \App\Models\Unit
 */
enum UnitType: string
{
    /**
     * Unidad canónica de consumo.
     * Ejemplos: METRO, LITRO, PIEZA, GRAMO
     *
     * Características:
     * - NO tiene compatible_base_unit_id
     * - Se usa SOLO para consumo/producción
     * - NUNCA aparece como unidad de compra
     * - Es el destino de las conversiones
     */
    case CANONICAL = 'canonical';

    /**
     * Presentación métrica derivada.
     * Ejemplos: ROLLO 25M, CAJA 100PZ, GALÓN 10L
     *
     * Características:
     * - SIEMPRE tiene compatible_base_unit_id
     * - Deriva métricamente de una canonical
     * - NUNCA se asigna a categorías
     * - Solo se usa en conversiones de materiales específicos
     */
    case METRIC_PACK = 'metric_pack';

    /**
     * Unidad logística pura de compra.
     * Ejemplos: CONO, SACO, PAQUETE, BOLSA, ROLLO (genérico)
     *
     * Características:
     * - NO tiene compatible_base_unit_id
     * - Representa empaques físicos de compra
     * - Es la ÚNICA permitida en Categoría ↔ Unidad
     * - Es la ÚNICA permitida como base_unit de Material
     * - Es el origen de las conversiones
     */
    case LOGISTIC = 'logistic';

    /**
     * Obtener etiqueta legible para UI.
     * Textos simplificados para usuarios del sector textil.
     */
    public function label(): string
    {
        return match ($this) {
            self::CANONICAL => 'Consumo',
            self::METRIC_PACK => 'Presentación',
            self::LOGISTIC => 'Compra',
        };
    }

    /**
     * Obtener descripción detallada para tooltips y ayuda contextual.
     */
    public function description(): string
    {
        return match ($this) {
            self::CANONICAL => 'Unidad en la que el material se gasta durante producción (metro, litro, pieza, minuto)',
            self::METRIC_PACK => 'Empaque con cantidad fija (Rollo 25m, Caja 100pz)',
            self::LOGISTIC => 'Unidad en la que se compra el material (cono, saco, paquete)',
        };
    }

    /**
     * Obtener texto de ayuda contextual para formularios.
     */
    public function helpText(): string
    {
        return match ($this) {
            self::CANONICAL => 'Unidad física real que se consume al producir una pieza',
            self::METRIC_PACK => 'Presentación comercial con cantidad predefinida',
            self::LOGISTIC => 'Empaque físico en el que llega el material del proveedor',
        };
    }

    /**
     * Verificar si puede asignarse a categorías.
     */
    public function canAssignToCategory(): bool
    {
        return $this === self::LOGISTIC;
    }

    /**
     * Verificar si puede ser unidad base de material.
     */
    public function canBeBaseMaterialUnit(): bool
    {
        return $this === self::LOGISTIC;
    }

    /**
     * Verificar si puede ser origen de conversión.
     */
    public function canBeConversionSource(): bool
    {
        return $this === self::LOGISTIC;
    }

    /**
     * Verificar si puede ser destino de conversión.
     */
    public function canBeConversionTarget(): bool
    {
        return $this === self::CANONICAL;
    }

    /**
     * Obtener color para badges en UI.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::CANONICAL => 'success',
            self::METRIC_PACK => 'warning',
            self::LOGISTIC => 'primary',
        };
    }

    /**
     * Obtener icono FontAwesome.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CANONICAL => 'fa-ruler',
            self::METRIC_PACK => 'fa-box',
            self::LOGISTIC => 'fa-dolly',
        };
    }
}
