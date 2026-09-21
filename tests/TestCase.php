<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->ensureSqliteInMemoryDatabase();
    }

    protected function ensureSqliteInMemoryDatabase(): void
    {
        if ($this->nonSqliteTestsAllowed()) {
            return;
        }

        $defaultConnection = config('database.default');
        $database = config("database.connections.{$defaultConnection}.database");

        if ($defaultConnection === 'sqlite' && $database === ':memory:') {
            return;
        }

        $this->fail(sprintf(
            'Test database guard: koneksi default harus sqlite dengan database :memory: (saat ini: %s / %s). '
            .'Untuk sengaja memakai database lain di lokal, set ALLOW_NON_SQLITE_TESTS=1.',
            (string) $defaultConnection,
            (string) $database,
        ));
    }

    protected function nonSqliteTestsAllowed(): bool
    {
        $flag = getenv('ALLOW_NON_SQLITE_TESTS');

        if ($flag !== false && $flag !== '' && $flag !== '0') {
            return true;
        }

        if (isset($_ENV['ALLOW_NON_SQLITE_TESTS']) && $_ENV['ALLOW_NON_SQLITE_TESTS'] !== '' && $_ENV['ALLOW_NON_SQLITE_TESTS'] !== '0') {
            return true;
        }

        if (isset($_SERVER['ALLOW_NON_SQLITE_TESTS']) && $_SERVER['ALLOW_NON_SQLITE_TESTS'] !== '' && $_SERVER['ALLOW_NON_SQLITE_TESTS'] !== '0') {
            return true;
        }

        return false;
    }
}
