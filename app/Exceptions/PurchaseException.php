<?php

namespace App\Exceptions;

use Exception;

class PurchaseException extends Exception
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

    public static function cannotModify(int $purchaseId, string $status): self
    {
        return new self(
            "No se puede modificar la compra en estado: {$status}",
            ['purchase_id' => $purchaseId, 'status' => $status]
        );
    }

    public static function cannotReceive(int $purchaseId, string $status): self
    {
        return new self(
            "No se puede recibir la compra en estado: {$status}",
            ['purchase_id' => $purchaseId, 'status' => $status]
        );
    }

    public static function cannotCancel(int $purchaseId, string $reason): self
    {
        return new self(
            "No se puede cancelar la compra: {$reason}",
            ['purchase_id' => $purchaseId, 'reason' => $reason]
        );
    }

    public static function invalidItem(string $reason, array $context = []): self
    {
        return new self("Item de compra inválido: {$reason}", $context);
    }

    public static function duplicateNumber(string $purchaseNumber): self
    {
        return new self(
            "El número de compra ya existe: {$purchaseNumber}",
            ['purchase_number' => $purchaseNumber]
        );
    }
}
