# Dashboard Optimization Implementation Examples

This document provides code examples for implementing the dashboard performance optimizations.

## Example 1: Optimized Workflow Metrics Query

### Before (N+1 Queries - SLOW)

```php
// app/Http/Controllers/DashboardController.php
private function getWorkflowMetrics($user, $userLocationCode)
{
    // Loading ALL documents into memory
    $overdueInvoicesQuery = Invoice::query();
    $overdueAdditionalQuery = AdditionalDocument::query();
    
    if (!$isAdmin && $userLocationCode) {
        $overdueInvoicesQuery->where('cur_loc', $userLocationCode);
        $overdueAdditionalQuery->where('cur_loc', $userLocationCode);
    }
    
    // ⚠️ PROBLEM: Loading all records
    $allInvoices = $overdueInvoicesQuery->get();
    $allAdditionalDocs = $overdueAdditionalQuery->get();
    
    $overdueCount = 0;
    
    // ⚠️ PROBLEM: N+1 queries - accessor triggers DB query per document
    foreach ($allInvoices as $invoice) {
        if ($invoice->days_in_current_location > 14) { // DB query here!
            $overdueCount++;
        }
    }
    
    foreach ($allAdditionalDocs as $doc) {
        if ($doc->days_in_current_location > 14) { // DB query here!
            $overdueCount++;
        }
    }
    
    return ['overdue_documents' => $overdueCount];
}
```

### After (Single Query - FAST)

```php
// app/Services/DashboardMetricsService.php
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    public function getOverdueDocumentsCount($userLocationCode, $isAdmin)
    {
        // Calculate arrival_date using SQL subquery (same logic as accessor)
        $arrivalDateSubquery = "
            COALESCE(
                (SELECT received_at FROM distributions 
                 INNER JOIN distribution_documents ON distributions.id = distribution_documents.distribution_id
                 WHERE distribution_documents.document_type = 'App\\\\Models\\\\Invoice'
                   AND distribution_documents.document_id = invoices.id
                   AND distribution_documents.receiver_verification_status = 'verified'
                   AND distributions.received_at IS NOT NULL
                 ORDER BY distributions.received_at DESC LIMIT 1),
                COALESCE(invoices.receive_date, invoices.created_at)
            )
        ";
        
        $additionalDocArrivalDateSubquery = "
            COALESCE(
                (SELECT received_at FROM distributions 
                 INNER JOIN distribution_documents ON distributions.id = distribution_documents.distribution_id
                 WHERE distribution_documents.document_type = 'App\\\\Models\\\\AdditionalDocument'
                   AND distribution_documents.document_id = additional_documents.id
                   AND distribution_documents.receiver_verification_status = 'verified'
                   AND distributions.received_at IS NOT NULL
                 ORDER BY distributions.received_at DESC LIMIT 1),
                COALESCE(additional_documents.receive_date, additional_documents.created_at)
            )
        ";
        
        // Single query for invoices
        $invoiceOverdue = Invoice::selectRaw("COUNT(*) as count")
            ->whereRaw("DATEDIFF(NOW(), {$arrivalDateSubquery}) > 14")
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->value('count') ?? 0;
        
        // Single query for additional documents
        $additionalOverdue = AdditionalDocument::selectRaw("COUNT(*) as count")
            ->whereRaw("DATEDIFF(NOW(), {$additionalDocArrivalDateSubquery}) > 14")
            ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
                $q->where('cur_loc', $userLocationCode);
            })
            ->value('count') ?? 0;
        
        return $invoiceOverdue + $additionalOverdue;
    }
}
```

## Example 2: Optimized Document Age Breakdown

### Before (N+1 Queries - SLOW)

```php
private function getDocumentAgeBreakdown($user, $userLocationCode)
{
    $invoices = Invoice::where('cur_loc', $userLocationCode)->get();
    $additionalDocs = AdditionalDocument::where('cur_loc', $userLocationCode)->get();
    
    // ⚠️ PROBLEM: Looping and calling accessor (N+1 queries)
    $invoiceAgeBreakdown = $this->categorizeDocumentsByDepartmentSpecificAge($invoices);
    $additionalAgeBreakdown = $this->categorizeDocumentsByDepartmentSpecificAge($additionalDocs);
    
    return [
        '0_7_days' => $invoiceAgeBreakdown['0_7_days'] + $additionalAgeBreakdown['0_7_days'],
        '8_14_days' => $invoiceAgeBreakdown['8_14_days'] + $additionalAgeBreakdown['8_14_days'],
        '15_plus_days' => $invoiceAgeBreakdown['15_plus_days'] + $additionalAgeBreakdown['15_plus_days'],
    ];
}

private function categorizeDocumentsByDepartmentSpecificAge($documents)
{
    $breakdown = ['0_7_days' => 0, '8_14_days' => 0, '15_plus_days' => 0];
    
    foreach ($documents as $document) {
        // ⚠️ PROBLEM: Accessor triggers DB query per document
        $ageCategory = $document->current_location_age_category; // DB query!
        
        switch ($ageCategory) {
            case '0-7_days':
                $breakdown['0_7_days']++;
                break;
            // ... more cases
        }
    }
    
    return $breakdown;
}
```

