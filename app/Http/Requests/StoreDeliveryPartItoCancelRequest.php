<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryPartItoCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cancel-ito') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'doc_entry' => ['required', 'integer', 'min:1'],
            'ito_no' => ['required', 'string', 'max:50'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'item_code' => ['nullable', 'string', 'max:100'],
            'unit_no' => ['nullable', 'string', 'max:100'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.min' => 'Alasan pembatalan wajib diisi minimal 10 karakter.',
            'reason.required' => 'Alasan pembatalan wajib diisi.',
        ];
    }
}
