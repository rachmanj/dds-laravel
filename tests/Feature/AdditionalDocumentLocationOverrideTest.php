<?php

namespace Tests\Feature;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use App\Models\Distribution;
use App\Models\DistributionDocument;
use App\Models\DistributionType;
use App\Models\User;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\DistributionTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalDocumentLocationOverrideTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Department, 2: Department, 3: int, 4: DistributionType}
     */
    private function seedBasics(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);
        $this->seed(DistributionTypeSeeder::class);

        $wrongLocation = Department::query()->create([
            'name' => 'Accounting Dept',
            'project' => '001H',
            'location_code' => '000HACC',
            'akronim' => 'ACC',
        ]);

        $correctLocation = Department::query()->create([
            'name' => 'Logistic Dept',
            'project' => '001H',
            'location_code' => '000HLOG',
            'akronim' => 'LOG',
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => $wrongLocation->id,
        ]);
        $user->assignRole('admin');

        $additionalDocumentTypeId = AdditionalDocumentType::query()->firstOrFail()->id;
        $distributionType = DistributionType::query()->firstOrFail();

        return [$user, $wrongLocation, $correctLocation, $additionalDocumentTypeId, $distributionType];
    }

    /**
     * @return array{0: AdditionalDocument, 1: Distribution}
     */
    private function createLockedGrpoDocument(
        User $user,
        Department $wrongLocation,
        Department $correctLocation,
        int $additionalDocumentTypeId,
        DistributionType $distributionType,
        string $distributionStatus = 'received',
    ): array {
        $date = now()->toDateString();

        $document = AdditionalDocument::query()->create([
            'type_id' => $additionalDocumentTypeId,
            'document_number' => 'DO-GRPO-LOCK-001',
            'document_date' => $date,
            'receive_date' => $date,
            'grpo_no' => 'GRPO-12345',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => $wrongLocation->location_code,
            'distribution_status' => 'distributed',
        ]);

        $distribution = Distribution::query()->create([
            'distribution_number' => '26/000HACC/DDS/LOC1',
            'type_id' => $distributionType->id,
            'origin_department_id' => $wrongLocation->id,
            'destination_department_id' => $correctLocation->id,
            'document_type' => 'additional_document',
            'created_by' => $user->id,
            'status' => $distributionStatus,
            'received_at' => $distributionStatus === 'received' ? now() : null,
            'year' => 2026,
            'sequence' => 1,
        ]);

        DistributionDocument::query()->create([
            'distribution_id' => $distribution->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $document->id,
            'origin_cur_loc' => $wrongLocation->location_code,
            'skip_verification' => false,
        ]);

        return [$document, $distribution];
    }

    public function test_user_with_permission_can_force_location_override_on_locked_grpo_document(): void
    {
        [
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        ] = $this->seedBasics();

        [$document] = $this->createLockedGrpoDocument(
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        );

        $response = $this->actingAs($user)->post(
            route('additional-documents.force-location', $document),
            [
                'to_loc' => $correctLocation->location_code,
                'reason' => 'Koreksi lokasi default GRPO yang salah.',
            ]
        );

        $response->assertRedirect()
            ->assertSessionHas('success');

        $document->refresh();
        $this->assertSame($correctLocation->location_code, $document->cur_loc);
        $this->assertSame('available', $document->distribution_status);

        $this->assertDatabaseHas('document_location_overrides', [
            'document_type' => AdditionalDocument::class,
            'document_id' => $document->id,
            'from_loc' => $wrongLocation->location_code,
            'to_loc' => $correctLocation->location_code,
            'reason' => 'Koreksi lokasi default GRPO yang salah.',
            'overridden_by' => $user->id,
        ]);
    }

    public function test_user_without_permission_cannot_force_location_override(): void
    {
        [
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        ] = $this->seedBasics();

        $logisticUser = User::factory()->create([
            'is_active' => true,
            'department_id' => $wrongLocation->id,
        ]);
        $logisticUser->assignRole('logistic');

        [$document] = $this->createLockedGrpoDocument(
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        );

        $response = $this->actingAs($logisticUser)->post(
            route('additional-documents.force-location', $document),
            [
                'to_loc' => $correctLocation->location_code,
                'reason' => 'Mencoba override tanpa izin.',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('document_location_overrides', [
            'document_id' => $document->id,
        ]);
    }

    public function test_force_location_override_rejects_document_without_grpo_no(): void
    {
        [
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        ] = $this->seedBasics();

        [$document] = $this->createLockedGrpoDocument(
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        );

        $document->update(['grpo_no' => null]);

        $response = $this->actingAs($user)->post(
            route('additional-documents.force-location', $document),
            [
                'to_loc' => $correctLocation->location_code,
                'reason' => 'Koreksi lokasi dokumen non-GRPO.',
            ]
        );

        $response->assertRedirect()
            ->assertSessionHasErrors('force_location');

        $this->assertDatabaseMissing('document_location_overrides', [
            'document_id' => $document->id,
        ]);
    }

    public function test_force_location_override_requires_valid_different_to_loc(): void
    {
        [
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        ] = $this->seedBasics();

        [$document] = $this->createLockedGrpoDocument(
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        );

        $responseMissing = $this->actingAs($user)->post(
            route('additional-documents.force-location', $document),
            [
                'reason' => 'Alasan koreksi lokasi yang valid.',
            ]
        );
        $responseMissing->assertRedirect()->assertSessionHasErrors('to_loc');

        $responseSame = $this->actingAs($user)->post(
            route('additional-documents.force-location', $document),
            [
                'to_loc' => $wrongLocation->location_code,
                'reason' => 'Alasan koreksi lokasi yang valid.',
            ]
        );
        $responseSame->assertRedirect()->assertSessionHasErrors('to_loc');

        $responseInvalid = $this->actingAs($user)->post(
            route('additional-documents.force-location', $document),
            [
                'to_loc' => 'INVALIDLOC',
                'reason' => 'Alasan koreksi lokasi yang valid.',
            ]
        );
        $responseInvalid->assertRedirect()->assertSessionHasErrors('to_loc');
    }

    public function test_force_location_override_requires_reason_min_10_characters(): void
    {
        [
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        ] = $this->seedBasics();

        [$document] = $this->createLockedGrpoDocument(
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        );

        $response = $this->actingAs($user)->post(
            route('additional-documents.force-location', $document),
            [
                'to_loc' => $correctLocation->location_code,
                'reason' => 'pendek',
            ]
        );

        $response->assertRedirect()->assertSessionHasErrors('reason');
    }

    public function test_force_location_override_rejects_document_in_sent_distribution(): void
    {
        [
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        ] = $this->seedBasics();

        [$document] = $this->createLockedGrpoDocument(
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
            'sent',
        );

        $response = $this->actingAs($user)->post(
            route('additional-documents.force-location', $document),
            [
                'to_loc' => $correctLocation->location_code,
                'reason' => 'Koreksi lokasi saat dokumen masih dalam distribusi.',
            ]
        );

        $response->assertRedirect()
            ->assertSessionHasErrors('force_location');

        $document->refresh();
        $this->assertSame($wrongLocation->location_code, $document->cur_loc);
        $this->assertDatabaseMissing('document_location_overrides', [
            'document_id' => $document->id,
        ]);
    }

    public function test_edit_page_shows_location_override_button_for_locked_grpo_document(): void
    {
        [
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        ] = $this->seedBasics();

        [$document] = $this->createLockedGrpoDocument(
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        );

        $response = $this->actingAs($user)->get(route('additional-documents.edit', $document));

        $response->assertOk()
            ->assertSee('Koreksi Lokasi')
            ->assertSee('forceLocationOverrideModal');
    }

    public function test_edit_page_shows_location_override_history(): void
    {
        [
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        ] = $this->seedBasics();

        [$document] = $this->createLockedGrpoDocument(
            $user,
            $wrongLocation,
            $correctLocation,
            $additionalDocumentTypeId,
            $distributionType,
        );

        $this->actingAs($user)->post(
            route('additional-documents.force-location', $document),
            [
                'to_loc' => $correctLocation->location_code,
                'reason' => 'Koreksi lokasi default GRPO yang salah.',
            ]
        );

        $response = $this->actingAs($user)->get(route('additional-documents.edit', $document));

        $response->assertOk()
            ->assertSee('Riwayat Koreksi Lokasi')
            ->assertSee($wrongLocation->location_code)
            ->assertSee($correctLocation->location_code)
            ->assertSee('Koreksi lokasi default GRPO yang salah.');
    }
}
