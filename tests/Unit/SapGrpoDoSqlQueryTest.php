<?php

namespace Tests\Unit;

use App\Services\SapService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SapGrpoDoSqlQueryTest extends TestCase
{
    public function test_execute_grpo_do_sql_query_returns_mapped_rows(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('select')
            ->once()
            ->withArgs(function (string $sql, array $bindings) {
                return $bindings === ['2026-09-03']
                    && str_contains($sql, 'FROM OPDN h')
                    && str_contains($sql, 'OUTER APPLY');
            })
            ->andReturn([
                (object) [
                    'DocEntry' => 1001,
                    'DocNum' => 2605001,
                    'CardCode' => 'VCASJIDR01',
                    'CardName' => 'Vendor A',
                    'NumAtCard' => 'DO-TEST-001',
                    'TaxDate' => '2026-09-03',
                    'DocDate' => '2026-09-03',
                    'CreateDate' => '2026-09-03',
                    'PoNo' => '250206314',
                    'Project' => '022C',
                ],
            ]);

        DB::shouldReceive('connection')->with('sap_sql')->andReturn($connection);

        $rows = (new SapService)->executeGrpoDoSqlQuery('2026-09-03');

        $this->assertCount(1, $rows);
        $this->assertSame(2605001, $rows[0]['DocNum']);
        $this->assertSame('DO-TEST-001', $rows[0]['NumAtCard']);
        $this->assertSame('250206314', $rows[0]['PoNo']);
        $this->assertSame('022C', $rows[0]['Project']);
    }
}
