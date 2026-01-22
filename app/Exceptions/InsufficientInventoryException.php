<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando el inventario es insuficiente para producción.
 * Contiene los detalles de materiales faltantes para trazabilidad.
 */
class InsufficientInventoryException extends Exception
{
    /**
     * Lista de materiales faltantes con detalles.
     *
     * @var array
     */
    protected array $missingMaterials;

    /**
     * @param array $missingMaterials Array de strings con descripción de materiales faltantes
     * @param string $message Mensaje de la excepción
     */
    public function __construct(array $missingMaterials, string $message = '')
    {
        $this->missingMaterials = $missingMaterials;

        if (empty($message)) {
            $message = 'Inventario insuficiente: ' . implode(', ', $missingMaterials);
        }

        parent::__construct($message);
    }

    /**
     * Obtiene la lista de materiales faltantes.
     *
     * @return array
     */
    public function getMissingMaterials(): array
    {
        return $this->missingMaterials;
    }
}
