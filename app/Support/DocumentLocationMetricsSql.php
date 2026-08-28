<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DocumentLocationMetricsSql
{
    public static function arrivalDateExpression(string $documentType): string
    {
        $modelClass = $documentType === 'invoice' ? 'App\\\\Models\\\\Invoice' : 'App\\\\Models\\\\AdditionalDocument';
        $table = $documentType === 'invoice' ? 'invoices' : 'additional_documents';
        $receiveColumn = $documentType === 'invoice' ? 'invoices.receive_date' : 'additional_documents.receive_date';
        $createdColumn = $documentType === 'invoice' ? 'invoices.created_at' : 'additional_documents.created_at';
        $documentIdColumn = $documentType === 'invoice' ? 'invoices.id' : 'additional_documents.id';

        return "COALESCE(
            (SELECT received_at FROM distributions 
             INNER JOIN distribution_documents ON distributions.id = distribution_documents.distribution_id
             WHERE distribution_documents.document_type = '{$modelClass}'
               AND distribution_documents.document_id = {$documentIdColumn}
               AND distribution_documents.receiver_verification_status = 'verified'
               AND distributions.received_at IS NOT NULL
             ORDER BY distributions.received_at DESC LIMIT 1),
            COALESCE({$receiveColumn}, {$createdColumn})
        )";
    }

    public static function daysInLocationExpression(string $documentType): string
    {
        $arrivalExpression = self::arrivalDateExpression($documentType);

        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST((julianday('now') - julianday({$arrivalExpression})) AS INTEGER)";
        }

        return "DATEDIFF(NOW(), {$arrivalExpression})";
    }
}
