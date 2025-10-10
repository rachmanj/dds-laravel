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

class GeneralDocumentImport implements ToModel, WithHeadingRow, WithChunkReading
{
    use Importable;

    protected $batchNo;
    protected $defaultValues;
    protected $successCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $documentTypeCounts = [];

    public function __construct($defaultValues = [])
    {
        $this->batchNo = $this->getBatchNo();
        $this->defaultValues = array_merge([
            'status' => 'open',
            'cur_loc' => Auth::user()->department->location_code ?? '000HLOG',
        ], $defaultValues);

        // Initialize document type counters
        $this->documentTypeCounts = [
            'DO' => 0,
            'GR' => 0,
            'MR' => 0,
        ];

        Log::info('GeneralDocumentImport constructed with:', [
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
                Log::info('General Excel import starting', [
                    'total_rows' => $event->getReader()->getTotalRows(),
                    'chunk_size' => $this->chunkSize()
                ]);
            },

            \Maatwebsite\Excel\Events\AfterImport::class => function (\Maatwebsite\Excel\Events\AfterImport $event) {
                Log::info('General Excel import completed', [
                    'success_count' => $this->successCount,
                    'skipped_count' => $this->skippedCount,
                    'error_count' => count($this->errors),
                    'document_type_counts' => $this->documentTypeCounts
                ]);
            }
        ];
    }

