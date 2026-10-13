<?php

namespace App\Services\Payments;

class PaymentGatewayManager
{
    public function default(): PaymentGateway
    {
        return $this->forProvider((string) config('payments.default'));
    }

    public function forProvider(string $provider): PaymentGateway
    {
        return match ($provider) {
            'midtrans' => new MidtransGateway,
            'xendit' => new XenditGateway,
            default => throw new PaymentGatewayException('Penyedia pembayaran tidak dikenal.'),
        };
    }
}
