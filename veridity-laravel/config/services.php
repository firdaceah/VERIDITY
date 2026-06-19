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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'veridity' => [
        'python_engine_url' => env('PYTHON_ENGINE_URL', 'http://127.0.0.1:8001'),
        'python_path' => env('PYTHON_PATH'),
        'python_toolkit_script' => env('PYTHON_TOOLKIT_SCRIPT'),
        'distri_integration_key' => env('DISTRIBUTOR_API_KEY', env('VERIDITY_INTEGRATION_KEY')),
        'integration_user_id' => env('VERIDITY_INTEGRATION_USER_ID'),
        'tesseract_path' => env('TESSERACT_PATH', 'tesseract'),
        'send_reset_email' => env('VERIDITY_SEND_RESET_EMAIL', false),
    ],

];
