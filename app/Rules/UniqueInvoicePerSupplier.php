<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Invoice;

class UniqueInvoicePerSupplier implements ValidationRule, DataAwareRule
{
    /**
     * The data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * The invoice being updated (for edit operations).
     */
    protected ?int $excludeId = null;

    /**
     * Create a new rule instance.
     */
    public function __construct(?int $excludeId = null)
    {
        $this->excludeId = $excludeId;
    }

    /**
     * Set the data under validation.
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($this->data['supplier_id'])) {
            return; // Let other validation rules handle missing supplier_id
        }

        $query = Invoice::where('supplier_id', $this->data['supplier_id'])
            ->where('invoice_number', $value);

        // Exclude current invoice when updating
        if ($this->excludeId) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('The invoice number has already been used for this supplier.');
        }
    }
}