    /**
     * Validate that the Excel file has the expected structure
     */
    public function validateExcelStructure(array $row): bool
    {
        $requiredColumns = ['description'];
        $missingColumns = [];

        foreach ($requiredColumns as $column) {
            if (!isset($row[$column])) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            Log::warning('General Excel structure validation failed:', [
                'missing_columns' => $missingColumns,
                'available_columns' => array_keys($row),
                'row_data' => $row
            ]);
            return false;
        }

        // Check if at least one document number field exists
        $documentFields = ['do_no', 'gr_no', 'mr_no'];
        $hasDocumentNumber = false;
        foreach ($documentFields as $field) {
            if (isset($row[$field]) && !empty($row[$field])) {
                $hasDocumentNumber = true;
                break;
            }
        }

        if (!$hasDocumentNumber) {
            Log::warning('No document number fields found in row:', [
                'row_data' => $row
            ]);
            return false;
        }

        Log::info('General Excel structure validation passed', [
            'available_columns' => array_keys($row),
            'has_document_numbers' => $hasDocumentNumber
        ]);

        return true;
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function model(array $row)
    {
        Log::info('Processing General Excel row:', [
            'row_data' => $row,
            'row_keys' => array_keys($row)
        ]);

        // Clean and normalize the row data
        $normalizedRow = $this->normalizeRowData($row);

        // Validate Excel structure first
        if (!$this->validateExcelStructure($normalizedRow)) {
            $this->errors[] = 'Row skipped: Invalid Excel structure - missing required columns or document numbers';
            $this->skippedCount++;
            return null;
        }

        // Process each document type that has data
        $documentsCreated = [];
        $rowErrors = [];

        // Process DO (Delivery Order)
        if (!empty($normalizedRow['do_no'])) {
            $documentDate = $normalizedRow['do_date'] ?? null;
            $result = $this->createDocument($normalizedRow, 'DO', $normalizedRow['do_no'], $documentDate);
            if ($result) {
                $documentsCreated[] = $result;
                $this->documentTypeCounts['DO']++;
            } else {
                $rowErrors[] = 'DO document creation failed';
            }
        }

        // Process GR (Goods Receipt)
        if (!empty($normalizedRow['gr_no'])) {
            $documentDate = $normalizedRow['gr_date'] ?? null;
            $result = $this->createDocument($normalizedRow, 'GR', $normalizedRow['gr_no'], $documentDate);
            if ($result) {
                $documentsCreated[] = $result;
                $this->documentTypeCounts['GR']++;
            } else {
                $rowErrors[] = 'GR document creation failed';
            }
        }

        // Process MR (Material Request)
        if (!empty($normalizedRow['mr_no'])) {
            $documentDate = $normalizedRow['mr_date'] ?? null;
            $result = $this->createDocument($normalizedRow, 'MR', $normalizedRow['mr_no'], $documentDate);
            if ($result) {
                $documentsCreated[] = $result;
                $this->documentTypeCounts['MR']++;
            } else {
                $rowErrors[] = 'MR document creation failed';
            }
        }

        // Handle errors
        if (!empty($rowErrors)) {
            $this->errors[] = 'Row errors: ' . implode(', ', $rowErrors);
        }

        // Update counters
        if (!empty($documentsCreated)) {
            $this->successCount += count($documentsCreated);
        } else {
            $this->skippedCount++;
        }

        // Return the first created document (Laravel Excel expects a single model)
        return !empty($documentsCreated) ? $documentsCreated[0] : null;
    }

    private function createDocument($row, $typeCode, $documentNumber, $documentDate)
    {
        try {
            // Validate document number
            if (empty($documentNumber)) {
                $this->errors[] = "Empty document number for {$typeCode} document";
                return null;
            }

            // Get or create document type
            $typeId = $this->getDocumentTypeId($typeCode);
            if (!$typeId) {
                $this->errors[] = "Failed to get document type for {$typeCode}";
                return null;
            }

            // Check for duplicates
            $exists = AdditionalDocument::where('document_number', $documentNumber)
                ->where('type_id', $typeId)
                ->exists();

            if ($exists) {
                Log::info("Document already exists, skipping: {$typeCode} - {$documentNumber}");
                $this->skippedCount++;
                return null; // Skip duplicate
            }

            // Convert and validate date
            $convertedDate = $this->convertDate($documentDate);
            if (!$convertedDate) {
                $this->errors[] = "Invalid date format for {$typeCode} document {$documentNumber}: {$documentDate}";
                return null;
            }

            // Prepare document data
            $data = [
                'type_id' => $typeId,
                'document_number' => trim($documentNumber),
                'document_date' => $convertedDate,
                'receive_date' => $convertedDate,
                'remarks' => $row['description'] ?? null,
                'created_by' => Auth::user()->id,
                'status' => $this->defaultValues['status'],
                'distribution_status' => 'available',
                'cur_loc' => $this->defaultValues['cur_loc'],
                'batch_no' => $this->batchNo,
            ];

            // Log the data being created for debugging
            Log::info('Creating General Document with data:', [
                'type' => $typeCode,
                'document_number' => $data['document_number'],
                'document_date' => $data['document_date'],
                'type_id' => $data['type_id']
            ]);

            // Create the document
            $document = new AdditionalDocument();
            $document->fill($data);
            $document->save();

            Log::info('Successfully created General Document:', [
                'id' => $document->id,
                'type' => $typeCode,
                'document_number' => $document->document_number,
                'document_date' => $document->document_date
            ]);

            return $document;
        } catch (\Exception $e) {
            Log::error('Error creating General Document:', [
                'error' => $e->getMessage(),
                'type' => $typeCode,
                'document_number' => $documentNumber,
                'document_date' => $documentDate,
                'trace' => $e->getTraceAsString()
            ]);

            $this->errors[] = "Error creating {$typeCode} document {$documentNumber}: " . $e->getMessage();
            return null;
        }
    }

    private function getDocumentTypeId($typeCode)
    {
        try {
            // Map type codes to full names
            $typeNames = [
                'DO' => 'Delivery Order',
                'GR' => 'Goods Receipt',
                'MR' => 'Material Requisition'
            ];

            $typeName = $typeNames[$typeCode] ?? $typeCode;

            // Try to find existing type
            $type = AdditionalDocumentType::where('type_name', $typeName)
                ->orWhere('type_name', $typeCode)
                ->first();

            if ($type) {
                return $type->id;
            }

            // Create new type if it doesn't exist
            $type = AdditionalDocumentType::create([
                'type_name' => $typeName
            ]);

            Log::info("Created new document type: {$typeName} (ID: {$type->id})");
            return $type->id;
        } catch (\Exception $e) {
            Log::error("Error getting/creating document type for {$typeCode}: " . $e->getMessage());
            return null;
        }
    }

    private function convertDate($date)
    {
        if (!$date) {
            // If no date provided, use current date as fallback
            return now()->format('Y-m-d');
        }

        try {
            // Handle Excel serial numbers (like 45915)
            if (is_numeric($date) && $date > 25569) { // Excel epoch starts at 25569 (1900-01-01)
                // Convert Excel serial number to date
                $unixTimestamp = ($date - 25569) * 86400; // 86400 seconds in a day
                try {
                    $dateObj = new \DateTime();
                    $dateObj->setTimestamp($unixTimestamp);
                    $formattedDate = $dateObj->format('Y-m-d');

                    Log::info('Converted Excel serial number to date:', [
                        'serial_number' => $date,
                        'unix_timestamp' => $unixTimestamp,
                        'formatted_date' => $formattedDate
                    ]);

                    return $formattedDate;
                } catch (\Exception $e) {
                    Log::error('Error converting Excel serial number:', [
                        'serial_number' => $date,
                        'error' => $e->getMessage()
                    ]);
                    return now()->format('Y-m-d');
                }
            }

            // Handle different date formats from Excel
            if (is_string($date)) {
                // Clean the string - remove extra spaces
                $date = trim($date);

                // Handle dd-mmm-yy format (from sample Excel)
                if (preg_match('/^\d{1,2}-\w{3}-\d{2}$/', $date)) {
                    $timestamp = strtotime($date);
                    if ($timestamp) {
                        return date('Y-m-d', $timestamp);
                    }
                }

                // Handle dd.mm.yyyy format
                if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $date)) {
                    $parts = explode('.', $date);
                    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    return $parts[2] . '-' . $month . '-' . $day;
                }

                // Handle dd-mm-yyyy format
                if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $date)) {
                    $parts = explode('-', $date);
                    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    return $parts[2] . '-' . $month . '-' . $day;
                }

