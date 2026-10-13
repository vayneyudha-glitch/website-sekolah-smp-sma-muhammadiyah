<?php

namespace App\Services\Payments;

final readonly class GatewayNotification
{
    public function __construct(
        public string $reference,
        public string $status,
        public float $amount,
        public ?string $currency = null,
    ) {}
}
