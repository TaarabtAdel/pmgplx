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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Ảnh chân dung file DKKH (JPEG2000) — công cụ chuyển sang PNG.
    | Mặc định dùng laravel/bin/opj_decompress.exe trên Windows.
    */
    'jp2' => [
        'decompress_bin' => env('JP2_DECOMPRESS_BIN'),
    ],

    'xeonline' => [
        'base_url' => env('XEONLINE_API_BASE_URL', 'http://117.2.146.185:7782'),
        'timeout' => (int) env('XEONLINE_API_TIMEOUT', 30),
    ],

    'nominatim' => [
        'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
        'user_agent' => env('NOMINATIM_USER_AGENT', 'khgplx-dat/1.0'),
        'timeout' => (int) env('NOMINATIM_TIMEOUT', 10),
    ],

];
