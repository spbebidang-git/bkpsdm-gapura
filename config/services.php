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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sso' => [
        'base_url' => env('SSO_BASE_URL'),
        'api_key' => env('API_KEY'),
        'client_id' => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'admin_username' => env('SSO_ADMIN_USERNAME', 'elearning'),
        'super_admin_email' => env('PRESTIGE_SUPER_ADMIN_EMAIL', 'genz.developt@gmail.com'),
        'admin_pass' => env('SSO_ADMIN_PASS'),
        'categories_path' => env('SSO_CATEGORIES_PATH', '/api/get_kode_kegiatan'),
        'admin_nip' => env('SSO_ADMIN_NIP', 'elearning'),
    ],

    'kedaton' => [
        'base_url' => env('KEDATON_BASE_URL', 'https://kedaton.gresikkab.go.id'),
        'client_id' => env('KEDATON_CLIENT_ID'),
        'client_secret' => env('KEDATON_CLIENT_SECRET'),
        'redirect_uri' => env('KEDATON_REDIRECT_URI', rtrim((string) env('APP_URL', ''), '/') . '/auth/kedaton/callback'),
        'scope' => env('KEDATON_SCOPE'),
        'data_key' => env('KEDATON_DATA_KEY'),
        'data_type' => env('KEDATON_DATA_TYPE', 'pns'),
        'opd_data_type' => env('KEDATON_OPD_DATA_TYPE', 'perangkat'),
        'opd_master_path' => env('KEDATON_OPD_MASTER_PATH', '/api/get_data_api'),
        'opd_master_method' => env('KEDATON_OPD_MASTER_METHOD', 'post'),
        'opd_code_field' => env('KEDATON_OPD_CODE_FIELD', 'kode_uker'),
        'opd_name_field' => env('KEDATON_OPD_NAME_FIELD', 'nama_uker'),
        'opd_response_path' => env('KEDATON_OPD_RESPONSE_PATH', 'data'),
        'employee_opd_code_fields' => array_values(array_filter(array_map('trim', explode(',', env('KEDATON_EMPLOYEE_OPD_CODE_FIELDS', 'fc_kduker,uker_kode,fv_kduker'))))),
        'employee_opd_name_fields' => array_values(array_filter(array_map('trim', explode(',', env('KEDATON_EMPLOYEE_OPD_NAME_FIELDS', 'fv_nama_uker,fc_nama_uker,nama_uker,nama_opd,nama_unit_kerja'))))),
    ],

    // Removed kominfo static api_key

];
