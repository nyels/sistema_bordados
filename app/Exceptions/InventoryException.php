<?php

namespace App\Exceptions;

use Exception;

class InventoryException extends Exception
{
    protected array $context;

    public function __construct(string $message, array $context = [], int $code = 0, ?Exception $previous = null)
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function insufficientStock(int $variantId, float $requested, float $available): self
    {
        return new self(
            "Stock insuficiente. Solicitado: {$requested}, Disponible: {$available}",
            [
                'variant_id' => $variantId,
                'requested' => $requested,
                'available' => $available,
            ]
        );
    }

    public static function invalidMovement(string $reason, array $context = []): self
    {
        return new self("Movimiento de inventario inválido: {$reason}", $context);
    }

    public static function variantNotFound(int $variantId): self
    {
        return new self(
            "Variante de material no encontrada",
            ['variant_id' => $variantId]
        );
    }
}
