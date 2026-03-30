<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openrouter' => [
        'key' => env('OPEN_ROUTER_API_KEY', env('OPENROUTER_API_KEY')),
        'model' => env('OPEN_ROUTER_MODEL', env('OPENROUTER_MODEL', 'openai/gpt-4o')),
        'base_url' => rtrim(env('OPEN_ROUTER_BASE_URL', env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1')), '/'),
        'timeout' => (int) env('OPEN_ROUTER_TIMEOUT', env('OPENROUTER_TIMEOUT', 120)),
        'pdf_timeout' => (int) env('OPEN_ROUTER_PDF_TIMEOUT', env('OPENROUTER_PDF_TIMEOUT', 240)),
        'pdf_engine' => env('OPEN_ROUTER_PDF_ENGINE', env('OPENROUTER_PDF_ENGINE', 'mistral-ocr')),
        'pdf_first_page_only' => filter_var(
            env('OPEN_ROUTER_PDF_FIRST_PAGE_ONLY', env('OPENROUTER_PDF_FIRST_PAGE_ONLY', true)),
            FILTER_VALIDATE_BOOL
        ),
        'enabled' => filter_var(env('INVOICE_IMPORT_ENABLED', true), FILTER_VALIDATE_BOOL),
        'extract_sync' => filter_var(env('INVOICE_IMPORT_EXTRACT_SYNC', false), FILTER_VALIDATE_BOOL),
        // Path to cacert.pem — fixes cURL error 60 on Windows when php.ini has no CA bundle (see OPEN_ROUTER_CAINFO in .env.example).
        'ca_bundle' => env('OPEN_ROUTER_CAINFO', env('OPENROUTER_CAINFO')),
    ],

    'sap' => [
        'ap_invoice' => [
            'default_item_code' => env('SAP_AP_INVOICE_DEFAULT_ITEM_CODE', 'SERVICE'),
            'default_payment_terms' => env('SAP_AP_INVOICE_DEFAULT_PAYMENT_TERMS', 30),
            'tax_codes' => [
                'default' => env('SAP_AP_INVOICE_DEFAULT_TAX_CODE', 'EXEMPT'),
                'by_currency' => [
                    'IDR' => env('SAP_AP_INVOICE_TAX_CODE_IDR', 'VAT11'),
                    'USD' => env('SAP_AP_INVOICE_TAX_CODE_USD', 'EXEMPT'),
                ],
                'by_invoice_type' => [
                    // Add mappings if needed
                ],
            ],
        ],
    ],

];
