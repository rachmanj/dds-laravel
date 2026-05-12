<?php

return [
    'http' => [
        'user_agent' => env('VENDOR_API_HTTP_USER_AGENT', 'DDS-Laravel-VendorInvoice/1.0'),
        'verify_ssl' => filter_var(env('VENDOR_API_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'vendors' => [
        'VCASJIDR01' => [
            'base_url' => env('VENDOR_VCASJIDR01_API_URL'),
            'token' => env('VENDOR_VCASJIDR01_API_TOKEN'),
            'type_id' => env('VENDOR_VCASJIDR01_TYPE_ID', 1),
            'cur_loc' => env('VENDOR_VCASJIDR01_CUR_LOC', '000HPROC'),
        ],
        'VCASAIDR01' => [
            'base_url' => env('VENDOR_VCASAIDR01_API_URL'),
            'token' => env('VENDOR_VCASAIDR01_API_TOKEN'),
            'type_id' => env('VENDOR_VCASAIDR01_TYPE_ID', 1),
            'cur_loc' => env('VENDOR_VCASAIDR01_CUR_LOC', '000HPROC'),
        ],
    ],
];
