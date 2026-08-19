<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Project;
use App\Models\SignatureSpecimen;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SignatureSpecimenCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    private function seedProject(): Project
    {
        return Project::query()->create([
            'code' => '001H',
            'owner' => 'Test Project',
            'location' => 'Site A',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_signature_specimens(): void
    {
        $this->get(route('admin.signature-specimens.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_signature_specimens(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('logistic');

        $this->actingAs($user)->get(route('admin.signature-specimens.index'))
            ->assertForbidden();
    }

    public function test_accounting_user_can_create_signature_specimen(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('accounting');

        $project = $this->seedProject();
        $department = Department::query()->create([
            'name' => 'Accounting Dept',
            'project' => '001H',
            'location_code' => '000HACC',
            'akronim' => 'ACC',
        ]);

        $response = $this->actingAs($user)->post(route('admin.signature-specimens.store'), [
            'name' => 'Receiver Alpha',
            'nik' => 'NIK123',
            'department_id' => $department->id,
            'project_ids' => [$project->id],
            'images' => [
                UploadedFile::fake()->image('sig1.jpg'),
            ],
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.signature-specimens.index'));
        $response->assertSessionHas('success');

        $specimen = SignatureSpecimen::query()->where('name', 'Receiver Alpha')->first();
        $this->assertNotNull($specimen);
        $this->assertTrue($specimen->projects()->where('projects.id', $project->id)->exists());
        $this->assertCount(1, $specimen->images);
    }

    public function test_accounting_user_can_delete_signature_specimen(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('accounting');

        $project = $this->seedProject();
        $specimen = SignatureSpecimen::query()->create([
            'name' => 'To Delete',
            'is_active' => true,
        ]);
        $specimen->projects()->attach($project->id);

        $response = $this->actingAs($user)->deleteJson(route('admin.signature-specimens.destroy', $specimen));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('signature_specimens', ['id' => $specimen->id]);
    }
}
