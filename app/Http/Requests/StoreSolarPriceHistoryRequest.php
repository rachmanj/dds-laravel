<?php

namespace App\Http\Requests;

use App\Models\InvoiceLineDetail;
use App\Services\PertaminaSolarInvoiceResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSolarPriceHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-solar-price-histories');
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('quantity') === '' || $this->input('quantity') === null) {
            $this->merge(['quantity' => null]);
        }
        if ($this->input('amount') === '' || $this->input('amount') === null) {
            $this->merge(['amount' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'exists:invoices,id'],
            'invoice_line_detail_id' => [
                'required',
                'integer',
                Rule::exists('invoice_line_details', 'id')->where(
                    'invoice_id',
                    (int) $this->input('invoice_id')
                ),
            ],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $lineId = $this->input('invoice_line_detail_id');
            if (! $lineId) {
                return;
            }
            $line = InvoiceLineDetail::query()->find($lineId);
            if (! $line) {
                return;
            }
            if (stripos($line->description, 'SOLAR') === false) {
                $v->errors()->add(
                    'invoice_line_detail_id',
                    'The selected line must have “SOLAR” in its description.'
                );
            }
            $invoice = $line->invoice;
            if (! $invoice) {
                return;
            }
            if ($invoice->supplier?->name !== PertaminaSolarInvoiceResolver::PERTAMINA_SUPPLIER_NAME) {
                $v->errors()->add('invoice_id', 'Invoice must be from supplier PERTAMINA.');
            }
        });
    }
}
