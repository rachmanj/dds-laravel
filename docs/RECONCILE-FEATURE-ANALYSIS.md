# Reconcile Feature Analysis

## Overview

The **Reconcile feature** is a data reconciliation system designed to match and compare external invoice data (from bank statements or vendor records) against the internal invoice system (IRR Support system). It serves as a financial reconciliation tool that helps identify discrepancies between external and internal records.

## Core Functionality

### 1. Data Upload & Import

**Purpose**: Users can upload Excel files containing invoice numbers from external sources (likely bank statements or vendor records)

**Process**:

-   Upload Excel file via modal interface
-   System imports invoice numbers using `ReconcileDetailImport` class
-   Data is temporarily flagged with `TEMP{user_id}` for user isolation
-   After upload, the flag is cleared and vendor_id is assigned

**Technical Implementation**:

```php
// Upload validation
$this->validate($request, [
    'file_upload' => 'required|mimes:xls,xlsx'
]);

// Import process
Excel::import(new ReconcileDetailImport, public_path('/file_upload/' . $nama_file));

// Update flag field
$temp_flag = 'TEMP' . auth()->user()->id;
ReconcileDetail::where('flag', $temp_flag)->update([
    'vendor_id' => $request->vendor_id,
    'flag' => null
]);
```

### 2. Data Matching & Comparison

**Purpose**: Cross-reference uploaded invoice numbers with internal invoice database

**Process**:

-   System searches for matching invoices in `irr5_invoice` table using LIKE pattern matching
-   Retrieves related data: vendor info, receive dates, amounts, SPI numbers, SPI dates
-   Displays both external invoice numbers and internal invoice details side-by-side

**Technical Implementation**:

```php
public function getInvoiceIrr($invoice_no)
{
    return Invoice::where('inv_no', 'LIKE', '%' . $invoice_no . '%')->first();
}
```

### 3. Data Display & Management

The system shows a comprehensive comparison table with:

| Column         | Description                         | Source           |
| -------------- | ----------------------------------- | ---------------- |
| **InvoiceNo**  | External invoice number             | Uploaded data    |
| **InvoiceIRR** | Internal invoice number             | IRR system       |
| **VendorN**    | Vendor name                         | Internal invoice |
| **ReceiveD**   | Invoice receive date                | Internal invoice |
| **Amount**     | Invoice amount (formatted)          | Internal invoice |
| **SPINo**      | SPI (Surat Perintah Invoice) number | Internal invoice |
| **SPIDate**    | SPI date                            | Internal invoice |

### 4. User Isolation & Security

-   Each user can only see their own uploaded data (`user_id` filtering)
-   Temporary flag system prevents data conflicts during upload
-   Users can delete only their own data

**Security Implementation**:

```php
// User-specific data filtering
$reconciles = ReconcileDetail::orderBy('created_at', 'desc')
    ->where('user_id', auth()->user()->id)
    ->get();

// Delete only user's own data
public function delete_mine()
{
    $reconciles = ReconcileDetail::where('user_id', auth()->user()->id);
    $reconciles->delete();
}
```

### 5. Export Functionality

-   Export reconciled data to Excel format
-   Uses `ReconcileExport` class with custom view template
-   Maintains same data structure as display table

## Business Purpose

This feature serves several critical business functions:

1. **Financial Reconciliation**: Match bank statement invoices with internal records
2. **Discrepancy Identification**: Find invoices that exist externally but not internally (or vice versa)
3. **Audit Trail**: Track which invoices have been processed and verified
4. **Data Validation**: Ensure internal records match external financial documents
5. **Compliance**: Maintain accurate financial records for auditing purposes

## Technical Architecture

### Models

#### ReconcileDetail Model

```php
class ReconcileDetail extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_no', 'inv_no');
    }
}
```

**Database Schema**:

