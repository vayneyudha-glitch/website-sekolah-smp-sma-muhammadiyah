<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\StudentInvoice;

interface PaymentGateway
{
    public function isConfigured(): bool;

    public function createCheckout(StudentInvoice $invoice, Payment $payment, string $returnUrl, string $method): GatewayCheckout;

    /** @param array<string, mixed> $headers */
    public function verifyWebhook(string $rawBody, array $headers): bool;

    public function parseWebhook(string $rawBody): ?GatewayNotification;
}
