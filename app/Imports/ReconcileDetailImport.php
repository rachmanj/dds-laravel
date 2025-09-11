<?php

namespace App\Imports;

use App\Models\ReconcileDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ReconcileDetailImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $tempFlag = 'TEMP' . Auth::id();


        // Try multiple possible column names for invoice number
        $invoiceNo = $this->normalizeInvoiceNumber(
            $row['invoice_no'] ??
                $row['invoice_number'] ??
                $row['invoice'] ??
                $row['Invoice Number'] ??
                $row['INVOICE_NO'] ??
                $row['INVOICE_NUMBER'] ??
                $row['Invoice'] ??
                null
        );

        // Try multiple possible column names for invoice date
        $invoiceDate = $this->parseDate(
            $row['invoice_date'] ??
                $row['date'] ??
                $row['Invoice Date'] ??
                $row['INVOICE_DATE'] ??
                $row['Date'] ??
                null
        );


        // Manual validation - check if invoice number exists
        if (empty($invoiceNo)) {
            throw new \Exception('Invoice number is required. Available columns: ' . implode(', ', array_keys($row)));
        }

        return new ReconcileDetail([
            'invoice_no' => $invoiceNo,
            'invoice_date' => $invoiceDate,
            'user_id' => Auth::id(),
            'flag' => $tempFlag,
        ]);
    }

    /**
     * Validation rules for the import.
     */
    public function rules(): array
    {
        return [
            // We'll handle validation manually in the model method
        ];
    }

    /**
     * Custom validation messages.
     */
    public function customValidationMessages()
    {
        return [
            'invoice_no.required' => 'Invoice number is required.',
            'invoice_no.string' => 'Invoice number must be a string.',
            'invoice_no.max' => 'Invoice number cannot exceed 255 characters.',
            'invoice_date.date' => 'Invoice date must be a valid date.',
        ];
    }

    /**
     * Normalize invoice number by removing extra spaces and converting to uppercase.
     */
    private function normalizeInvoiceNumber($invoiceNo)
    {
        if (empty($invoiceNo)) {
            return null;
        }

        return strtoupper(trim($invoiceNo));
    }

    /**
     * Parse date from various formats.
     */
    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Handle Excel date serial numbers
            if (is_numeric($date)) {
                $unixDate = ($date - 25569) * 86400;
                return date('Y-m-d', $unixDate);
            }

            // Handle string dates
            if (is_string($date)) {
                $parsedDate = \Carbon\Carbon::parse($date);
                return $parsedDate->format('Y-m-d');
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Handle import failures.
     */
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        $errors = [];
        foreach ($failures as $failure) {
            $errors[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
        }

        throw new \Exception('Import validation failed: ' . implode('; ', $errors));
    }
}
