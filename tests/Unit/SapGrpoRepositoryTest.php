<?php

namespace Tests\Unit;

use App\Repositories\SapGrpoRepository;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SapGrpoRepositoryTest extends TestCase
{
    public function test_fetch_reads_param_sql_file_and_passes_two_bindings(): void
    {
        $expectedSql = trim((string) file_get_contents(base_path('docs/sap-queries/grpo-param.sql')));
        $from = '2026-09-01';
        $to = '2026-09-15';

        $connection = Mockery::mock();
        $connection->shouldReceive('select')
            ->once()
            ->withArgs(function (string $sql, array $bindings) use ($expectedSql, $from, $to): bool {
                return $sql === $expectedSql && $bindings === [$from, $to];
            })
            ->andReturn([]);

        DB::shouldReceive('connection')
            ->once()
            ->with('sap_sql')
            ->andReturn($connection);

        $rows = (new SapGrpoRepository)->fetch($from, $to);

        $this->assertSame([], $rows);
    }
}
