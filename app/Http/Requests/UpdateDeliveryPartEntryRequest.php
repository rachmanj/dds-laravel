<?php

namespace App\Http\Requests;

use App\Models\DeliveryPartEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryPartEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit-delivery-part') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ito_no_override' => ['nullable', 'string', 'max:50'],
            'no_spb' => ['nullable', 'string', 'max:100'],
            'remarks_barang' => ['nullable', 'string', 'max:2000'],
            'tgl_delivery' => ['nullable', 'date'],
            'transporter' => ['nullable', 'string', 'max:255'],
            'unit_kendaraan' => ['nullable', 'string', 'max:255'],
            'ekspedisi' => ['nullable', 'string', 'max:255', Rule::in(DeliveryPartEntry::EKSPEDISI_OPTIONS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ekspedisi.in' => 'Ekspedisi harus salah satu dari: '.implode(', ', DeliveryPartEntry::EKSPEDISI_OPTIONS).'.',
            'tgl_delivery.date' => 'Tgl Delivery harus berformat tanggal yang valid.',
        ];
    }
}
