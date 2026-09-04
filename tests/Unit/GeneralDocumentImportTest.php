<?php

namespace Tests\Unit;

use App\Imports\GeneralDocumentImport;
use App\Models\AdditionalDocumentType;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class GeneralDocumentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_document_type_id_for_do_resolves_delivery_order_do_type(): void
    {
        $this->seed(AdditionalDocumentTypeSeeder::class);

        $deliveryOrderDoType = AdditionalDocumentType::query()
            ->where('type_name', 'Delivery Order (DO)')
            ->firstOrFail();

        $import = new GeneralDocumentImport;
        $typeId = $this->invokeGetDocumentTypeId($import, 'DO');

        $this->assertSame($deliveryOrderDoType->id, $typeId);
        $this->assertNull(
            AdditionalDocumentType::query()->where('type_name', 'Delivery Order')->first()
        );
    }

    private function invokeGetDocumentTypeId(GeneralDocumentImport $import, string $typeCode): ?int
    {
        $method = new ReflectionMethod(GeneralDocumentImport::class, 'getDocumentTypeId');
        $method->setAccessible(true);

        return $method->invoke($import, $typeCode);
    }
}
