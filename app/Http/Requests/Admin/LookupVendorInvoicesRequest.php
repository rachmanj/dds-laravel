<?php

namespace App\Http\Requests\Admin;

use App\Services\VendorInvoiceFetchService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LookupVendorInvoicesRequest extends FormRequest
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
            'invoice_numbers' => ['required', 'string', 'max:10000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $parsed = VendorInvoiceFetchService::parseInvoiceNumbers($this->string('invoice_numbers')->toString());
            if ($parsed === []) {
                $v->errors()->add('invoice_numbers', 'Enter at least one invoice number.');
            }
        });
    }
}
