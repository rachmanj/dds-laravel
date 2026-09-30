<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseProjectMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-delivery-part-mapping') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'whs_code' => ['required', 'string', 'max:50', 'unique:logistics_warehouse_projects,whs_code'],
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'whs_code.required' => 'Kode warehouse wajib diisi.',
            'whs_code.unique' => 'Kode warehouse sudah dipetakan.',
            'project_id.required' => 'Project wajib dipilih.',
            'project_id.exists' => 'Project tidak ditemukan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('whs_code')) {
            $this->merge([
                'whs_code' => strtoupper(trim((string) $this->input('whs_code'))),
            ]);
        }
    }
}
