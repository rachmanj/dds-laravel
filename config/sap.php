<?php

return [
    'server_url' => env('SAP_SERVER_URL'),
    'db_name' => env('SAP_DB_NAME'),
    'user' => env('SAP_USER'),
    'password' => env('SAP_PASSWORD'),
    'query_ids' => [
        'list_ito' => 'list_ito',
    ],
    'invoice_mappings' => [
        'po_no' => 'Reference1', // Map Laravel po_no to SAP Reference1
        'vendor_id' => 'CardCode', // Assuming vendor_id in invoices maps to SAP CardCode
        'total_amount' => 'DocTotal', // Map total to DocTotal
        // Add more mappings as needed, e.g., 'due_date' => 'DocDueDate',
        // 'tax_amount' => 'VatSum',
        // etc.
    ],
];
