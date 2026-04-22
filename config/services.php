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

    'domain_assistant' => [
        'enabled' => filter_var(env('DOMAIN_ASSISTANT_ENABLED', false), FILTER_VALIDATE_BOOL),
        'tools_enabled' => filter_var(env('DOMAIN_ASSISTANT_TOOLS_ENABLED', true), FILTER_VALIDATE_BOOL),
        'streaming_enabled' => filter_var(env('DOMAIN_ASSISTANT_STREAMING_ENABLED', true), FILTER_VALIDATE_BOOL),
        // Max user messages per calendar day (0 = unlimited). Counts assistant chat user turns only.
        'daily_user_message_limit' => (int) env('DOMAIN_ASSISTANT_DAILY_USER_LIMIT', 0),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'assistant_enabled' => filter_var(env('TELEGRAM_ASSISTANT_ENABLED', false), FILTER_VALIDATE_BOOL),
        // When true, process Telegram assistant messages in the webhook request (no queue worker). Set false + run queue:work for scale.
        'dispatch_sync' => filter_var(env('TELEGRAM_ASSISTANT_DISPATCH_SYNC', true), FILTER_VALIDATE_BOOL),
        // When true (and user has see-all-record-switch), Telegram uses expanded invoice/additional-document scope — same as web with “Show all records” on. Default false aligns with web default (checkbox off).
        'expand_all_locations' => filter_var(env('TELEGRAM_ASSISTANT_EXPAND_ALL_LOCATIONS', false), FILTER_VALIDATE_BOOL),
    ],

    'openrouter' => [
        'key' => env('OPEN_ROUTER_API_KEY', env('OPENROUTER_API_KEY')),
        'model' => env('OPEN_ROUTER_MODEL', env('OPENROUTER_MODEL', 'openai/gpt-4o')),
        'chat_model' => env('OPEN_ROUTER_CHAT_MODEL', env('OPENROUTER_CHAT_MODEL')),
        'chat_timeout' => (int) env('OPEN_ROUTER_CHAT_TIMEOUT', env('OPENROUTER_CHAT_TIMEOUT', 120)),
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
        // Max upload size for invoice import (kilobytes). Laravel validation "max" for files uses KB. Also raise PHP upload_max_filesize / post_max_size and web server limits to match.
        'max_upload_kb' => max(1, (int) env('INVOICE_IMPORT_MAX_FILE_KB', env('OPENROUTER_MAX_UPLOAD_KB', 51200))),
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

    'solar_price_scheduler' => [
        'creator_user_id' => env('SOLAR_PRICE_SCHEDULER_USER_ID'),
        'timezone' => env('SOLAR_PRICE_SCHEDULER_TIMEZONE', 'Asia/Makassar'),
    ],

];
