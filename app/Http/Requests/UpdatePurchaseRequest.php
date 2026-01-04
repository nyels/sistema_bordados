<?php

namespace App\Http\Requests;

use App\Models\Purchase;

class UpdatePurchaseRequest extends StorePurchaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validación adicional: verificar que la compra se puede editar
     */
    public function withValidator($validator): void
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            $purchaseId = $this->route('id');

            if ($purchaseId) {
                $purchase = Purchase::find($purchaseId);

                if ($purchase && !$purchase->can_edit) {
                    $validator->errors()->add(
                        'purchase',
                        "No se puede modificar la compra en estado: {$purchase->status->label()}"
                    );
                }
            }
        });
    }
}
