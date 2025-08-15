<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Distribution;
use App\Models\DistributionType;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\AdditionalDocument;
use App\Models\DistributionDocument;
use App\Models\DistributionHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DistributionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $user = Auth::user();
        $query = Distribution::with(['type', 'originDepartment', 'destinationDepartment', 'creator']);

        // Filter by department access based on user role
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
            if ($user->department) {
                // Regular users only see distributions where they are the destination department
                // and the distribution is in "sent" status (ready to receive)
                $query->where('destination_department_id', $user->department->id)
                    ->where('status', 'sent');
            }
        }

        $distributions = $query->latest()->paginate(15);
        $distributionTypes = DistributionType::active()->get();
        $departments = Department::orderBy('name')->get();

        return view('distributions.index', compact('distributions', 'distributionTypes', 'departments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View|RedirectResponse
    {
        $user = Auth::user();

        // Check if user has a department assigned
        if (!$user->department) {
            return back()->with('error', 'User must have a department assigned to create distributions');
        }

        $distributionTypes = DistributionType::active()->get();
        $departments = Department::orderBy('name')->get();
        $invoices = Invoice::where('cur_loc', $user->department->location_code)
            ->availableForDistribution()
            ->get();
        $additionalDocuments = AdditionalDocument::where('cur_loc', $user->department->location_code)
            ->availableForDistribution()
            ->get();

        return view('distributions.create', compact(
            'distributionTypes',
            'departments',
            'invoices',
            'additionalDocuments'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // Debug: Log the incoming request data
        Log::info('Distribution creation request data:', $request->all());

        $validator = Validator::make($request->all(), [
            'type_id' => 'required|exists:distribution_types,id',
            'destination_department_id' => 'required|exists:departments,id',
            'document_type' => 'required|in:invoice,additional_document',
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'required|integer',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // Check if user has a department assigned
            if (!$user->department) {
                throw new \Exception('User must have a department assigned to create distributions');
            }

            $currentYear = Carbon::now()->year;
            $originDepartmentId = $user->department->id;

            // Generate sequence number for this department/year combination
            $sequence = Distribution::getNextSequence($currentYear, $originDepartmentId);

            Log::info("Generated sequence number: {$sequence} for year: {$currentYear}, department: {$originDepartmentId}");

            // Validate sequence number is within reasonable bounds
            if ($sequence > 999999) {
                throw new \Exception("Sequence number {$sequence} exceeds maximum allowed value (999999)");
            }

            // Generate distribution number with retry logic for sequence conflicts
            $distributionNumber = $this->generateUniqueDistributionNumber(
                $currentYear,
                $user->department->location_code,
                $sequence,
                $originDepartmentId
            );

            Log::info("Generated distribution number: {$distributionNumber}");

            // Create distribution with retry logic for sequence conflicts
            $maxRetries = 5;
            $attempts = 0;
            $distribution = null;

            do {
                try {
                    $distribution = Distribution::create([
                        'distribution_number' => $distributionNumber,
                        'type_id' => $request->type_id,
                        'origin_department_id' => $originDepartmentId,
                        'destination_department_id' => $request->destination_department_id,
                        'document_type' => $request->document_type,
                        'created_by' => $user->id,
                        'status' => 'draft',
                        'notes' => $request->notes,
                        'year' => $currentYear,
                        'sequence' => $sequence
                    ]);
                    break; // Success, exit the loop
                } catch (\Illuminate\Database\QueryException $e) {
                    // Check if it's a duplicate key error
                    if ($e->getCode() == 23000 && strpos($e->getMessage(), 'distributions_year_dept_seq_unique') !== false) {
                        $attempts++;
                        if ($attempts >= $maxRetries) {
                            throw new \Exception("Unable to create distribution after {$maxRetries} attempts due to sequence conflicts");
                        }

                        // Get a fresh sequence number and try again
                        $sequence = Distribution::getNextSequence($currentYear, $originDepartmentId);
                        $distributionNumber = $this->generateDistributionNumber($currentYear, $user->department->location_code, $sequence);

                        Log::warning("Sequence conflict detected, retrying with new sequence: {$sequence}");
                    } else {
                        // Re-throw if it's not a duplicate key error
                        throw $e;
                    }
                }
            } while ($attempts < $maxRetries);

            // Attach documents
            $this->attachDocuments($distribution, $request->document_type, $request->document_ids);

            // If distributing invoices, also automatically include any attached additional documents
            if ($request->document_type === 'invoice') {
                $this->attachInvoiceAdditionalDocuments($distribution, $request->document_ids);
            }

            // Log creation
            DistributionHistory::logWorkflowTransition(
                $distribution,
                $user,
                'created',
                'draft',
                'Distribution created'
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Distribution created successfully',
                    'distribution' => $distribution->load(['type', 'originDepartment', 'destinationDepartment'])
                ]);
            }

            return redirect()->route('distributions.show', $distribution)
                ->with('success', 'Distribution created successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create distribution: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to create distribution: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Distribution $distribution): View
    {
        $distribution->load([
            'type',
            'originDepartment',
            'destinationDepartment',
            'creator',
            'senderVerifier',
            'receiverVerifier',
            'documents.document',
            'histories.user'
        ]);

        return view('distributions.show', compact('distribution'));
    }

    /**
     * Display the print view for Transmittal Advice.
     */
    public function print(Distribution $distribution): View
    {
        // Load all necessary relationships for printing
        $distribution->load([
            'type',
            'originDepartment',
            'destinationDepartment',
            'creator',
            'senderVerifier',
            'receiverVerifier',
            'documents.document',
            'histories.user'
        ]);

        // Load additional documents for invoices
        foreach ($distribution->documents as $distributionDocument) {
            if ($distributionDocument->document_type === Invoice::class) {
                $invoice = $distributionDocument->document;
                if ($invoice) {
                    $invoice->load('additionalDocuments.doctype');
                }
            }
        }

        return view('distributions.print', compact('distribution'));
    }

    /**
     * Display numbering statistics view
     */
    public function numberingStatsView(): View
    {
        $departments = Department::orderBy('name')->get();

        // Get overall statistics
        $stats = [
            'total_distributions' => Distribution::count(),
            'current_year_total' => Distribution::where('year', Carbon::now()->year)->count(),
            'departments_with_distributions' => Distribution::distinct('origin_department_id')->count(),
            'highest_sequence' => Distribution::max('sequence') ?? 0
        ];

        // Get yearly statistics
        $yearlyStats = [];
        for ($year = Carbon::now()->year; $year >= Carbon::now()->year - 5; $year--) {
            $yearData = Distribution::where('year', $year)
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(DISTINCT origin_department_id) as departments,
                    MAX(sequence) as highest_sequence
                ')
                ->first();

            $yearlyStats[$year] = [
                'total' => $yearData->total ?? 0,
                'departments' => $yearData->departments ?? 0,
                'highest_sequence' => $yearData->highest_sequence ?? 0
            ];
        }

        // Get department statistics
        $departmentStats = [];
        foreach ($departments as $dept) {
            $deptData = Distribution::where('origin_department_id', $dept->id)
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN year = ? THEN 1 END) as current_year
                ', [Carbon::now()->year])
                ->first();

            $departmentStats[] = [
                'name' => $dept->name,
                'location_code' => $dept->location_code,
                'total' => $deptData->total ?? 0,
                'current_year' => $deptData->current_year ?? 0
            ];
        }

        // Get sequence analysis
        $sequenceAnalysis = [];
        foreach ($departments as $dept) {
            for ($year = Carbon::now()->year; $year >= Carbon::now()->year - 2; $year--) {
                $analysis = Distribution::where('year', $year)
                    ->where('origin_department_id', $dept->id)
                    ->selectRaw('
                        MAX(sequence) as current_sequence,
                        MAX(created_at) as last_used
                    ')
                    ->first();

                $sequenceAnalysis[] = [
                    'year' => $year,
                    'department_name' => $dept->name,
                    'location_code' => $dept->location_code,
                    'current_sequence' => $analysis->current_sequence ?? 0,
                    'next_sequence' => ($analysis->current_sequence ?? 0) + 1,
                    'last_used' => $analysis->last_used ? Carbon::parse($analysis->last_used) : null
                ];
            }
        }

        return view('distributions.numbering-stats', compact(
            'departments',
            'stats',
            'yearlyStats',
            'departmentStats',
            'sequenceAnalysis'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Distribution $distribution): View|RedirectResponse
    {
        // Only allow editing in draft status
        if ($distribution->status !== 'draft') {
            return back()->with('error', 'Only draft distributions can be edited');
        }

        $distributionTypes = DistributionType::active()->get();
        $departments = Department::orderBy('name')->get();

        return view('distributions.edit', compact('distribution', 'distributionTypes', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Distribution $distribution): JsonResponse|RedirectResponse
    {
        // Only allow updating in draft status
        if ($distribution->status !== 'draft') {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft distributions can be updated'
                ], 422);
            }
            return back()->with('error', 'Only draft distributions can be updated');
        }

        $validator = Validator::make($request->all(), [
            'type_id' => 'required|exists:distribution_types,id',
            'destination_department_id' => 'required|exists:departments,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $oldStatus = $distribution->status;

            $distribution->update([
                'type_id' => $request->type_id,
                'destination_department_id' => $request->destination_department_id,
                'notes' => $request->notes
            ]);

            // Log update
            DistributionHistory::logWorkflowTransition(
                $distribution,
                Auth::user(),
                $oldStatus,
                $distribution->status,
                'Distribution updated'
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Distribution updated successfully',
                    'distribution' => $distribution->load(['type', 'originDepartment', 'destinationDepartment'])
                ]);
            }

            return redirect()->route('distributions.show', $distribution)
                ->with('success', 'Distribution updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update distribution: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to update distribution: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Distribution $distribution): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        // Check if user has permission to delete this distribution
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
            // Regular users can only delete draft distributions they created
            if ($distribution->status !== 'draft' || $distribution->created_by !== $user->id) {
                if (request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to delete this distribution'
                    ], 403);
                }
                return back()->with('error', 'You do not have permission to delete this distribution');
            }
        } else {
            // Admin users can delete any distribution regardless of status
            // but only draft distributions can be deleted (business rule)
            if ($distribution->status !== 'draft') {
                if (request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only draft distributions can be deleted'
                    ], 422);
                }
                return back()->with('error', 'Only draft distributions can be deleted');
            }
        }

        try {
            DB::beginTransaction();

            // Log deletion
            DistributionHistory::logWorkflowTransition(
                $distribution,
                Auth::user(),
                $distribution->status,
                'deleted',
                'Distribution deleted'
            );

            $distribution->delete();

            DB::commit();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Distribution deleted successfully'
                ]);
            }

            return redirect()->route('distributions.index')
                ->with('success', 'Distribution deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete distribution: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete distribution: ' . $e->getMessage());
        }
    }

    // Workflow Transition Methods

    /**
     * Mark distribution as verified by sender
     */
    public function verifyBySender(Request $request, Distribution $distribution): JsonResponse|RedirectResponse
    {
        if (!$distribution->canVerifyBySender()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution cannot be verified by sender at this stage'
                ], 422);
            }
            return back()->with('error', 'Distribution cannot be verified by sender at this stage');
        }

        $validator = Validator::make($request->all(), [
            'verification_notes' => 'nullable|string|max:1000',
            'document_verifications' => 'required|array',
            'document_verifications.*.document_id' => 'required|integer',
            'document_verifications.*.status' => 'required|in:verified,missing,damaged',
            'document_verifications.*.notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $oldStatus = $distribution->status;

            // Update distribution status
            $distribution->markAsVerifiedBySender($user, $request->verification_notes);

            // Update document verifications
            foreach ($request->document_verifications as $verification) {
                $distributionDocument = DistributionDocument::where('distribution_id', $distribution->id)
                    ->where('document_id', $verification['document_id'])
                    ->first();

                if ($distributionDocument) {
                    $distributionDocument->markAsSenderVerified(
                        $verification['status'],
                        $verification['notes'] ?? null
                    );

                    // Log document verification
                    DistributionHistory::logDocumentVerification(
                        $distribution,
                        $user,
                        'sender',
                        $distributionDocument->document_type,
                        $verification['document_id'],
                        $verification['status'],
                        $verification['notes'] ?? null
                    );
                }
            }

            // Log workflow transition
            DistributionHistory::logWorkflowTransition(
                $distribution,
                $user,
                $oldStatus,
                $distribution->status,
                'Verified by sender'
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Distribution verified by sender successfully',
                    'distribution' => $distribution->fresh()
                ]);
            }

            return redirect()->route('distributions.show', $distribution)
                ->with('success', 'Distribution verified by sender successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify distribution: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to verify distribution: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Mark distribution as sent
     */
    public function send(Request $request, Distribution $distribution): JsonResponse|RedirectResponse
    {
        if (!$distribution->canSend()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution cannot be sent at this stage'
                ], 422);
            }
            return back()->with('error', 'Distribution cannot be sent at this stage');
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $oldStatus = $distribution->status;

            $distribution->markAsSent();

            // Update document distribution statuses to "in_transit"
            $this->updateDocumentDistributionStatuses($distribution, 'in_transit');

            // Log workflow transition
            DistributionHistory::logWorkflowTransition(
                $distribution,
                $user,
                $oldStatus,
                $distribution->status,
                'Distribution sent'
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Distribution sent successfully',
                    'distribution' => $distribution->fresh()
                ]);
            }

            return redirect()->route('distributions.show', $distribution)
                ->with('success', 'Distribution sent successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send distribution: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to send distribution: ' . $e->getMessage());
        }
    }

    /**
     * Mark distribution as received
     */
    public function receive(Request $request, Distribution $distribution): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        // Check if user has permission to receive this distribution
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
            // Regular users can only receive distributions if they are in the destination department
            if (!$user->department || $user->department->id !== $distribution->destination_department_id) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only receive distributions sent to your department'
                    ], 403);
                }
                return back()->with('error', 'You can only receive distributions sent to your department');
            }
        }

        if (!$distribution->canReceive()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution cannot be received at this stage'
                ], 422);
            }
            return back()->with('error', 'Distribution cannot be received at this stage');
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $oldStatus = $distribution->status;

            $distribution->markAsReceived();

            // Update document distribution statuses to "distributed"
            $this->updateDocumentDistributionStatuses($distribution, 'distributed');

            // Update document locations to destination department
            $this->updateDocumentLocations($distribution);

            // Log workflow transition
            DistributionHistory::logWorkflowTransition(
                $distribution,
                $user,
                $oldStatus,
                $distribution->status,
                'Distribution received'
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Distribution received successfully',
                    'distribution' => $distribution->fresh()
                ]);
            }

            return redirect()->route('distributions.show', $distribution)
                ->with('success', 'Distribution received successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to receive distribution: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to receive distribution: ' . $e->getMessage());
        }
    }

    /**
     * Mark distribution as verified by receiver
     */
    public function verifyByReceiver(Request $request, Distribution $distribution): JsonResponse|RedirectResponse
    {
        if (!$distribution->canVerifyByReceiver()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution cannot be verified by receiver at this stage'
                ], 422);
            }
            return back()->with('error', 'Distribution cannot be verified by receiver at this stage');
        }

        $validator = Validator::make($request->all(), [
            'verification_notes' => 'nullable|string|max:1000',
            'document_verifications' => 'required|array',
            'document_verifications.*.document_id' => 'required|integer',
            'document_verifications.*.status' => 'required|in:verified,missing,damaged',
            'document_verifications.*.notes' => 'nullable|string|max:500',
            'has_discrepancies' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $oldStatus = $distribution->status;
            $hasDiscrepancies = $request->has_discrepancies;

            // Update distribution status
            $distribution->markAsVerifiedByReceiver($user, $request->verification_notes, $hasDiscrepancies);

            // Update document verifications
            foreach ($request->document_verifications as $verification) {
                $distributionDocument = DistributionDocument::where('distribution_id', $distribution->id)
                    ->where('document_id', $verification['document_id'])
                    ->first();

                if ($distributionDocument) {
                    $distributionDocument->markAsReceiverVerified(
                        $verification['status'],
                        $verification['notes'] ?? null
                    );

                    // Log document verification
                    DistributionHistory::logDocumentVerification(
                        $distribution,
                        $user,
                        'receiver',
                        $distributionDocument->document_type,
                        $verification['document_id'],
                        $verification['status'],
                        $verification['notes'] ?? null
                    );

                    // Log discrepancy if any
                    if (in_array($verification['status'], ['missing', 'damaged'])) {
                        DistributionHistory::logDiscrepancyReport(
                            $distribution,
                            $user,
                            $distributionDocument->document_type,
                            $verification['document_id'],
                            $verification['status'],
                            $verification['notes'] ?? null
                        );
                    }
                }
            }

            // Log workflow transition
            DistributionHistory::logWorkflowTransition(
                $distribution,
                $user,
                $oldStatus,
                $distribution->status,
                'Verified by receiver'
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Distribution verified by receiver successfully',
                    'distribution' => $distribution->fresh()
                ]);
            }

            return redirect()->route('distributions.show', $distribution)
                ->with('success', 'Distribution verified by receiver successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify distribution: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to verify distribution: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Mark distribution as completed
     */
    public function complete(Request $request, Distribution $distribution): JsonResponse|RedirectResponse
    {
        if (!$distribution->canComplete()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution cannot be completed at this stage'
                ], 422);
            }
            return back()->with('error', 'Distribution cannot be completed at this stage');
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $oldStatus = $distribution->status;

            $distribution->markAsCompleted();

            // Ensure document distribution statuses are "distributed"
            $this->updateDocumentDistributionStatuses($distribution, 'distributed');

            // Update document locations to ensure they're at the final destination
            $this->updateDocumentLocations($distribution);

            // Log workflow transition
            DistributionHistory::logWorkflowTransition(
                $distribution,
                $user,
                $oldStatus,
                $distribution->status,
                'Distribution completed'
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Distribution completed successfully',
                    'distribution' => $distribution->fresh()
                ]);
            }

            return redirect()->route('distributions.show', $distribution)
                ->with('success', 'Distribution completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to complete distribution: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to complete distribution: ' . $e->getMessage());
        }
    }

    // Helper Methods

    /**
     * Generate distribution number in format: YY/LOCATION/DDS/0001
     */
    private function generateDistributionNumber(int $year, string $locationCode, int $sequence): string
    {
        $yearSuffix = substr($year, -2);
        $formattedSequence = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        return "{$yearSuffix}/{$locationCode}/DDS/{$formattedSequence}";
    }

    /**
     * Check if distribution number is unique
     */
    private function isDistributionNumberUnique(string $distributionNumber): bool
    {
        return !Distribution::where('distribution_number', $distributionNumber)->exists();
    }

    /**
     * Generate unique distribution number with retry logic
     */
    private function generateUniqueDistributionNumber(int $year, string $locationCode, int $sequence, int $departmentId): string
    {
        $maxRetries = 10;
        $attempts = 0;
        $currentSequence = $sequence;

        do {
            $distributionNumber = $this->generateDistributionNumber($year, $locationCode, $currentSequence);

            if ($this->isDistributionNumberUnique($distributionNumber)) {
                return $distributionNumber;
            }

            // Get the next available sequence number
            $currentSequence = Distribution::getNextSequence($year, $departmentId);
            $attempts++;
        } while ($attempts < $maxRetries);

        // If we can't find a unique number after max retries, throw an exception
        throw new \Exception("Unable to generate unique distribution number after {$maxRetries} attempts");
    }

    /**
     * Attach documents to distribution
     */
    private function attachDocuments(Distribution $distribution, string $documentType, array $documentIds): void
    {
        foreach ($documentIds as $documentId) {
            DistributionDocument::create([
                'distribution_id' => $distribution->id,
                'document_type' => $documentType === 'invoice' ? Invoice::class : AdditionalDocument::class,
                'document_id' => $documentId
            ]);
        }
    }

    /**
     * Automatically attach additional documents that are linked to distributed invoices
     * This ensures that when invoices are distributed, their supporting documents are also included
     */
    private function attachInvoiceAdditionalDocuments(Distribution $distribution, array $invoiceIds): void
    {
        foreach ($invoiceIds as $invoiceId) {
            $invoice = Invoice::find($invoiceId);
            if ($invoice && $invoice->additionalDocuments()->count() > 0) {
                foreach ($invoice->additionalDocuments as $additionalDocument) {
                    // Only attach if not already attached to this distribution
                    $existingAttachment = DistributionDocument::where('distribution_id', $distribution->id)
                        ->where('document_type', AdditionalDocument::class)
                        ->where('document_id', $additionalDocument->id)
                        ->first();

                    if (!$existingAttachment) {
                        DistributionDocument::create([
                            'distribution_id' => $distribution->id,
                            'document_type' => AdditionalDocument::class,
                            'document_id' => $additionalDocument->id
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Update document distribution statuses
     * Called when:
     * 1. Distribution is sent (status: in_transit)
     * 2. Distribution is received (status: distributed)
     * 3. Distribution is completed (status: distributed)
     * 
     * Note: When distributing invoices, this also updates the status of any
     * additional documents that are attached to those invoices.
     */
    private function updateDocumentDistributionStatuses(Distribution $distribution, string $status): void
    {
        foreach ($distribution->documents as $distributionDocument) {
            if ($distributionDocument->document_type === Invoice::class) {
                // Update invoice status
                Invoice::where('id', $distributionDocument->document_id)
                    ->update(['distribution_status' => $status]);

                // Also update status of any additional documents attached to this invoice
                $invoice = Invoice::find($distributionDocument->document_id);
                if ($invoice && $invoice->additionalDocuments()->count() > 0) {
                    $invoice->additionalDocuments()->update(['distribution_status' => $status]);
                }
            } elseif ($distributionDocument->document_type === AdditionalDocument::class) {
                AdditionalDocument::where('id', $distributionDocument->document_id)
                    ->update(['distribution_status' => $status]);
            }
        }
    }

    /**
     * Update document locations to destination department
     * Called when:
     * 1. Distribution is received (initial location update)
     * 2. Distribution is completed (final location confirmation)
     * 
     * Note: When moving invoices, this also moves any additional documents
     * that are attached to those invoices.
     */
    private function updateDocumentLocations(Distribution $distribution): void
    {
        $destinationLocationCode = $distribution->destinationDepartment->location_code;

        foreach ($distribution->documents as $distributionDocument) {
            if ($distributionDocument->document_type === Invoice::class) {
                // Update invoice location
                Invoice::where('id', $distributionDocument->document_id)
                    ->update(['cur_loc' => $destinationLocationCode]);

                // Also update location of any additional documents attached to this invoice
                $invoice = Invoice::find($distributionDocument->document_id);
                if ($invoice && $invoice->additionalDocuments()->count() > 0) {
                    $invoice->additionalDocuments()->update(['cur_loc' => $destinationLocationCode]);
                }
            } elseif ($distributionDocument->document_type === AdditionalDocument::class) {
                AdditionalDocument::where('id', $distributionDocument->document_id)
                    ->update(['cur_loc' => $destinationLocationCode]);
            }
        }
    }

    /**
     * Get distribution history
     */
    public function history(Distribution $distribution): JsonResponse
    {
        $histories = $distribution->histories()
            ->with('user')
            ->orderBy('action_performed_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'histories' => $histories
        ]);
    }

    /**
     * Get discrepancy summary
     */
    public function discrepancySummary(Distribution $distribution): JsonResponse
    {
        $discrepancies = $distribution->documents()
            ->withDiscrepancies()
            ->get();

        return response()->json([
            'success' => true,
            'discrepancies' => $discrepancies
        ]);
    }

    /**
     * Get next sequence number for a department/year combination
     */
    public function getNextSequence(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2030',
            'department_id' => 'required|exists:departments,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $nextSequence = Distribution::getNextSequence($request->year, $request->department_id);

        return response()->json([
            'success' => true,
            'next_sequence' => $nextSequence,
            'distribution_number' => $this->generateDistributionNumber(
                $request->year,
                Department::find($request->department_id)->location_code,
                $nextSequence
            )
        ]);
    }

    /**
     * Get distribution statistics for numbering system monitoring
     */
    public function getNumberingStats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year' => 'nullable|integer|min:2020|max:2030',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $year = $request->year ?? Carbon::now()->year;
        $departmentId = $request->department_id;

        $query = Distribution::query();

        if ($departmentId) {
            $query->where('origin_department_id', $departmentId);
        }

        $query->where('year', $year);

        $stats = [
            'year' => $year,
            'total_distributions' => $query->count(),
            'sequence_range' => [
                'min' => $query->min('sequence'),
                'max' => $query->max('sequence')
            ],
            'by_department' => []
        ];

        if (!$departmentId) {
            // Get stats by department
            $departments = Department::all();
            foreach ($departments as $dept) {
                $deptStats = Distribution::where('year', $year)
                    ->where('origin_department_id', $dept->id)
                    ->selectRaw('
                        COUNT(*) as total,
                        MIN(sequence) as min_sequence,
                        MAX(sequence) as max_sequence,
                        MAX(sequence) + 1 as next_sequence
                    ')
                    ->first();

                $stats['by_department'][] = [
                    'department_id' => $dept->id,
                    'department_name' => $dept->name,
                    'location_code' => $dept->location_code,
                    'total' => $deptStats->total ?? 0,
                    'sequence_range' => [
                        'min' => $deptStats->min_sequence ?? 0,
                        'max' => $deptStats->max_sequence ?? 0
                    ],
                    'next_sequence' => $deptStats->next_sequence ?? 1
                ];
            }
        }

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
