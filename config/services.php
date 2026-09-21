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
        'admin_username' => env('SSO_ADMIN_USERNAME'),
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
    ],

    // Removed kominfo static api_key

];
