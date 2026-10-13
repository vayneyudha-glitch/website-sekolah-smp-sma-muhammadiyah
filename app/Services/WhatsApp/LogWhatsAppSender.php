<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppSender;
use Illuminate\Support\Facades\Log;

class LogWhatsAppSender implements WhatsAppSender
{
    public function send(string $recipient, string $message): void
    {
        Log::info('WhatsApp notification queued for provider integration.', [
            'recipient_hash' => hash('sha256', $recipient),
            'message_length' => mb_strlen($message),
        ]);
    }
}
