<?php

namespace App\Exceptions;

use Exception;

class SapSqlQueryException extends Exception
{
    public static function connectionFailed(string $message): self
    {
        return new self('Gagal mengambil data dari SAP (koneksi sap_sql): '.$message);
    }
}