                // Handle dd/mm/yyyy format
                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)) {
                    $parts = explode('/', $date);
                    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    return $parts[2] . '-' . $month . '-' . $day;
                }

                // Handle yyyy-mm-dd format
                if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $date)) {
                    $parts = explode('-', $date);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $day = str_pad($parts[2], 2, '0', STR_PAD_LEFT);
                    return $parts[0] . '-' . $month . '-' . $day;
                }

                // Try to parse with strtotime
                $timestamp = strtotime($date);
                if ($timestamp) {
                    return date('Y-m-d', $timestamp);
                }

                // If we can't parse it, log it and use current date
                Log::warning('Could not parse date, using current date as fallback:', [
                    'original_date' => $date,
                    'fallback_date' => now()->format('Y-m-d')
                ]);
            } elseif ($date instanceof \DateTime) {
                return $date->format('Y-m-d');
            } elseif ($date instanceof \Carbon\Carbon) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            Log::error('Error converting date, using current date as fallback:', [
                'original_date' => $date,
                'error' => $e->getMessage(),
                'fallback_date' => now()->format('Y-m-d')
            ]);
        }

        // Always return a valid date - use current date as fallback
        return now()->format('Y-m-d');
    }

    private function normalizeRowData(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            // Clean the key by removing extra spaces and converting to lowercase
            $cleanKey = trim(strtolower($key));

            // Map various possible column names to our standard keys
            switch ($cleanKey) {
                case 'description':
                case 'descriptions':
                    $normalized['description'] = $value;
                    break;

                case 'do_no':
                case 'do no':
                case 'dono':
                case 'delivery_order_no':
                    $normalized['do_no'] = $value;
                    break;

                case 'do_date':
                case 'do date':
                case 'dodate':
                case 'delivery_order_date':
                    $normalized['do_date'] = $value;
                    break;

                case 'gr_no':
                case 'gr no':
                case 'grno':
                case 'goods_receipt_no':
                    $normalized['gr_no'] = $value;
                    break;

                case 'gr_date':
                case 'gr date':
                case 'grdate':
                case 'goods_receipt_date':
                    $normalized['gr_date'] = $value;
                    break;

                case 'mr_no':
                case 'mr no':
                case 'mrno':
                case 'material_request_no':
                    $normalized['mr_no'] = $value;
                    break;

                case 'mr_date':
                case 'mr date':
                case 'mrdate':
                case 'material_request_date':
                    $normalized['mr_date'] = $value;
                    break;

                default:
                    // Keep original key for any unmapped columns
                    $normalized[$cleanKey] = $value;
                    break;
            }
        }

        Log::info('Normalized General row data:', [
            'original' => $row,
            'normalized' => $normalized
        ]);

        return $normalized;
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
        return 50;
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

    public function getDocumentTypeCounts()
    {
        return $this->documentTypeCounts;
    }
}