### After (Single Query - FAST)

```php
// app/Services/DashboardMetricsService.php
public function getDocumentAgeBreakdown($userLocationCode, $isAdmin)
{
    $arrivalDateSubquery = $this->getArrivalDateSubquery('Invoice');
    $additionalArrivalDateSubquery = $this->getArrivalDateSubquery('AdditionalDocument');
    
    // Single query for invoices with age categories
    $invoiceBreakdown = Invoice::selectRaw("
        SUM(CASE WHEN DATEDIFF(NOW(), {$arrivalDateSubquery}) <= 7 THEN 1 ELSE 0 END) as days_0_7,
        SUM(CASE WHEN DATEDIFF(NOW(), {$arrivalDateSubquery}) BETWEEN 8 AND 14 THEN 1 ELSE 0 END) as days_8_14,
        SUM(CASE WHEN DATEDIFF(NOW(), {$arrivalDateSubquery}) > 14 THEN 1 ELSE 0 END) as days_15_plus,
        COUNT(*) as total
    ")
    ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
        $q->where('cur_loc', $userLocationCode);
    })
    ->first();
    
    // Single query for additional documents
    $additionalBreakdown = AdditionalDocument::selectRaw("
        SUM(CASE WHEN DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) <= 7 THEN 1 ELSE 0 END) as days_0_7,
        SUM(CASE WHEN DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) BETWEEN 8 AND 14 THEN 1 ELSE 0 END) as days_8_14,
        SUM(CASE WHEN DATEDIFF(NOW(), {$additionalArrivalDateSubquery}) > 14 THEN 1 ELSE 0 END) as days_15_plus,
        COUNT(*) as total
    ")
    ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
        $q->where('cur_loc', $userLocationCode);
    })
    ->first();
    
    return [
        '0_7_days' => ($invoiceBreakdown->days_0_7 ?? 0) + ($additionalBreakdown->days_0_7 ?? 0),
        '8_14_days' => ($invoiceBreakdown->days_8_14 ?? 0) + ($additionalBreakdown->days_8_14 ?? 0),
        '15_plus_days' => ($invoiceBreakdown->days_15_plus ?? 0) + ($additionalBreakdown->days_15_plus ?? 0),
        'total_documents' => ($invoiceBreakdown->total ?? 0) + ($additionalBreakdown->total ?? 0),
        'invoices_count' => $invoiceBreakdown->total ?? 0,
        'additional_docs_count' => $additionalBreakdown->total ?? 0,
    ];
}

private function getArrivalDateSubquery($modelType)
{
    $tableName = $modelType === 'Invoice' ? 'invoices' : 'additional_documents';
    $documentType = $modelType === 'Invoice' 
        ? 'App\\\\Models\\\\Invoice' 
        : 'App\\\\Models\\\\AdditionalDocument';
    
    return "
        COALESCE(
            (SELECT received_at FROM distributions 
             INNER JOIN distribution_documents ON distributions.id = distribution_documents.distribution_id
             WHERE distribution_documents.document_type = '{$documentType}'
               AND distribution_documents.document_id = {$tableName}.id
               AND distribution_documents.receiver_verification_status = 'verified'
               AND distributions.received_at IS NOT NULL
             ORDER BY distributions.received_at DESC LIMIT 1),
            COALESCE({$tableName}.receive_date, {$tableName}.created_at)
        )
    ";
}
```

## Example 3: Combined Invoice + AdditionalDocument Queries

### Before (2 Separate Queries)

```php
$inTransitQuery = Invoice::where('distribution_status', 'in_transit');
$inTransitQuery2 = AdditionalDocument::where('distribution_status', 'in_transit');

if (!$isAdmin && $userLocationCode) {
    $inTransitQuery->where('cur_loc', $userLocationCode);
    $inTransitQuery2->where('cur_loc', $userLocationCode);
}

$inTransitCount = $inTransitQuery->count() + $inTransitQuery2->count();
```

### After (Single UNION Query)

