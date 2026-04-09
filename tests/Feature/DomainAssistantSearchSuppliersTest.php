<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use App\Services\DomainAssistantDataService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainAssistantSearchSuppliersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_search_suppliers_multi_word_requires_all_tokens_in_name_or_code(): void
    {
        $user = User::factory()->create(['is_active' => true, 'username' => 'supsearch1']);
        $user->assignRole('admin');

        foreach ([
            ['VMIEIDR01', 'MITRA INTI ENERGI, PT'],
            ['VMISIDR01', 'MITRA INTI SEJAHTERA, PT'],
            ['VMIISIDR01', 'MITRA INTI SOLUSINDO'],
            ['VMIUIDR01', 'MITRA INTI UTAMA, PT'],
        ] as [$code, $name]) {
            Supplier::query()->create([
                'sap_code' => $code,
                'name' => $name,
                'type' => 'vendor',
                'is_active' => true,
                'created_by' => $user->id,
            ]);
        }

        $service = app(DomainAssistantDataService::class);

        $narrow = $service->searchSuppliers($user, 'Mitra Inti Solusindo', 20);
        $this->assertCount(1, $narrow);
        $this->assertSame('VMIISIDR01', $narrow[0]['sap_code']);

        $broad = $service->searchSuppliers($user, 'Mitra Inti', 20);
        $this->assertCount(4, $broad);
    }

    public function test_search_suppliers_sap_code_exact_match(): void
    {
        $user = User::factory()->create(['is_active' => true, 'username' => 'supsearch2']);
        $user->assignRole('admin');

        Supplier::query()->create([
            'sap_code' => 'VMIISIDR01',
            'name' => 'MITRA INTI SOLUSINDO',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $service = app(DomainAssistantDataService::class);
        $rows = $service->searchSuppliers($user, 'VMIISIDR01', 20);

        $this->assertCount(1, $rows);
        $this->assertSame('MITRA INTI SOLUSINDO', $rows[0]['name']);
    }
}
