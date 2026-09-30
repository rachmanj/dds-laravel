<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateDeliveryPartSpbRequest extends StoreDeliveryPartSpbRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $spb = $this->route('spb');

        return array_merge(parent::rules(), [
            'no_spb' => [
                'required',
                'string',
                'max:100',
                Rule::unique('delivery_part_spb', 'no_spb')
                    ->where(fn ($query) => $query->where('project_id', $this->input('project_id')))
                    ->ignore($spb instanceof \App\Models\DeliveryPartSpb ? $spb->id : $spb),
            ],
        ]);
    }
}
