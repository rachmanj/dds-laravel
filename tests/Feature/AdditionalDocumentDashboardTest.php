<?php

namespace Tests\Feature;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use App\Models\Distribution;
use App\Models\DistributionDocument;
use App\Models\DistributionType;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\DistributionTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdditionalDocumentDashboardTest extends TestCase
{
    use RefreshDatabase;

    private int $additionalDocumentTypeId;

    private DistributionType $distributionType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);
        $this->seed(DistributionTypeSeeder::class);

        $this->additionalDocumentTypeId = AdditionalDocumentType::query()->firstOrFail()->id;
        $this->distributionType = DistributionType::query()->firstOrFail();
    }

    protected function tearDown(): void
    {
        AdditionalDocument::clearLocationArrivalPreloadCache();

        parent::tearDown();
    }

    private function createAdminUser(): User
    {
        $department = Department::query()->create([
            'name' => 'HQ',
            'project' => '001H',
            'location_code' => '000HACC',
            'akronim' => 'HQ',
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $user->assignRole('admin');

        return $user;
    }

    private function createAdditionalDocument(
        User $user,
        array $attributes = [],
    ): AdditionalDocument {
        $defaults = [
            'type_id' => $this->additionalDocumentTypeId,
            'document_number' => 'DOC-'.uniqid(),
            'document_date' => now()->toDateString(),
            'created_by' => $user->id,
            'status' => 'open',
            'distribution_status' => 'available',
            'cur_loc' => '000HACC',
        ];

        return AdditionalDocument::query()->create(array_merge($defaults, $attributes));
    }

    private function attachVerifiedDistribution(
        User $user,
        AdditionalDocument $document,
        Carbon $receivedAt,
        string $receiverVerificationStatus = 'verified',
    ): void {
        $origin = Department::query()->where('location_code', '000HACC')->firstOrFail();
        $destination = Department::query()->firstOrCreate(
            ['location_code' => '000HDEST'],
            [
                'name' => 'Destination',
                'project' => '001H',
                'akronim' => 'DST',
            ],
        );

        $distribution = Distribution::query()->create([
            'distribution_number' => 'DIST-'.uniqid(),
            'type_id' => $this->distributionType->id,
            'origin_department_id' => $origin->id,
            'destination_department_id' => $destination->id,
            'document_type' => 'additional_document',
            'created_by' => $user->id,
            'status' => 'completed',
            'received_at' => $receivedAt,
            'year' => (int) $receivedAt->format('Y'),
            'sequence' => random_int(1000, 9999),
        ]);

        DistributionDocument::query()->create([
            'distribution_id' => $distribution->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $document->id,
            'origin_cur_loc' => $origin->location_code,
            'receiver_verification_status' => $receiverVerificationStatus,
        ]);
    }

    /**
     * @param  iterable<int, AdditionalDocument>  $documents
     * @return array{age_breakdown: array<string, int>, status_by_age: array<string, array<string, int>>}
     */
    private function computeExpectedAgeAndStatusFromAccessors(iterable $documents): array
    {
        AdditionalDocument::clearLocationArrivalPreloadCache();

        $ageBreakdown = [
            '0-7_days' => 0,
            '8-14_days' => 0,
            '15-30_days' => 0,
            '30_plus_days' => 0,
        ];

        $statusByAge = [
            '0-7_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
            '8-14_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
            '15-30_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
            '30_plus_days' => ['available' => 0, 'in_transit' => 0, 'distributed' => 0, 'unaccounted_for' => 0],
        ];

        foreach ($documents as $document) {
            $fresh = AdditionalDocument::query()->findOrFail($document->id);
            $ageCategory = $fresh->current_location_age_category;
            $status = $fresh->distribution_status ?? 'available';

            if (! isset($ageBreakdown[$ageCategory])) {
                $ageCategory = '0-7_days';
            }

            $ageBreakdown[$ageCategory]++;

            if (isset($statusByAge[$ageCategory][$status])) {
                $statusByAge[$ageCategory][$status]++;
            }
        }

        return [
            'age_breakdown' => $ageBreakdown,
            'status_by_age' => $statusByAge,
        ];
    }

    /**
     * @param  iterable<int, AdditionalDocument>  $documents
     * @return array<string, int>
     */
    private function computeExpectedAlertsFromAccessors(iterable $documents): array
    {
        AdditionalDocument::clearLocationArrivalPreloadCache();

        $alerts = [
            'overdue_critical' => 0,
            'overdue_warning' => 0,
            'stuck_documents' => 0,
            'recently_arrived' => 0,
        ];

        foreach ($documents as $document) {
            $fresh = AdditionalDocument::query()->findOrFail($document->id);
            $daysInCurrentLocation = $fresh->days_in_current_location;
            $status = $fresh->distribution_status ?? 'available';

            if ($daysInCurrentLocation > 30 && in_array($status, ['available', 'in_transit'], true)) {
                $alerts['overdue_critical']++;
            } elseif ($daysInCurrentLocation > 14 && $daysInCurrentLocation <= 30 && in_array($status, ['available', 'in_transit'], true)) {
                $alerts['overdue_warning']++;
            } elseif ($daysInCurrentLocation > 7 && $status === 'available') {
                $alerts['stuck_documents']++;
            } elseif ($daysInCurrentLocation <= 3) {
                $alerts['recently_arrived']++;
            }
        }

        return $alerts;
    }

    public function test_dashboard_aging_metrics_match_accessor_semantics(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00'));

        $user = $this->createAdminUser();

        $recentAvailable = $this->createAdditionalDocument($user, [
            'document_number' => 'RECENT-AVAIL',
            'receive_date' => '2026-03-12',
            'distribution_status' => 'available',
        ]);

        $stuckAvailable = $this->createAdditionalDocument($user, [
            'document_number' => 'STUCK-AVAIL',
            'receive_date' => '2026-03-01',
            'distribution_status' => 'available',
        ]);

        $warningInTransit = $this->createAdditionalDocument($user, [
            'document_number' => 'WARN-TRANSIT',
            'receive_date' => '2026-02-10',
            'distribution_status' => 'in_transit',
        ]);

        $criticalDistributed = $this->createAdditionalDocument($user, [
            'document_number' => 'CRIT-DIST',
            'receive_date' => '2025-01-01',
            'distribution_status' => 'distributed',
        ]);
        $this->attachVerifiedDistribution($user, $criticalDistributed, Carbon::parse('2026-01-01 10:00:00'));

        $availableWithPivotUsesVerifiedReceipt = $this->createAdditionalDocument($user, [
            'document_number' => 'AVAIL-PIVOT',
            'receive_date' => '2025-01-01',
            'distribution_status' => 'available',
        ]);
        $this->attachVerifiedDistribution($user, $availableWithPivotUsesVerifiedReceipt, Carbon::parse('2026-03-10 08:00:00'));

        $unverifiedDoesNotCount = $this->createAdditionalDocument($user, [
            'document_number' => 'UNVERIFIED',
            'receive_date' => '2026-03-01',
            'distribution_status' => 'distributed',
        ]);
        $this->attachVerifiedDistribution($user, $unverifiedDoesNotCount, Carbon::parse('2026-03-01 08:00:00'), 'missing');

        $documents = AdditionalDocument::query()->orderBy('id')->get();
        $expectedAge = $this->computeExpectedAgeAndStatusFromAccessors($documents);
        $expectedAlerts = $this->computeExpectedAlertsFromAccessors($documents);

        $expectedWorkflow = [
            'total_documents' => $documents->count(),
            'distributed_documents' => $documents->where('distribution_status', 'distributed')->count(),
            'in_transit_documents' => $documents->where('distribution_status', 'in_transit')->count(),
            'unaccounted_documents' => $documents->where('distribution_status', 'unaccounted_for')->count(),
        ];
        $expectedWorkflow['distribution_efficiency'] = $expectedWorkflow['total_documents'] > 0
            ? round(($expectedWorkflow['distributed_documents'] / $expectedWorkflow['total_documents']) * 100, 2)
            : 0;

        AdditionalDocument::clearLocationArrivalPreloadCache();

        $response = $this->actingAs($user)->get(route('additional-documents.dashboard'));

        $response->assertOk();

        $ageAndStatus = $response->viewData('ageAndStatus');
        $departmentAlerts = $response->viewData('departmentAlerts');
        $workflowMetrics = $response->viewData('workflowMetrics');

        $this->assertSame($expectedAge['age_breakdown'], $ageAndStatus['age_breakdown']);
        $this->assertSame($expectedAge['status_by_age'], $ageAndStatus['status_by_age']);
        $this->assertSame($expectedAlerts, $departmentAlerts);
        $this->assertSame($expectedWorkflow['total_documents'], $workflowMetrics['total_documents']);
        $this->assertSame($expectedWorkflow['distributed_documents'], $workflowMetrics['distributed_documents']);
        $this->assertSame($expectedWorkflow['in_transit_documents'], $workflowMetrics['in_transit_documents']);
        $this->assertSame($expectedWorkflow['unaccounted_documents'], $workflowMetrics['unaccounted_documents']);
        $this->assertSame($expectedWorkflow['distribution_efficiency'], $workflowMetrics['distribution_efficiency']);

        Carbon::setTestNow();
    }

    public function test_aging_metrics_methods_share_single_document_fetch_per_request(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00'));

        $user = $this->createAdminUser();

        $this->createAdditionalDocument($user, [
            'receive_date' => '2026-03-01',
            'distribution_status' => 'available',
        ]);
        $this->createAdditionalDocument($user, [
            'receive_date' => '2026-02-01',
            'distribution_status' => 'in_transit',
        ]);

        AdditionalDocument::clearLocationArrivalPreloadCache();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $controller = app(\App\Http\Controllers\AdditionalDocumentDashboardController::class);
        $isAdmin = ['admin'];

        $invoke = function (string $method) use ($controller, $user, $isAdmin): void {
            $reflection = new \ReflectionMethod($controller, $method);
            $reflection->setAccessible(true);
            $reflection->invoke($controller, $user, $user->department_location_code, $isAdmin);
        };

        $invoke('getAgeAndStatusMetrics');
        $queriesAfterFirstMethod = count(DB::getQueryLog());

        $invoke('getDepartmentSpecificAgingAlerts');
        $queriesAfterSecondMethod = count(DB::getQueryLog());

        $this->assertSame(
            $queriesAfterFirstMethod,
            $queriesAfterSecondMethod,
            'Second aging metrics method should not run additional database queries.',
        );

        Carbon::setTestNow();
    }

    public function test_dashboard_query_count_does_not_scale_with_document_volume(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->get(route('additional-documents.dashboard'));

        $this->seedDocumentsForQueryBenchmark($user, 10);
        $queriesForTenDocuments = $this->countDashboardQueries($user);

        $this->seedDocumentsForQueryBenchmark($user, 20);
        $queriesForThirtyDocuments = $this->countDashboardQueries($user);

        $this->assertSame(
            $queriesForTenDocuments,
            $queriesForThirtyDocuments,
            'Dashboard query count should stay constant when document count increases.',
        );
    }

    private function countDashboardQueries(User $user): int
    {
        AdditionalDocument::clearLocationArrivalPreloadCache();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)->get(route('additional-documents.dashboard'));

        return count(DB::getQueryLog());
    }

    private function seedDocumentsForQueryBenchmark(User $user, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $document = $this->createAdditionalDocument($user, [
                'document_number' => 'BENCH-'.uniqid(),
                'receive_date' => now()->subDays($i % 45)->toDateString(),
                'distribution_status' => ['available', 'in_transit', 'distributed'][$i % 3],
                'po_no' => $i % 2 === 0 ? 'PO-BENCH-'.($i % 5) : null,
            ]);

            if ($i % 4 === 0) {
                $this->attachVerifiedDistribution(
                    $user,
                    $document,
                    now()->subDays($i % 20),
                );
            }
        }
    }
}
