<?php

namespace Tests\Unit;

use App\Repositories\SapUsageRepository;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SapUsageRepositoryTest extends TestCase
{
    public function test_fetch_reads_param_sql_file_and_passes_six_bindings(): void
    {
        $expectedSql = trim((string) file_get_contents(base_path('docs/sap-queries/pemakaian-param.sql')));
        $from = '2026-09-01';
        $to = '2026-09-15';

        $connection = Mockery::mock();
        $connection->shouldReceive('select')
            ->once()
            ->withArgs(function (string $sql, array $bindings) use ($expectedSql, $from, $to): bool {
                return $sql === $expectedSql
                    && $bindings === [$from, $to, $from, $to, $from, $to];
            })
            ->andReturn([]);

        DB::shouldReceive('connection')
            ->once()
            ->with('sap_sql')
            ->andReturn($connection);

        $rows = (new SapUsageRepository)->fetch($from, $to);

        $this->assertSame([], $rows);
    }

    public function test_fetch_by_source_filters_rows_in_php(): void
    {
        $from = '2026-09-01';
        $to = '2026-09-15';

        $connection = Mockery::mock();
        $connection->shouldReceive('select')
            ->once()
            ->andReturn([
                (object) ['Source' => 'Goods Issue', 'DocNum' => '1001'],
                (object) ['Source' => 'Delivery', 'DocNum' => '2001'],
                (object) ['Source' => 'AP Service', 'DocNum' => '3001'],
            ]);

        DB::shouldReceive('connection')
            ->once()
            ->with('sap_sql')
            ->andReturn($connection);

        $rows = (new SapUsageRepository)->fetchBySource($from, $to, 'delivery');

        $this->assertCount(1, $rows);
        $this->assertSame('delivery', $rows[0]['source']);
        $this->assertSame('2001', $rows[0]['doc_num']);
    }
}
