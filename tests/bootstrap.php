<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../bootstrap/polyfills.php';

/**
 * @return list<string>
 */
function tests_env_values(string $name): array
{
    $values = [];

    if (isset($_ENV[$name]) && $_ENV[$name] !== false && $_ENV[$name] !== '') {
        $values[] = (string) $_ENV[$name];
    }

    if (isset($_SERVER[$name]) && $_SERVER[$name] !== false && $_SERVER[$name] !== '') {
        $values[] = (string) $_SERVER[$name];
    }

    $fromGetenv = getenv($name);
    if ($fromGetenv !== false && $fromGetenv !== '') {
        $values[] = (string) $fromGetenv;
    }

    return array_values(array_unique($values));
}

function tests_guard_against_production_database(): void
{
    $connections = tests_env_values('DB_CONNECTION');
    $hosts = tests_env_values('DB_HOST');
    $databases = tests_env_values('DB_DATABASE');

    $hasNonSqliteConnection = false;
    foreach ($connections as $connection) {
        if (strtolower($connection) !== 'sqlite') {
            $hasNonSqliteConnection = true;
            break;
        }
    }

    $localHosts = ['localhost', '127.0.0.1', '::1'];
    $hasNonLocalHost = false;
    foreach ($hosts as $host) {
        if (! in_array(strtolower($host), $localHosts, true)) {
            $hasNonLocalHost = true;
            break;
        }
    }

    $hasProductionDatabase = false;
    foreach ($databases as $database) {
        if (stripos($database, 'dds_backend') !== false || stripos($database, 'esd_backend') !== false) {
            $hasProductionDatabase = true;
            break;
        }
    }

    if (! $hasNonSqliteConnection || (! $hasNonLocalHost && ! $hasProductionDatabase)) {
        return;
    }

    $displayConnection = $connections !== [] ? implode(', ', $connections) : '(tidak ada)';
    $displayHost = $hosts !== [] ? implode(', ', $hosts) : '(tidak ada)';
    $displayDatabase = $databases !== [] ? implode(', ', $databases) : '(tidak ada)';

    fwrite(STDERR, <<<MESSAGE
PENGUJIAN DIHENTIKAN: konfigurasi database tidak aman untuk test.

Nilai yang terbaca:
  DB_CONNECTION = {$displayConnection}
  DB_HOST       = {$displayHost}
  DB_DATABASE   = {$displayDatabase}

Test hanya boleh memakai SQLite in-memory (:memory:). Jangan jalankan test dengan variabel lingkungan database produksi atau server jarak jauh.

Bersihkan sesi shell Anda dengan:
  unset DB_CONNECTION DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD

MESSAGE);

    exit(1);
}

tests_guard_against_production_database();
