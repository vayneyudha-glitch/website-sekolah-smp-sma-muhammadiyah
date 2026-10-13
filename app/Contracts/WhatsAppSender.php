<?php

namespace App\Contracts;

interface WhatsAppSender
{
    public function send(string $recipient, string $message): void;
}
