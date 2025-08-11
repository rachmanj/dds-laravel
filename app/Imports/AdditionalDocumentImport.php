<?php

namespace App\Imports;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdditionalDocumentImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    use Importable;

    protected $documentTypeId;
    protected $batchNo;
    protected $checkDuplicates;
    protected $duplicateAction;
    protected $defaultValues;
    protected $successCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];

    public function __construct($documentTypeId = null, $defaultValues = [])
    {
        $this->documentTypeId = $documentTypeId;
        $this->batchNo = $this->getBatchNo();
        $this->defaultValues = array_merge([
            'status' => 'open',
            'cur_loc' => '000HLOG',
        ], $defaultValues);
    }

    public function model(array $row)
    {
        // Check if required fields exist - look for ito_no in Excel file
        if (!isset($row['ito_no']) || empty($row['ito_no'])) {
            $this->errors[] = 'Row skipped: Missing document number (ito_no)';
            $this->skippedCount++;
            return null;
        }

        // Determine document type
        $typeId = $this->getDocumentTypeId($row);
        if (!$typeId) {
            $this->errors[] = 'Row skipped: Invalid or missing document type';
            $this->skippedCount++;
            return null;
        }

        // Always check for duplicates and skip them
        $exists = AdditionalDocument::where('document_number', $row['ito_no'])
            ->where('type_id', $typeId)
            ->exists();

        if ($exists) {
            $this->skippedCount++;
            return null;
        }

        // Prepare data with defaults
        $data = $this->prepareDocumentData($row, $typeId);

        $this->successCount++;
        return new AdditionalDocument($data);
    }

    private function getDocumentTypeId($row)
    {
        // If specific type ID is provided, use it
        if ($this->documentTypeId) {
            return $this->documentTypeId;
        }

        // Try to find type by name in the row
        if (isset($row['document_type']) && !empty($row['document_type'])) {
            $type = AdditionalDocumentType::where('type_name', 'LIKE', '%' . $row['document_type'] . '%')
                ->orWhere('type_name', strtoupper($row['document_type']))
                ->orWhere('type_name', strtolower($row['document_type']))
                ->first();

            if ($type) {
                return $type->id;
            }
        }

        // Fallback to ITO type if no type specified
        return $this->getItoTypeId();
    }

    private function prepareDocumentData($row, $typeId)
    {
        $data = [
            'type_id' => $typeId,
            'document_number' => $row['ito_no'],
            'document_date' => $this->convertDate($row['ito_date'] ?? null),
            'po_no' => $row['po_no'] ?? null,
            'project' => $row['project'] ?? null,
            'receive_date' => $this->convertDate($row['ito_created_date'] ?? null),
            'created_by' => Auth::user()->id,
            'remarks' => $row['ito_remarks'] ?? null,
            'status' => $row['status'] ?? $this->defaultValues['status'],
            'cur_loc' => '000HLOG',
            'ito_creator' => $row['ito_creator'] ?? null,
            'grpo_no' => $row['grpo_no'] ?? null,
            'origin_wh' => $row['origin_wh'] ?? null,
            'destination_wh' => $row['destination_wh'] ?? null,
            'batch_no' => $this->batchNo,
        ];

        // Filter out null values
        return array_filter($data, function ($value) {
            return $value !== null;
        });
    }



    private function convertDate($date)
    {
        if (!$date) {
            return null;
        }

        try {
            if (is_string($date)) {
                // Handle dd-mm-yyyy format
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
                    $year = substr($date, 6, 4);
                    $month = substr($date, 3, 2);
                    $day = substr($date, 0, 2);
                    return $year . '-' . $month . '-' . $day;
                }

                // Handle dd/mm/yyyy format
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
                    $parts = explode('/', $date);
                    return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }

                // Try to parse with strtotime
                $timestamp = strtotime($date);
                if ($timestamp) {
                    return date('Y-m-d', $timestamp);
                }
            } elseif ($date instanceof \DateTime) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            Log::error('Error converting date: ' . $e->getMessage());
        }

        return null;
    }

    private function getItoTypeId()
    {
        try {
            $ito_type = AdditionalDocumentType::where('type_name', 'ITO')
                ->orWhere('type_name', 'ito')
                ->first();

            if (!$ito_type) {
                Log::error('ITO type not found in AdditionalDocumentType table');
                return null;
            }

            return $ito_type->id;
        } catch (\Exception $e) {
            Log::error('Error getting ITO type ID: ' . $e->getMessage());
            return null;
        }
    }

    private function getBatchNo()
    {
        try {
            $batch_no = AdditionalDocument::max('batch_no') ?? 0;
            return $batch_no + 1;
        } catch (\Exception $e) {
            Log::error('Error getting batch number: ' . $e->getMessage());
            return 1;
        }
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
