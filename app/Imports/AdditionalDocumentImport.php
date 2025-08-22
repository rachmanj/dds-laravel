<?php

namespace App\Imports;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdditionalDocumentImport implements ToModel, WithHeadingRow, WithChunkReading
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

        // Log the constructor parameters for debugging
        Log::info('AdditionalDocumentImport constructed with:', [
            'document_type_id' => $documentTypeId,
            'default_values' => $this->defaultValues
        ]);
    }

    /**
     * Called before the import starts
     */
    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\BeforeImport::class => function (\Maatwebsite\Excel\Events\BeforeImport $event) {
                Log::info('Excel import starting', [
                    'total_rows' => $event->getReader()->getTotalRows(),
                    'chunk_size' => $this->chunkSize()
                ]);
            },

            \Maatwebsite\Excel\Events\BeforeSheet::class => function (\Maatwebsite\Excel\Events\BeforeSheet $event) {
                Log::info('Processing Excel sheet:', [
                    'sheet_name' => $event->getSheet()->getTitle(),
                    'highest_row' => $event->getSheet()->getHighestRow(),
                    'highest_column' => $event->getSheet()->getHighestColumn()
                ]);
            },

            \Maatwebsite\Excel\Events\AfterImport::class => function (\Maatwebsite\Excel\Events\AfterImport $event) {
                Log::info('Excel import completed', [
                    'success_count' => $this->successCount,
                    'skipped_count' => $this->skippedCount,
                    'error_count' => count($this->errors)
                ]);
            }
        ];
    }

    /**
     * Validate that the Excel file has the expected structure
     */
    public function validateExcelStructure(array $row): bool
    {
        $requiredColumns = ['ito_no', 'ito_date'];
        $missingColumns = [];

        foreach ($requiredColumns as $column) {
            if (!isset($row[$column])) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            Log::warning('Excel structure validation failed:', [
                'missing_columns' => $missingColumns,
                'available_columns' => array_keys($row),
                'row_data' => $row
            ]);
            return false;
        }

        // Log successful validation
        Log::info('Excel structure validation passed', [
            'available_columns' => array_keys($row),
            'required_columns' => $requiredColumns
        ]);

        return true;
    }

    /**
     * Ensure the Excel file has the correct structure
     */
    public function startCell(): string
    {
        return 'A1'; // Start from the first cell to ensure we get headers
    }

    /**
     * Custom heading row processor to ensure proper column mapping
     */
    public function headingRow(): int
    {
        return 1; // First row contains headers
    }



    public function model(array $row)
    {
        // Debug: Log the row data to see what's being received
        Log::info('Processing Excel row:', [
            'row_data' => $row,
            'row_keys' => array_keys($row)
        ]);

        // Clean and normalize the row data
        $normalizedRow = $this->normalizeRowData($row);

        // Validate Excel structure first
        if (!$this->validateExcelStructure($normalizedRow)) {
            $this->errors[] = 'Row skipped: Invalid Excel structure - missing required columns';
            $this->skippedCount++;
            return null;
        }

        // Check if required fields exist - look for ito_no in Excel file
        if (!isset($normalizedRow['ito_no']) || empty($normalizedRow['ito_no'])) {
            $this->errors[] = 'Row skipped: Missing document number (ito_no)';
            $this->skippedCount++;
            return null;
        }

        // Determine document type
        $typeId = $this->getDocumentTypeId($normalizedRow);
        if (!$typeId) {
            $this->errors[] = 'Row skipped: Invalid or missing document type';
            $this->skippedCount++;
            return null;
        }

        // Always check for duplicates and skip them
        $exists = AdditionalDocument::where('document_number', $normalizedRow['ito_no'])
            ->where('type_id', $typeId)
            ->exists();

        if ($exists) {
            $this->skippedCount++;
            return null;
        }

        // Prepare data with defaults
        $data = $this->prepareDocumentData($normalizedRow, $typeId);

        // Define valid database columns based on the actual table structure
        $validColumns = [
            'type_id',
            'document_number',
            'document_date',
            'po_no',
            'project',
            'receive_date',
            'created_by',
            'attachment',
            'remarks',
            'flag',
            'status',
            'distribution_status',
            'cur_loc',
            'ito_creator',
            'grpo_no',
            'origin_wh',
            'destination_wh',
            'batch_no'
        ];

        // Start with clean data array and only include valid columns
        $filteredData = [];

        // Set essential columns first
        $filteredData['type_id'] = $typeId;
        $filteredData['created_by'] = Auth::user()->id;
        $filteredData['status'] = $this->defaultValues['status'];
        $filteredData['distribution_status'] = 'available';
        $filteredData['cur_loc'] = $this->defaultValues['cur_loc'];
        $filteredData['batch_no'] = $this->batchNo;

        // Add optional columns if they exist in data
        foreach ($data as $key => $value) {
            if (in_array($key, $validColumns) && $value !== null) {
                $filteredData[$key] = $value;
            }
        }

        // Log the final filtered data for debugging
        Log::info('Final filtered data for model creation:', $filteredData);

        $this->successCount++;

        try {
            // Create and save the model directly instead of using batch insert
            $model = new AdditionalDocument();
            $model->fill($filteredData);
            $model->save();

            Log::info('Successfully created AdditionalDocument:', [
                'id' => $model->id,
                'document_number' => $model->document_number
            ]);

            return $model;
        } catch (\Exception $e) {
            Log::error('Error creating AdditionalDocument model:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $filteredData,
                'row' => $row
            ]);

            $this->errors[] = 'Row skipped: Error creating document: ' . $e->getMessage();
            $this->skippedCount++;
            $this->successCount--; // Decrease success count since we're actually skipping

            return null;
        }
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
        // Map Excel columns to database columns
        $data = [
            'document_number' => $row['ito_no'] ?? null,
            'document_date' => $this->convertDate($row['ito_date'] ?? null),
            'po_no' => $row['po_no'] ?? null,
            'receive_date' => $this->convertDate($row['ito_date'] ?? null), // Use ito_date as receive_date
            'remarks' => $row['ito_remar'] ?? null, // Map to ito_remar from Excel
            'grpo_no' => $row['grpo_no'] ?? null,
            'origin_wh' => $row['origin_wh'] ?? null,
            'destination_wh' => $row['destinatic'] ?? null, // Map to destinatic from Excel
            'ito_creator' => $row['user_nam'] ?? null, // Map to User Nam from Excel
        ];

        // Log the data being prepared for debugging
        Log::info('Preparing document data:', [
            'excel_row' => $row,
            'mapped_data' => $data,
            'type_id' => $typeId
        ]);

        return $data;
    }

    private function convertDate($date)
    {
        if (!$date) {
            return null;
        }

        try {
            // Handle different date formats from Excel
            if (is_string($date)) {
                // Handle dd.mm.yyyy format (from Excel sample)
                if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
                    $parts = explode('.', $date);
                    return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }

                // Handle dd-mm-yyyy format
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
                    $parts = explode('-', $date);
                    return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
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
                ->orWhere('type_name', 'Ito')
                ->first();

            if ($ito_type) {
                return $ito_type->id;
            }

            // If ITO type doesn't exist, create it
            $ito_type = AdditionalDocumentType::create([
                'type_name' => 'ITO'
            ]);

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



    public function chunkSize(): int
    {
        return 50; // Reduced from 100 to avoid batch insert issues
    }

    /**
     * Normalize row data to handle different Excel column header formats
     */
    private function normalizeRowData(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            // Clean the key by removing extra spaces and converting to lowercase
            $cleanKey = trim(strtolower($key));

            // Map various possible column names to our standard keys
            switch ($cleanKey) {
                case 'ito_no':
                case 'ito no':
                case 'itono':
                    $normalized['ito_no'] = $value;
                    break;

                case 'ito_date':
                case 'ito date':
                case 'itodate':
                    $normalized['ito_date'] = $value;
                    break;

                case 'po_no':
                case 'po no':
                case 'pono':
                    $normalized['po_no'] = $value;
                    break;

                case 'grpo_no':
                case 'grpo no':
                case 'grpono':
                    $normalized['grpo_no'] = $value;
                    break;

                case 'origin_wh':
                case 'origin wh':
                case 'originwh':
                    $normalized['origin_wh'] = $value;
                    break;

                case 'destinatic':
                case 'destination':
                case 'destination_wh':
                case 'destination wh':
                    $normalized['destinatic'] = $value;
                    break;

                case 'ito_remar':
                case 'ito remar':
                case 'ito remarks':
                case 'remarks':
                    $normalized['ito_remar'] = $value;
                    break;

                case 'user nam':
                case 'user name':
                case 'username':
                case 'user_nam':
                    $normalized['user_nam'] = $value;
                    break;

                default:
                    // Keep original key for any unmapped columns
                    $normalized[$cleanKey] = $value;
                    break;
            }
        }

        Log::info('Normalized row data:', [
            'original' => $row,
            'normalized' => $normalized
        ]);

        return $normalized;
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
