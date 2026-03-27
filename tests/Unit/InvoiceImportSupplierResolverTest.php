<?php

namespace Tests\Unit;

use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceImportSupplierResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceImportSupplierResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_name_match_returns_supplier_id(): void
    {
        $user = User::factory()->create();
        Supplier::create([
            'name' => 'PT Contoh Supplier',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $resolver = new InvoiceImportSupplierResolver;
        $result = $resolver->resolve('PT Contoh Supplier');

        $this->assertNotNull($result['supplier_id']);
        $this->assertCount(1, $result['candidates']);
    }

    public function test_unknown_name_returns_null_id(): void
    {
        $user = User::factory()->create();
        Supplier::create([
            'name' => 'Other Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $resolver = new InvoiceImportSupplierResolver;
        $result = $resolver->resolve('Completely Unknown Name XYZ');

        $this->assertNull($result['supplier_id']);
    }
}
