<?php

namespace App\Http\Requests;

class StoreDeliveryPartEntryRequest extends UpdateDeliveryPartEntryRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'project_code' => ['required', 'string', 'max:20'],
            'ito_no' => ['nullable', 'string', 'max:50'],
            'item_code' => ['nullable', 'string', 'max:100'],
            'unit_no' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