```php
// app/Services/DashboardMetricsService.php
public function getInTransitCount($userLocationCode, $isAdmin)
{
    $invoiceQuery = Invoice::where('distribution_status', 'in_transit')
        ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
            $q->where('cur_loc', $userLocationCode);
        })
        ->selectRaw("'invoice' as type, id");
    
    $additionalQuery = AdditionalDocument::where('distribution_status', 'in_transit')
        ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
            $q->where('cur_loc', $userLocationCode);
        })
        ->selectRaw("'additional' as type, id");
    
    return DB::table(DB::raw("({$invoiceQuery->toSql()} UNION ALL {$additionalQuery->toSql()}) as combined"))
        ->mergeBindings($invoiceQuery->getQuery())
        ->mergeBindings($additionalQuery->getQuery())
        ->count();
}
```

## Example 4: Optimized SAP Metrics Query

### Before (66+ Queries - One Per Department)

```php
foreach ($departments as $department) {
    $invoicesQuery = Invoice::where('cur_loc', $department->location_code);
    
    $totalInvoices = $invoicesQuery->count(); // Query 1
    $invoicesWithoutSap = $invoicesQuery->clone()->whereNull('sap_doc')->count(); // Query 2
    $invoicesWithSap = $invoicesQuery->clone()->whereNotNull('sap_doc')->count(); // Query 3
    
    $sapMetrics[] = [
        'department_name' => $department->name,
        'total_invoices' => $totalInvoices,
        'without_sap_doc' => $invoicesWithoutSap,
        'with_sap_doc' => $invoicesWithSap,
    ];
}
```

### After (Single Aggregated Query)

```php
// app/Services/DashboardMetricsService.php
public function getSapDocumentMetrics($userLocationCode, $isAdmin)
{
    $departmentsQuery = Department::whereHas('invoices')
        ->when(!$isAdmin && $userLocationCode, function($q) use ($userLocationCode) {
            $q->where('location_code', $userLocationCode);
        });
    
    $locationCodes = $departmentsQuery->pluck('location_code');
    
    // Single query for all departments
    $sapMetrics = Invoice::select('cur_loc')
        ->selectRaw('COUNT(*) as total_invoices')
        ->selectRaw('SUM(CASE WHEN sap_doc IS NULL THEN 1 ELSE 0 END) as without_sap_doc')
        ->selectRaw('SUM(CASE WHEN sap_doc IS NOT NULL THEN 1 ELSE 0 END) as with_sap_doc')
        ->whereIn('cur_loc', $locationCodes)
        ->groupBy('cur_loc')
        ->get()
        ->keyBy('cur_loc');
    
    // Join with department names
    $departments = $departmentsQuery->get()->keyBy('location_code');
    
    return $sapMetrics->map(function($metric) use ($departments) {
        $department = $departments->get($metric->cur_loc);
        
        return [
            'department_name' => $department->name ?? 'Unknown',
            'location_code' => $metric->cur_loc,
            'total_invoices' => $metric->total_invoices,
            'without_sap_doc' => $metric->without_sap_doc,
            'with_sap_doc' => $metric->with_sap_doc,
            'completion_percentage' => $metric->total_invoices > 0 
                ? round(($metric->with_sap_doc / $metric->total_invoices) * 100, 1) 
                : 0
        ];
    })->values()->toArray();
}
```

## Example 5: Caching Implementation

### Service Class with Caching

```php
// app/Services/DashboardMetricsService.php
use Illuminate\Support\Facades\Cache;

class DashboardMetricsService
{
    public function getWorkflowMetrics($user, $userLocationCode)
    {
        $cacheKey = "dashboard.workflow_metrics.{$user->id}.{$userLocationCode}";
        
        return Cache::remember($cacheKey, 300, function() use ($user, $userLocationCode) {
            $isAdmin = $user->hasAnyRole(['admin', 'superadmin']);
            
            return [
                'pending_distributions' => $this->getPendingDistributionsCount($user, $userLocationCode, $isAdmin),
                'in_transit_documents' => $this->getInTransitCount($userLocationCode, $isAdmin),
                'overdue_documents' => $this->getOverdueDocumentsCount($userLocationCode, $isAdmin),
                'unaccounted_documents' => $this->getUnaccountedCount($userLocationCode, $isAdmin),
            ];
        });
    }
    
    public function invalidateCache($userId, $userLocationCode)
    {
        Cache::forget("dashboard.workflow_metrics.{$userId}.{$userLocationCode}");
        Cache::forget("dashboard.age_breakdown.{$userId}.{$userLocationCode}");
        Cache::forget("dashboard.sap_metrics.{$userId}.{$userLocationCode}");
        Cache::forget("dashboard.aging_alerts.{$userId}.{$userLocationCode}");
    }
}
```

