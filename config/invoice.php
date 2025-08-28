<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice Payment Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for invoice payment management.
    |
    */

    // Number of days after which an invoice is considered overdue for payment
    'payment_overdue_days' => env('INVOICE_PAYMENT_OVERDUE_DAYS', 30),

    // Default payment date when marking invoices as paid (current date)
    'default_payment_date' => now()->format('Y-m-d'),

    // Payment status options
    'payment_statuses' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
    ],

    // Invoice status options
    'statuses' => [
        'open' => 'Open',
        'verify' => 'Verify',
        'return' => 'Return',
        'sap' => 'SAP',
        'close' => 'Close',
        'cancel' => 'Cancel',
    ],
];
