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

    /*
    | Gộp Word tổng (xuất biên bản). Mặc định gộp XML trong PHP (ổn định, không cần Word).
    | WORD_MERGE_USE_WORD=true + Microsoft Word trên server → gộp bằng COM InsertFile.
    | WORD_MERGE_DISABLE=true → luôn tắt Word (giữ tương thích cũ).
    */
    'word_merge' => [
        'use_word' => env('WORD_MERGE_USE_WORD', false),
        'powershell' => env('WORD_MERGE_POWERSHELL'),
        'disable' => env('WORD_MERGE_DISABLE', false),
    ],

    /*
    | Xuất tổng biên bản: mặc định chỉ xuất từng file vào thư mục tung-file/ (+ ZIP tải về).
    | BIEN_BAN_TONG_GOP_FILE=true → sau đó gộp thêm 1 file Word tổng.
    */
    'clippit' => [
        'bin' => env('CLIPPIT_BIN'),
    ],

    'bien_ban_tong' => [
        'merge_files' => env('BIEN_BAN_TONG_GOP_FILE', false),
        // clippit (khuyến nghị) | word | xml | auto | none
        'merge_driver' => env('BIEN_BAN_TONG_MERGE_DRIVER', 'clippit'),
        'allow_xml_merge' => env('BIEN_BAN_TONG_ALLOW_XML_MERGE', false),
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
