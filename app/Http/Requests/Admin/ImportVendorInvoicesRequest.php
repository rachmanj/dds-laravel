<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportVendorInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['superadmin', 'admin', 'accounting'])
            || $user->can('create-invoices');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_nos' => ['required', 'array', 'min:1'],
            'invoice_nos.*' => ['required', 'string', 'max:255'],
        ];
    }
}
