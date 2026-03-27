# SAP B1 AP Invoice Integration - Implementation Plan

**Date**: 2025-01-27  
**Status**: Ready for Implementation  
**Based on**: [SAP-AP-INVOICE-INTEGRATION-CONCEPT.md](./SAP-AP-INVOICE-INTEGRATION-CONCEPT.md)

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Phase 1: Core Functionality](#phase-1-core-functionality)
4. [Phase 2: Field Mapping](#phase-2-field-mapping)
5. [Phase 3: UI & UX](#phase-3-ui--ux)
6. [Phase 4: Advanced Features](#phase-4-advanced-features)
7. [Testing Strategy](#testing-strategy)
8. [Deployment Checklist](#deployment-checklist)

---

## Overview

This implementation plan provides step-by-step instructions for implementing SAP B1 AP Invoice integration. The implementation is divided into 4 phases, with Phase 1 being the foundation.

### Implementation Phases

- **Phase 1**: Core Functionality (Week 1) - Foundation
- **Phase 2**: Field Mapping (Week 2) - Complete data mapping
- **Phase 3**: UI & UX (Week 3) - User interface enhancements
- **Phase 4**: Advanced Features (Future) - Multi-line items, GRPO integration

---

## Prerequisites

### 1. Environment Setup

- ✅ Laravel 11+ application running
- ✅ SAP B1 Service Layer accessible
- ✅ SAP credentials configured in `.env`
- ✅ Queue worker running (for async job processing)
- ✅ Database access configured

### 2. SAP B1 Configuration

Verify SAP B1 endpoints are accessible:
- `POST /Login` - Authentication
- `POST /Invoices` - AP Invoice creation
- `GET /ProjectsService_GetProjectList` - Projects sync
- `GET /ProfitCenters` - Profit Centers sync
- `GET /BusinessPartners` - Supplier validation
- `GET /GoodsReceiptPOs` - GRPO query (Phase 2)

### 3. Required Permissions

Create these permissions (if not exists):
- `invoices.view`
- `invoices.sap-sync` - Permission to sync invoices to SAP
- `sap-projects.view` - View SAP projects
- `sap-projects.sync` - Sync SAP projects
- `sap-departments.view` - View SAP departments
- `sap-departments.sync` - Sync SAP departments

---

## Phase 1: Core Functionality

**Duration**: Week 1  
**Goal**: Establish foundation for SAP AP Invoice integration

### Task 1.1: Create SAP Project Model and Migration

**Files to Create**:
- `database/migrations/YYYY_MM_DD_HHMMSS_create_sap_projects_table.php`
- `app/Models/SapProject.php`

**Migration**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_projects', function (Blueprint $table) {
            $table->id();
            $table->string('sap_code', 20)->unique()->comment('SAP ProjectCode');
            $table->string('name', 255)->comment('SAP ProjectName');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            
            $table->index('sap_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_projects');
    }
};
```

**Model** (`app/Models/SapProject.php`):
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SapProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'sap_code',
        'name',
        'description',
        'is_active',
        'synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

**Action Items**:
- [ ] Create migration file
- [ ] Create model file
- [ ] Run migration: `php artisan migrate`
- [ ] Test model creation

---

### Task 1.2: Create SAP Department Model and Migration

**Files to Create**:
- `database/migrations/YYYY_MM_DD_HHMMSS_create_sap_departments_table.php`
- `app/Models/SapDepartment.php`

**Migration**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_departments', function (Blueprint $table) {
            $table->id();
            $table->string('sap_code', 20)->unique()->comment('SAP CenterCode (Profit Center)');
            $table->string('name', 255)->comment('SAP CenterName');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            
            $table->index('sap_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_departments');
    }
};
```

**Model** (`app/Models/SapDepartment.php`):
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SapDepartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sap_code',
        'name',
        'description',
        'is_active',
        'synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

**Action Items**:
- [ ] Create migration file
- [ ] Create model file
- [ ] Run migration: `php artisan migrate`
- [ ] Test model creation

---

### Task 1.3: Create SAP Project Sync Service

**Files to Create**:
- `app/Services/SapProjectSyncService.php`

**Service** (Reference: Projects-Departments guide pattern):
```php
<?php

namespace App\Services;

use App\Models\SapProject;
use App\Services\SapService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SapProjectSyncService
{
    protected SapService $sapService;

    public function __construct(SapService $sapService)
    {
        $this->sapService = $sapService;
    }

    public function syncProjects(): array
    {
        try {
            $this->sapService->ensureSession();

            // Call SAP B1 ProjectsService_GetProjectList
            $response = $this->sapService->get('ProjectsService_GetProjectList');
            
            $projects = $response['value'] ?? (is_array($response) ? $response : []);
            
            $stats = [
                'total' => count($projects),
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
                'error_messages' => [],
            ];

            DB::beginTransaction();

            try {
                foreach ($projects as $sapProject) {
                    try {
                        $projectCode = $sapProject['ProjectCode'] ?? $sapProject['Code'] ?? null;
                        $projectName = $sapProject['ProjectName'] ?? $sapProject['Name'] ?? null;

                        if (!$projectCode || !$projectName) {
                            $stats['errors']++;
                            $stats['error_messages'][] = 'Missing ProjectCode or ProjectName: ' . json_encode($sapProject);
                            continue;
                        }

                        // Upsert by sap_code
                        $project = SapProject::where('sap_code', $projectCode)->first();

                        if ($project) {
                            $project->update([
                                'name' => $projectName,
                                'description' => $sapProject['ProjectDescription'] ?? $sapProject['Name'] ?? null,
                                'is_active' => $sapProject['Active'] ?? true,
                                'synced_at' => now(),
                            ]);
                            $stats['updated']++;
                        } else {
                            SapProject::create([
                                'sap_code' => $projectCode,
                                'name' => $projectName,
                                'description' => $sapProject['ProjectDescription'] ?? $sapProject['Name'] ?? null,
                                'is_active' => $sapProject['Active'] ?? true,
                                'synced_at' => now(),
                            ]);
                            $stats['created']++;
                        }
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        $stats['error_messages'][] = "Error processing project {$projectCode}: " . $e->getMessage();
                        Log::error('Error syncing SAP project', [
                            'project' => $sapProject,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                Log::info('SAP Projects sync completed', $stats);

                return [
                    'success' => true,
                    'message' => "Sync completed: {$stats['created']} created, {$stats['updated']} updated, {$stats['errors']} errors",
                    'stats' => $stats,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('SAP Projects sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'stats' => [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'errors' => 1,
                    'error_messages' => [$e->getMessage()],
                ],
            ];
        }
    }
}
```

**Action Items**:
- [ ] Create service file
- [ ] Add `ensureSession()` method to `SapService` if not exists
- [ ] Add `get()` method to `SapService` if not exists
- [ ] Test sync service manually
- [ ] Create artisan command for testing: `php artisan sap:sync-projects`

---

### Task 1.4: Create SAP Department Sync Service

**Files to Create**:
- `app/Services/SapDepartmentSyncService.php`

**Service** (Similar pattern to Project sync):
```php
<?php

namespace App\Services;

use App\Models\SapDepartment;
use App\Services\SapService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SapDepartmentSyncService
{
    protected SapService $sapService;

    public function __construct(SapService $sapService)
    {
        $this->sapService = $sapService;
    }

    public function syncDepartments(): array
    {
        try {
            $this->sapService->ensureSession();

            // Call SAP B1 ProfitCenters
            $response = $this->sapService->get('ProfitCenters', [
                'query' => [
                    '$select' => 'CenterCode,CenterName',
                ],
            ]);
            
            $departments = $response['value'] ?? (is_array($response) ? $response : []);
            
            $stats = [
                'total' => count($departments),
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
                'error_messages' => [],
            ];

            DB::beginTransaction();

            try {
                foreach ($departments as $sapDepartment) {
                    try {
                        $centerCode = $sapDepartment['CenterCode'] ?? null;
                        $centerName = $sapDepartment['CenterName'] ?? null;

                        if (!$centerCode || !$centerName) {
                            $stats['errors']++;
                            $stats['error_messages'][] = 'Missing CenterCode or CenterName: ' . json_encode($sapDepartment);
                            continue;
                        }

                        // Upsert by sap_code
                        $department = SapDepartment::where('sap_code', $centerCode)->first();

                        if ($department) {
                            $department->update([
                                'name' => $centerName,
                                'is_active' => true,
                                'synced_at' => now(),
                            ]);
                            $stats['updated']++;
                        } else {
                            SapDepartment::create([
                                'sap_code' => $centerCode,
                                'name' => $centerName,
                                'is_active' => true,
                                'synced_at' => now(),
                            ]);
                            $stats['created']++;
                        }
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        $stats['error_messages'][] = "Error processing department {$centerCode}: " . $e->getMessage();
                        Log::error('Error syncing SAP department', [
                            'department' => $sapDepartment,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                Log::info('SAP Departments sync completed', $stats);

                return [
                    'success' => true,
                    'message' => "Sync completed: {$stats['created']} created, {$stats['updated']} updated, {$stats['errors']} errors",
                    'stats' => $stats,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('SAP Departments sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'stats' => [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'errors' => 1,
                    'error_messages' => [$e->getMessage()],
                ],
            ];
        }
    }
}
```

**Action Items**:
- [ ] Create service file
- [ ] Test sync service manually
- [ ] Create artisan command for testing: `php artisan sap:sync-departments`

---

### Task 1.5: Create SAP AP Invoice Payload Builder

**Files to Create**:
- `app/Services/SapApInvoicePayloadBuilder.php`

**Service** (Reference: `SapArInvoiceBuilder` pattern):
```php
<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SapProject;
use App\Models\SapDepartment;
use Illuminate\Support\Facades\Log;

class SapApInvoicePayloadBuilder
{
    protected Invoice $invoice;
    protected array $config;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->config = config('services.sap.ap_invoice', []);
    }

    /**
     * Build complete AP Invoice payload for SAP B1
     */
    public function build(): array
    {
        $this->validate();

        $payload = [
            'CardCode' => $this->mapSupplier(),
            'DocDate' => $this->invoice->invoice_date->format('Y-m-d'),
            'DocDueDate' => $this->mapDueDate(),
            'DocCurrency' => $this->invoice->currency,
            'NumAtCard' => $this->invoice->invoice_number,
            'Comments' => $this->invoice->remarks ?? 'Imported from DDS - Invoice #' . $this->invoice->id,
            'DocumentLines' => $this->mapLineItems(),
        ];

        // Add PO reference if available
        if ($this->invoice->po_no) {
            $payload['Reference1'] = $this->invoice->po_no;
        }

        return $payload;
    }

    /**
     * Validate invoice data before building payload
     */
    public function validate(): array
    {
        $errors = [];

        if (!$this->invoice->supplier || !$this->invoice->supplier->sap_code) {
            $errors[] = 'Supplier does not have SAP code';
        }

        if ($this->invoice->amount <= 0) {
            $errors[] = 'Invoice amount must be greater than 0';
        }

        if (!$this->invoice->invoice_date) {
            $errors[] = 'Invoice date is required';
        }

        if (!empty($errors)) {
            throw new \Exception('Validation failed: ' . implode(', ', $errors));
        }

        return [];
    }

    /**
     * Map supplier to SAP CardCode
     */
    protected function mapSupplier(): string
    {
        if (!$this->invoice->supplier || !$this->invoice->supplier->sap_code) {
            throw new \Exception('Supplier does not have SAP code');
        }

        return $this->invoice->supplier->sap_code;
    }

    /**
     * Map payment due date
     */
    protected function mapDueDate(): string
    {
        if ($this->invoice->payment_date) {
            return $this->invoice->payment_date->format('Y-m-d');
        }

        // Default: 30 days from invoice date
        return $this->invoice->invoice_date->addDays(30)->format('Y-m-d');
    }

    /**
     * Map line items
     */
    protected function mapLineItems(): array
    {
        $projectCode = $this->mapProjectCode();
        $costingCode = $this->mapCostingCode();
        $taxCode = $this->determineTaxCode();

        return [
            [
                'ItemCode' => $this->config['default_item_code'] ?? 'SERVICE',
                'Quantity' => 1,
                'UnitPrice' => $this->invoice->amount,
                'TaxCode' => $taxCode,
                'LineTotal' => $this->invoice->amount,
                'ProjectCode' => $projectCode,
                'CostingCode' => $costingCode,
            ]
        ];
    }

    /**
     * Map project code from invoice to SAP ProjectCode
     */
    protected function mapProjectCode(): ?string
    {
        if (!$this->invoice->invoice_project) {
            return null;
        }

        // Try direct match by sap_code first
        $sapProject = SapProject::where('sap_code', $this->invoice->invoice_project)
            ->active()
            ->first();

        if ($sapProject) {
            return $sapProject->sap_code;
        }

        // Try match by name (if invoice_project contains name instead of code)
        $sapProject = SapProject::where('name', $this->invoice->invoice_project)
            ->active()
            ->first();

        return $sapProject?->sap_code;
    }

    /**
     * Map cost center from invoice location to SAP CostingCode
     */
    protected function mapCostingCode(): ?string
    {
        if (!$this->invoice->cur_loc) {
            return null;
        }

        // Try direct match by sap_code first
        $sapDepartment = SapDepartment::where('sap_code', $this->invoice->cur_loc)
            ->active()
            ->first();

        if ($sapDepartment) {
            return $sapDepartment->sap_code;
        }

        // Try match by name (if cur_loc contains name instead of code)
        $sapDepartment = SapDepartment::where('name', $this->invoice->cur_loc)
            ->active()
            ->first();

        return $sapDepartment?->sap_code;
    }

    /**
     * Determine tax code for invoice
     */
    protected function determineTaxCode(): string
    {
        $taxConfig = $this->config['tax_codes'] ?? [];

        // Check by currency
        if (isset($taxConfig['by_currency'][$this->invoice->currency])) {
            return $taxConfig['by_currency'][$this->invoice->currency];
        }

        // Check by invoice type (if configured)
        if ($this->invoice->type && isset($taxConfig['by_invoice_type'][$this->invoice->type->type_name])) {
            return $taxConfig['by_invoice_type'][$this->invoice->type->type_name];
        }

        // Default
        return $taxConfig['default'] ?? 'EXEMPT';
    }

    /**
     * Get preview data for UI (future use)
     */
    public function getPreviewData(): array
    {
        return [
            'ap_invoice' => [
                'supplier' => [
                    'code' => $this->invoice->supplier->sap_code ?? null,
                    'name' => $this->invoice->supplier->name ?? null,
                ],
                'invoice_number' => $this->invoice->invoice_number,
                'invoice_date' => $this->invoice->invoice_date->format('Y-m-d'),
                'due_date' => $this->mapDueDate(),
                'amount' => $this->invoice->amount,
                'currency' => $this->invoice->currency,
                'po_no' => $this->invoice->po_no,
                'project' => [
                    'code' => $this->mapProjectCode(),
                    'name' => SapProject::where('sap_code', $this->mapProjectCode())->first()?->name,
                ],
                'cost_center' => [
                    'code' => $this->mapCostingCode(),
                    'name' => SapDepartment::where('sap_code', $this->mapCostingCode())->first()?->name,
                ],
                'tax_code' => $this->determineTaxCode(),
            ],
        ];
    }
}
```

**Action Items**:
- [ ] Create service file
- [ ] Add config file: `config/services.php` → `sap.ap_invoice` section
- [ ] Test payload builder with sample invoice
- [ ] Verify payload structure matches SAP B1 requirements

---

### Task 1.6: Complete CreateSapApInvoiceJob

**Files to Update**:
- `app/Jobs/CreateSapApInvoiceJob.php`

**Complete Implementation**:
```php
<?php

namespace App\Jobs;

use App\Services\SapService;
use App\Services\SapApInvoicePayloadBuilder;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\RequestException;

class CreateSapApInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // Exponential backoff in seconds

    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function handle(SapService $sapService)
    {
        $invoice = $this->invoice->fresh(['supplier']);
        if (!$invoice) {
            Log::channel('sap')->error('CreateSapApInvoiceJob: Invoice reference could not be reloaded.');
            return;
        }

        $payload = null;

        try {
            // Validate supplier has SAP code
            if (!$invoice->supplier || !$invoice->supplier->sap_code) {
                throw new \Exception('Supplier ' . ($invoice->supplier->name ?? ('#' . $invoice->supplier_id)) . ' does not have a SAP CardCode mapping.');
            }

            // Validate supplier exists in SAP
            $vendor = $this->resolveVendor($sapService, $invoice->supplier->sap_code);

            // Build payload using builder
            $payloadBuilder = new SapApInvoicePayloadBuilder($invoice);
            $payload = $payloadBuilder->build();

            // Create AP Invoice in SAP
            $response = $sapService->createApInvoice($payload);

            // Update invoice with SAP response
            DB::transaction(function () use ($invoice, $response, $payload) {
                $invoice->update([
                    'sap_status' => 'posted',
                    'sap_doc_num' => $response['DocNum'] ?? null,
                    'sap_error_message' => null,
                    'sap_last_attempted_at' => now(),
                ]);

                // Log success
                DB::table('sap_logs')->insert([
                    'invoice_id' => $invoice->id,
                    'action' => 'create_invoice',
                    'status' => 'success',
                    'request_payload' => json_encode($payload),
                    'response_payload' => json_encode($response),
                    'attempt_count' => $this->attempts(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            Log::channel('sap')->info('AP Invoice created successfully', [
                'invoice_id' => $invoice->id,
                'sap_doc_num' => $response['DocNum'] ?? null,
            ]);

        } catch (\Exception $e) {
            // Update invoice with error
            $invoice->update([
                'sap_status' => 'failed',
                'sap_error_message' => $e->getMessage(),
                'sap_last_attempted_at' => now(),
            ]);

            // Log error
            DB::table('sap_logs')->insert([
                'invoice_id' => $invoice->id,
                'action' => 'create_invoice',
                'status' => 'failed',
                'request_payload' => json_encode($payload ?? []),
                'response_payload' => null,
                'error_message' => $e->getMessage(),
                'attempt_count' => $this->attempts(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::channel('sap')->error('AP Invoice creation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Retry if not max attempts
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1]);
            } else {
                Log::channel('sap')->error('Max retries exceeded for invoice ' . $invoice->id);
            }

            throw $e;
        }
    }

    protected function resolveVendor(SapService $sapService, string $cardCode): array
    {
        try {
            $vendor = $sapService->getBusinessPartner($cardCode);
        } catch (RequestException $exception) {
            $parsedMessage = $this->parseSapErrorMessage($exception);
            throw new \Exception("SAP vendor {$cardCode} not found. {$parsedMessage}");
        }

        if (!$vendor || empty($vendor['CardCode'])) {
            throw new \Exception("SAP vendor {$cardCode} not found.");
        }

        $cardType = strtolower($vendor['CardType'] ?? '');
        if (!in_array($cardType, ['s', 'csupplier'], true)) {
            $cardTypeLabel = $vendor['CardType'] ?? 'unknown';
            throw new \Exception("SAP Business Partner {$cardCode} has CardType '{$cardTypeLabel}'. Expected supplier.");
        }

        return $vendor;
    }

    protected function parseSapErrorMessage(RequestException $exception): string
    {
        $response = $exception->getResponse();

        if (!$response) {
            return $exception->getMessage();
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if (isset($decoded['error']['message']['value'])) {
            return $decoded['error']['message']['value'];
        }

        return $body ?: $exception->getMessage();
    }
}
```

**Action Items**:
- [ ] Fix incomplete code (lines 42-44)
- [ ] Replace hardcoded payload with `SapApInvoicePayloadBuilder`
- [ ] Add proper error handling
- [ ] Test job with sample invoice
- [ ] Verify queue processing

---

### Task 1.7: Add Invoice Validation Method

**Files to Update**:
- `app/Models/Invoice.php`

**Add Method**:
```php
/**
 * Check if invoice can be synced to SAP
 */
public function canSyncToSap(): array
{
    $errors = [];

    // Check status
    if ($this->status !== 'sap') {
        $errors[] = 'Invoice status must be "sap" before syncing to SAP';
    }

    // Check SAP status
    if ($this->sap_status === 'pending') {
        $errors[] = 'Invoice is already pending SAP sync';
    }

    if ($this->sap_status === 'posted') {
        $errors[] = 'Invoice is already posted to SAP';
    }

    // Check supplier
    if (!$this->supplier) {
        $errors[] = 'Invoice must have a supplier';
    } elseif (!$this->supplier->sap_code) {
        $errors[] = 'Supplier does not have SAP code';
    }

    // Check amount
    if ($this->amount <= 0) {
        $errors[] = 'Invoice amount must be greater than 0';
    }

    // Check dates
    if (!$this->invoice_date) {
        $errors[] = 'Invoice date is required';
    }

    // Check currency
    if (!$this->currency) {
        $errors[] = 'Currency is required';
    }

    return $errors;
}
```

**Action Items**:
- [ ] Add method to Invoice model
- [ ] Test validation with various scenarios
- [ ] Update controller to use this method

---

### Task 1.8: Update InvoiceController::sapSync()

**Files to Update**:
- `app/Http/Controllers/InvoiceController.php`

**Update Method**:
```php
public function sapSync(Invoice $invoice)
{
    // Check if user can sync this invoice
    /** @var User $user */
    $user = Auth::user();
    if (!$user->hasAnyRole(['superadmin', 'admin', 'accounting', 'finance'])) {
        $locationCode = $user->department_location_code;
        if ($locationCode && $invoice->cur_loc !== $locationCode) {
            abort(403, 'You can only sync invoices from your department location.');
        }
    }

    // Validate invoice can be synced
    $validationErrors = $invoice->canSyncToSap();
    if (!empty($validationErrors)) {
        return back()->withErrors(['sap_sync' => implode(', ', $validationErrors)]);
    }

    // Check if already synced
    if ($invoice->sap_status === 'pending' || $invoice->sap_status === 'posted') {
        return back()->with('error', 'Invoice is already sent or pending in SAP.');
    }

    // Update status and queue job
    $invoice->update(['sap_status' => 'pending']);
    CreateSapApInvoiceJob::dispatch($invoice);

    return back()->with('success', 'Invoice queued for SAP posting.');
}
```

**Action Items**:
- [ ] Update `sapSync()` method
- [ ] Add validation error handling
- [ ] Test sync flow end-to-end
- [ ] Verify queue job is dispatched

---

### Task 1.9: Add Configuration File

**Files to Update**:
- `config/services.php`

**Add Configuration**:
```php
'sap' => [
    // ... existing SAP config ...
    
    'ap_invoice' => [
        'default_item_code' => env('SAP_AP_INVOICE_DEFAULT_ITEM_CODE', 'SERVICE'),
        'default_payment_terms' => env('SAP_AP_INVOICE_DEFAULT_PAYMENT_TERMS', 30),
        'tax_codes' => [
            'default' => env('SAP_AP_INVOICE_DEFAULT_TAX_CODE', 'EXEMPT'),
            'by_currency' => [
                'IDR' => env('SAP_AP_INVOICE_TAX_CODE_IDR', 'VAT11'),
                'USD' => env('SAP_AP_INVOICE_TAX_CODE_USD', 'EXEMPT'),
            ],
            'by_invoice_type' => [
                // Add mappings if needed
            ],
        ],
    ],
],
```

**Action Items**:
- [ ] Add configuration to `config/services.php`
- [ ] Add environment variables to `.env.example`
- [ ] Test configuration loading

---

### Task 1.10: Create Artisan Commands for Testing

**Files to Create**:
- `app/Console/Commands/SapSyncProjects.php`
- `app/Console/Commands/SapSyncDepartments.php`

**Commands** (for testing sync services):
```php
<?php

namespace App\Console\Commands;

use App\Services\SapProjectSyncService;
use Illuminate\Console\Command;

class SapSyncProjects extends Command
{
    protected $signature = 'sap:sync-projects';
    protected $description = 'Sync SAP Projects from SAP B1';

    public function handle(SapProjectSyncService $syncService)
    {
        $this->info('Starting SAP Projects sync...');
        
        $result = $syncService->syncProjects();
        
        if ($result['success']) {
            $this->info($result['message']);
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total', $result['stats']['total']],
                    ['Created', $result['stats']['created']],
                    ['Updated', $result['stats']['updated']],
                    ['Errors', $result['stats']['errors']],
                ]
            );
        } else {
            $this->error($result['message']);
        }
    }
}
```

**Action Items**:
- [ ] Create sync commands
- [ ] Test commands manually
- [ ] Document command usage

---

## Phase 1 Checklist

- [ ] Task 1.1: SAP Project model and migration created
- [ ] Task 1.2: SAP Department model and migration created
- [ ] Task 1.3: SAP Project sync service created
- [ ] Task 1.4: SAP Department sync service created
- [ ] Task 1.5: SAP AP Invoice payload builder created
- [ ] Task 1.6: CreateSapApInvoiceJob completed
- [ ] Task 1.7: Invoice validation method added
- [ ] Task 1.8: InvoiceController::sapSync() updated
- [ ] Task 1.9: Configuration file updated
- [ ] Task 1.10: Artisan commands created
- [ ] All migrations run successfully
- [ ] Sync services tested manually
- [ ] Payload builder tested with sample data
- [ ] Job processing tested end-to-end
- [ ] Error handling verified

---

## Phase 2: Field Mapping

**Duration**: Week 2  
**Goal**: Complete all field mappings and enhance payload

### Task 2.1: Enhance PO Number Mapping

**Files to Update**:
- `app/Services/SapApInvoicePayloadBuilder.php`

**Enhancement**: Ensure PO number is properly mapped to `Reference1`

**Action Items**:
- [ ] Verify PO number mapping
- [ ] Add validation for PO format
- [ ] Test with various PO formats

---

### Task 2.2: Add Invoice Number to NumAtCard Mapping

**Files to Update**:
- `app/Services/SapApInvoicePayloadBuilder.php`

**Enhancement**: Map `invoice_number` to `NumAtCard` (already done, verify)

**Action Items**:
- [ ] Verify mapping works correctly
- [ ] Test with special characters
- [ ] Handle null/empty values

---

### Task 2.3: Implement Tax Code Logic

**Files to Update**:
- `app/Services/SapApInvoicePayloadBuilder.php`
- `config/services.php`

**Enhancement**: Complete tax code determination logic

**Action Items**:
- [ ] Test tax code mapping by currency
- [ ] Test tax code mapping by invoice type
- [ ] Add fallback to default tax code
- [ ] Verify tax codes exist in SAP

---

### Task 2.4: Add Currency Validation

**Files to Create/Update**:
- `app/Services/SapService.php`
- `app/Services/SapApInvoicePayloadBuilder.php`

**Enhancement**: Validate currency exists in SAP before sync

**Action Items**:
- [ ] Add `validateCurrency()` method to SapService
- [ ] Add currency validation to payload builder
- [ ] Cache currency list for performance
- [ ] Test with invalid currencies

---

### Task 2.5: Create Project/Department Mapping Table (If Needed)

**Files to Create**:
- `database/migrations/YYYY_MM_DD_HHMMSS_create_invoice_sap_project_mapping_table.php`
- `database/migrations/YYYY_MM_DD_HHMMSS_create_invoice_sap_department_mapping_table.php`

**Purpose**: If invoices use DDS codes instead of SAP codes, create mapping tables

**Action Items**:
- [ ] Determine if mapping tables are needed
- [ ] Create migrations if needed
- [ ] Update payload builder to use mappings
- [ ] Create admin UI for managing mappings (Phase 3)

---

## Phase 2 Checklist

- [ ] PO number mapping verified
- [ ] Invoice number mapping verified
- [ ] Tax code logic implemented and tested
- [ ] Currency validation added
- [ ] Mapping tables created (if needed)
- [ ] All field mappings tested end-to-end

---

## Phase 3: UI & UX

**Duration**: Week 3  
**Goal**: Enhance user interface for SAP sync

### Task 3.1: Add SAP Status to Invoice Show Page

**Files to Update**:
- `resources/views/invoices/show.blade.php`

**Enhancement**: Display SAP status, document number, error messages

**Action Items**:
- [ ] Add SAP status badge
- [ ] Display SAP document number (`sap_doc_num`)
- [ ] Show last sync attempt time
- [ ] Display error message if failed
- [ ] Add "Send to SAP" button (conditional)
- [ ] Add "Retry SAP Sync" button (if failed)

---

### Task 3.2: Add SAP Status Column to Invoice Index

**Files to Update**:
- `app/Http/Controllers/InvoiceController.php` (data method)
- `resources/views/invoices/index.blade.php`

**Enhancement**: Add SAP status column with badge

**Action Items**:
- [ ] Add `sap_status` column to DataTable
- [ ] Add SAP status badge formatting
- [ ] Add filter by SAP status
- [ ] Test filtering functionality

---

### Task 3.3: Add Pre-Sync Validation Warnings

**Files to Update**:
- `resources/views/invoices/show.blade.php`
- `app/Http/Controllers/InvoiceController.php`

**Enhancement**: Show validation warnings before sync

**Action Items**:
- [ ] Display validation errors in UI
- [ ] Highlight missing required fields
- [ ] Show warnings for optional fields
- [ ] Prevent sync if critical errors exist

---

### Task 3.4: Add SAP Logs Display

**Files to Update**:
- `resources/views/invoices/show.blade.php`

**Enhancement**: Display SAP sync logs

**Action Items**:
- [ ] Query `sap_logs` table
- [ ] Display log entries in table
- [ ] Show request/response payloads (collapsible)
- [ ] Format timestamps and status

---

## Phase 3 Checklist

- [ ] SAP status displayed on show page
- [ ] SAP status column added to index
- [ ] Validation warnings implemented
- [ ] SAP logs displayed
- [ ] UI tested and polished

---

## Phase 4: Advanced Features

**Duration**: Future  
**Goal**: Advanced features and optimizations

### Task 4.1: Multi-Line Item Support

**Files to Update**:
- `app/Services/SapApInvoicePayloadBuilder.php`

**Enhancement**: Support multiple line items

**Action Items**:
- [ ] Design multi-line item structure
- [ ] Update payload builder
- [ ] Handle line-item project mapping
- [ ] Test with complex invoices

---

### Task 4.2: GRPO Line-Item Mapping

**Files to Create/Update**:
- `app/Services/SapService.php` (add GRPO query method)
- `app/Services/SapApInvoicePayloadBuilder.php`

**Enhancement**: Query SAP GRPO and map line items

**Action Items**:
- [ ] Add `getGrpoByDocNum()` to SapService
- [ ] Update payload builder to query GRPO
- [ ] Map GRPO line items to AP Invoice
- [ ] Handle GRPO not found scenarios

---

### Task 4.3: SAP Preview Page

**Files to Create**:
- `resources/views/invoices/sap_preview.blade.php`
- `app/Http/Controllers/InvoiceController.php` (add preview method)

**Enhancement**: Preview SAP payload before submission (similar to AR Invoice)

**Action Items**:
- [ ] Create preview route and method
- [ ] Build preview view
- [ ] Display payload data
- [ ] Add edit functionality (future)

---

### Task 4.4: Admin UI for SAP Projects/Departments

**Files to Create**:
- `app/Http/Controllers/Admin/SapProjectController.php`
- `app/Http/Controllers/Admin/SapDepartmentController.php`
- `resources/views/admin/sap_projects/index.blade.php`
- `resources/views/admin/sap_departments/index.blade.php`

**Enhancement**: Admin interface for managing SAP Projects and Departments

**Action Items**:
- [ ] Create controllers
- [ ] Create views with DataTables
- [ ] Add sync buttons
- [ ] Add routes and permissions

---

## Phase 4 Checklist

- [ ] Multi-line item support implemented
- [ ] GRPO integration completed
- [ ] SAP preview page created
- [ ] Admin UI for SAP models created

---

## Testing Strategy

### Unit Tests

**Files to Create**:
- `tests/Unit/Services/SapApInvoicePayloadBuilderTest.php`
- `tests/Unit/Models/InvoiceTest.php` (add `canSyncToSap` test)

**Test Cases**:
- Payload builder creates correct structure
- Validation catches all error cases
- Project/cost center mapping works
- Tax code determination logic

### Integration Tests

**Files to Create**:
- `tests/Feature/InvoiceSapSyncTest.php`

**Test Cases**:
- Full sync flow (controller → job → SAP)
- Error handling and retry logic
- Status updates correctly
- Logs created properly

### Manual Testing

**Test Scenarios**:
1. Sync successful invoice
2. Sync invoice with missing supplier SAP code
3. Sync invoice with invalid project code
4. Sync invoice with network error (retry)
5. Sync invoice with SAP validation error
6. View SAP status on invoice show page
7. Filter invoices by SAP status

---

## Deployment Checklist

### Pre-Deployment

- [ ] All migrations run successfully
- [ ] Configuration files updated
- [ ] Environment variables set
- [ ] Queue worker configured
- [ ] SAP credentials verified
- [ ] Unit tests passing
- [ ] Integration tests passing

### Deployment Steps

1. [ ] Backup database
2. [ ] Run migrations: `php artisan migrate`
3. [ ] Clear config cache: `php artisan config:clear`
4. [ ] Sync SAP Projects: `php artisan sap:sync-projects`
5. [ ] Sync SAP Departments: `php artisan sap:sync-departments`
6. [ ] Test sync with sample invoice
7. [ ] Monitor queue worker
8. [ ] Monitor SAP logs

### Post-Deployment

- [ ] Verify sync functionality
- [ ] Monitor error logs
- [ ] Check queue processing
- [ ] Verify SAP document creation
- [ ] User acceptance testing

---

## Support & Troubleshooting

### Common Issues

**Issue**: "Supplier does not have SAP code"
- **Solution**: Ensure supplier has `sap_code` field populated

**Issue**: "SAP Project not found"
- **Solution**: Sync SAP Projects first: `php artisan sap:sync-projects`

**Issue**: "Queue job not processing"
- **Solution**: Ensure queue worker is running: `php artisan queue:work`

**Issue**: "SAP session expired"
- **Solution**: Check SAP credentials and network connectivity

### Logs Location

- Application logs: `storage/logs/laravel.log`
- SAP channel logs: `storage/logs/sap.log` (if configured)
- SAP logs table: `sap_logs` table in database

---

## Next Steps After Phase 1

1. **Review Phase 1 implementation**
2. **Test with real SAP instance**
3. **Gather user feedback**
4. **Plan Phase 2 implementation**
5. **Document any issues or learnings**

---

**Last Updated**: 2025-01-27  
**Version**: 1.0  
**Status**: Ready for Implementation
