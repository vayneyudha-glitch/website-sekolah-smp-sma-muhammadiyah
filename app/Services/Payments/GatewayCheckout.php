<?php

namespace App\Services\Payments;

final readonly class GatewayCheckout
{
    public function __construct(
        public string $reference,
        public string $url,
        public ?string $providerId = null,
    ) {}
}
