<?php

namespace Tests\Unit;

use App\Services\SapService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SapGrpoSqlLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sap.server_url' => 'https://example.test/b1s/v1/',
            'sap.db_name' => 'SBO_AAP_NEW',
            'sap.user' => 'manager',
            'sap.password' => 'secret',
        ]);
    }

    public function test_sql_lookup_groups_grpo_lines_by_doc_entry(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('selectOne')
            ->once()
            ->andReturn((object) [
                'DocEntry' => 20488,
                'DocNum' => 260204302,
                'CardCode' => 'VCASJIDR01',
            ]);
        $connection->shouldReceive('select')
            ->once()
            ->andReturn([
                (object) [
                    'DocEntry' => 22904,
                    'DocNum' => 2605,
                    'CardCode' => 'VCASJIDR01',
                    'LineNum' => 0,
                    'ItemCode' => 'TO-SCAFFSET',
                    'Quantity' => 4,
                    'OpenQty' => 4,
                    'Price' => 1105000,
                ],
                (object) [
                    'DocEntry' => 22904,
                    'DocNum' => 2605,
                    'CardCode' => 'VCASJIDR01',
                    'LineNum' => 1,
                    'ItemCode' => 'TO-SCFCTW',
                    'Quantity' => 4,
                    'OpenQty' => 4,
                    'Price' => 910000,
                ],
                (object) [
                    'DocEntry' => 22904,
                    'DocNum' => 2605,
                    'CardCode' => 'VCASJIDR01',
                    'LineNum' => 2,
                    'ItemCode' => 'TO-SCFEB060',
                    'Quantity' => 8,
                    'OpenQty' => 8,
                    'Price' => 162500,
                ],
            ]);

        DB::shouldReceive('connection')->with('sap_sql')->andReturn($connection);

        $grpos = (new SapService)->getGrposByPoNumber('260204302');

        $this->assertCount(1, $grpos);
        $this->assertSame(22904, $grpos[0]['DocEntry']);
        $this->assertSame(2605, $grpos[0]['DocNum']);
        $this->assertSame('VCASJIDR01', $grpos[0]['CardCode']);
        $this->assertCount(3, $grpos[0]['DocumentLines']);
        $this->assertSame('TO-SCAFFSET', $grpos[0]['DocumentLines'][0]['ItemCode']);
        $this->assertSame(4.0, $grpos[0]['DocumentLines'][0]['RemainingOpenQuantity']);
        $this->assertSame(1105000.0, $grpos[0]['DocumentLines'][0]['Price']);
        $this->assertSame('TO-SCFEB060', $grpos[0]['DocumentLines'][2]['ItemCode']);
        $this->assertSame(8.0, $grpos[0]['DocumentLines'][2]['Quantity']);
    }

    public function test_sql_lookup_returns_empty_when_purchase_order_is_missing(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('selectOne')->once()->andReturn(null);
        $connection->shouldNotReceive('select');

        DB::shouldReceive('connection')->with('sap_sql')->andReturn($connection);

        $grpos = (new SapService)->getGrposByPoNumber('260204302');

        $this->assertSame([], $grpos);
    }

    public function test_sql_lookup_returns_empty_for_blank_po_number(): void
    {
        DB::shouldReceive('connection')->never();

        $grpos = (new SapService)->getGrposByPoNumber('   ');

        $this->assertSame([], $grpos);
    }

    public function test_service_layer_fallback_runs_when_sql_throws(): void
    {
        $service = new class extends SapService
        {
            public bool $fallbackCalled = false;

            protected function getGrposByPoNumberFromSql(string $poDocNum): array
            {
                throw new \RuntimeException('could not find driver');
            }

            protected function getGrposByPoNumberFromServiceLayer(string $poDocNum): array
            {
                $this->fallbackCalled = true;

                return [
                    [
                        'DocEntry' => 22904,
                        'DocNum' => 2605,
                        'CardCode' => 'VCASJIDR01',
                        'DocumentLines' => [],
                    ],
                ];
            }
        };

        $grpos = $service->getGrposByPoNumber('260204302');

        $this->assertTrue($service->fallbackCalled);
        $this->assertSame(22904, $grpos[0]['DocEntry']);
    }
}