```sql
CREATE TABLE reconcile_details (
    id BIGINT PRIMARY KEY,
    invoice_no VARCHAR(255) NULL,
    vendor_id BIGINT NULL,
    invoice_date DATE NULL,
    user_id BIGINT NULL,
    flag VARCHAR(20) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Controllers

#### ReportsReconcileController

-   `index()`: Display reconcile data page
-   `upload()`: Handle Excel file upload and import
-   `delete_mine()`: Delete user's own data
-   `export()`: Export data to Excel
-   `data()`: AJAX endpoint for DataTables
-   `getInvoiceIrr()`: Helper method to find matching invoices
-   `getReconcileData()`: Prepare data for display

### Import/Export Classes

#### ReconcileDetailImport

```php
class ReconcileDetailImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $temp_flag = 'TEMP' . auth()->user()->id;

        return new ReconcileDetail([
            'invoice_no' => $row['invoice_no'],
            'user_id' => auth()->user()->id,
            'flag' => $temp_flag
        ]);
    }
}
```

#### ReconcileExport

```php
class ReconcileExport implements FromView
{
    public function view(): View
    {
        return view('reports.reconcile.export', compact('reconciles'));
    }
}
```

### Views

1. **index.blade.php**: Main display page with table and upload modal
2. **export.blade.php**: Excel export template
3. **index_old.blade.php**: Previous version (DataTables implementation)

### Routes

```php
Route::prefix('reconcile')->name('reconcile.')->group(function () {
    Route::get('/', [ReportsReconcileController::class, 'index'])->name('index');
    Route::get('/data', [ReportsReconcileController::class, 'data'])->name('data');
    Route::post('/upload', [ReportsReconcileController::class, 'upload'])->name('upload');
    Route::get('/delete-mine', [ReportsReconcileController::class, 'delete_mine'])->name('delete_mine');
    Route::get('/export', [ReportsReconcileController::class, 'export'])->name('export');
});
```

## User Workflow

1. **Access**: Navigate to Reports → Reconciliation
2. **Upload**: Click "Upload" button and select Excel file with invoice numbers
3. **Process**: System automatically imports and matches data
4. **Review**: Review matched/unmatched data in table format
5. **Export**: Click "Export" to download Excel report
6. **Cleanup**: Click "Delete All" to remove user's data when done

## Key Features

### Data Matching Logic

-   Uses LIKE pattern matching for flexible invoice number matching
-   Handles partial matches and variations in invoice number formats
-   Retrieves comprehensive invoice details including vendor, dates, and amounts

### User Experience

-   Clean, intuitive interface with AdminLTE styling
-   Modal-based upload process
-   Real-time data display
-   Export functionality for further analysis

### Security & Data Integrity

-   User-specific data isolation
-   Temporary flag system during upload process
-   Safe deletion (only user's own data)
-   Input validation for file uploads

## Integration Points

-   **Invoice System**: Primary integration with `irr5_invoice` table
-   **Vendor Management**: Links to vendor information
-   **SPI System**: Integrates with Surat Perintah Invoice data
-   **User Management**: User-specific data handling
-   **File Management**: Excel import/export functionality

## Potential Improvements

1. **Enhanced Matching**: Implement fuzzy matching for better invoice number matching
2. **Batch Processing**: Support for larger file uploads
3. **Audit Logging**: Track all reconciliation activities
4. **Notification System**: Alert users of matching results
5. **Advanced Filtering**: Add search and filter capabilities
6. **Data Validation**: Enhanced validation for uploaded data
7. **Performance Optimization**: Optimize queries for large datasets

## Conclusion

The Reconcile feature is a critical financial reconciliation tool that ensures data integrity between external financial sources and the internal IRR Support system. It provides a comprehensive solution for matching invoice data, identifying discrepancies, and maintaining accurate financial records for auditing and compliance purposes.

The system is well-architected with proper separation of concerns, user isolation, and security measures. It effectively serves its purpose as a financial reconciliation tool while maintaining data integrity and user experience standards.
