<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'provider', 'raw_body', 'signature_valid', 'processing_status',
    'error_message', 'processed_at',
])]
class PaymentWebhookLog extends Model
{
    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
