<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDeliveryPartSpbRequest extends FormRequest
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
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'no_spb' => [
                'required',
                'string',
                'max:100',
                Rule::unique('delivery_part_spb', 'no_spb')
                    ->where(fn ($query) => $query->where('project_id', $this->input('project_id'))),
            ],
            'tanggal' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.part_number' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.uom' => ['nullable', 'string', 'max:50'],
            'items.*.remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);
            if (! is_array($items)) {
                return;
            }

            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $partNumber = trim((string) ($item['part_number'] ?? ''));
                $description = trim((string) ($item['description'] ?? ''));
                if ($partNumber === '' && $description === '') {
                    $validator->errors()->add(
                        "items.{$index}.part_number",
                        'Part number atau description harus diisi pada setiap baris.'
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_spb.required' => 'Nomor SPB wajib diisi.',
            'no_spb.unique' => 'Nomor SPB sudah digunakan untuk site ini.',
            'items.min' => 'Minimal satu baris barang harus diisi.',
            'items.*.qty.min' => 'QTY tidak boleh negatif.',
        ];
    }
}