### Cache Invalidation Listener

```php
// app/Listeners/InvalidateDashboardCache.php
use App\Events\DistributionCreated;
use App\Events\DistributionStatusChanged;
use App\Services\DashboardMetricsService;

class InvalidateDashboardCache
{
    public function handle($event)
    {
        $service = app(DashboardMetricsService::class);
        
        // Invalidate cache for all users (or specific users if event provides user info)
        // For simplicity, invalidate all caches matching pattern
        Cache::flush(); // Or use more targeted invalidation
        
        // Or invalidate per user if event provides user context
        if (isset($event->user)) {
            $service->invalidateCache($event->user->id, $event->user->department_location_code);
        }
    }
}
```

## Example 6: Welcome Page Implementation

### Welcome Controller

```php
// app/Http/Controllers/WelcomeController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Only load minimal, cached data
        $quickStats = [
            'pending_distributions' => Cache::remember(
                "welcome.pending.{$user->id}", 
                60, 
                fn() => Distribution::where('status', 'sent')
                    ->where('destination_department_id', $user->department_id)
                    ->count()
            ),
        ];
        
        return view('welcome', compact('quickStats'));
    }
}
```

### Welcome View (Lightweight)

```blade
{{-- resources/views/welcome.blade.php --}}
@extends('layouts.main')

@section('title_page', 'Welcome')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <h1 class="display-4">Welcome back, {{ auth()->user()->name }}!</h1>
                    <p class="lead">Your dashboard is loading...</p>
                    
                    <div class="mt-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                            Go to Dashboard
                        </a>
                        <a href="{{ route('additional-documents.index') }}" class="btn btn-outline-primary btn-lg ml-2">
                            View Documents
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load dashboard in background
setTimeout(function() {
    window.location.href = '{{ route('dashboard') }}';
}, 2000); // Redirect after 2 seconds, or let user click button
</script>
@endsection
```

### Update Login Redirect

```php
// app/Http/Controllers/Auth/LoginController.php
public function login(Request $request)
{
    // ... validation and authentication ...
    
    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        
        // Redirect to welcome page instead of dashboard
        return redirect()->intended('/welcome');
    }
    
    // ... error handling ...
}
```

### Add Welcome Route

```php
// routes/web.php
Route::middleware(['auth', 'active.user'])->group(function () {
    Route::get('/welcome', [WelcomeController::class, 'index'])->name('welcome');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // ... other routes ...
});
```

## Example 7: Database Indexes Migration

```php
// database/migrations/YYYY_MM_DD_add_dashboard_indexes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Indexes for distribution_status filtering
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('distribution_status', 'idx_invoices_distribution_status');
            $table->index('cur_loc', 'idx_invoices_cur_loc');
            $table->index(['cur_loc', 'sap_doc'], 'idx_invoices_cur_loc_sap_doc');
        });
        
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->index('distribution_status', 'idx_additional_docs_distribution_status');
            $table->index('cur_loc', 'idx_additional_docs_cur_loc');
        });
        
        // Indexes for distribution queries
        Schema::table('distribution_documents', function (Blueprint $table) {
            $table->index(['document_type', 'document_id'], 'idx_dist_docs_type_id');
            $table->index('receiver_verification_status', 'idx_dist_docs_status');
        });
        
        Schema::table('distributions', function (Blueprint $table) {
            $table->index('received_at', 'idx_distributions_received_at');
            $table->index(['status', 'destination_department_id'], 'idx_dist_status_dest_dept');
        });
    }
    
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_distribution_status');
            $table->dropIndex('idx_invoices_cur_loc');
            $table->dropIndex('idx_invoices_cur_loc_sap_doc');
        });
        
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->dropIndex('idx_additional_docs_distribution_status');
            $table->dropIndex('idx_additional_docs_cur_loc');
        });
        
        Schema::table('distribution_documents', function (Blueprint $table) {
            $table->dropIndex('idx_dist_docs_type_id');
            $table->dropIndex('idx_dist_docs_status');
        });
        
        Schema::table('distributions', function (Blueprint $table) {
            $table->dropIndex('idx_distributions_received_at');
            $table->dropIndex('idx_dist_status_dest_dept');
        });
    }
};
```

## Summary

These examples demonstrate how to:
1. Replace N+1 queries with single aggregated database queries
2. Use SQL subqueries to replicate accessor logic at database level
3. Combine multiple queries into single UNION queries
4. Implement caching to reduce database load
5. Create lightweight welcome page for instant post-login response
6. Add database indexes for query optimization

**Key Principle**: Move calculations from PHP loops to database queries whenever possible.
