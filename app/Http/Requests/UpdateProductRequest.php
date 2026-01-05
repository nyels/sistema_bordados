<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $productId = $this->route('id');

        $rules = parent::rules();

        // SKU único excepto el actual
        $rules['sku'] = [
            'required',
            'string',
            'min:3',
            'max:50',
            'regex:/^[A-Z0-9\-\_]+$/u',
            Rule::unique('products', 'sku')->ignore($productId),
        ];

        return $rules;
    }
}
