<?php

return [
    'provider' => env('WHATSAPP_PROVIDER') ?: 'log',
    'providers' => [
        'log' => App\Services\WhatsApp\LogWhatsAppSender::class,
    ],
];
