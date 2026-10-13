<?php

$sandbox = env('PAYMENTS_SANDBOX', true);
$sandbox = $sandbox === '' || $sandbox === null ? true : (bool) $sandbox;

return [
    'default' => env('PAYMENTS_DEFAULT_PROVIDER') ?: 'midtrans',
    'sandbox' => $sandbox,
    'currency' => env('PAYMENTS_CURRENCY') ?: 'IDR',

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'api_url' => env('MIDTRANS_API_URL') ?: ($sandbox
            ? 'https://app.sandbox.midtrans.com'
            : 'https://app.midtrans.com'),
    ],

    'xendit' => [
        'secret_key' => env('XENDIT_SECRET_KEY'),
        'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
        'api_url' => env('XENDIT_API_URL') ?: 'https://api.xendit.co',
    ],
];
