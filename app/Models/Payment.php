<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_invoice_id', 'method', 'gateway', 'gateway_reference', 'receipt_number',
    'status', 'amount', 'payment_url', 'proof_path', 'paid_at', 'metadata',
])]
class Payment extends Model
{
    public const STATUS_PENDING = 'menunggu';

    public const STATUS_PAID = 'lunas';

    public const STATUS_FAILED = 'gagal';

    public const STATUS_EXPIRED = 'kedaluwarsa';

    public const STATUS_CANCELLED = 'dibatalkan';

    protected function casts(): array
    {
        return ['amount' => 'integer', 'paid_at' => 'datetime', 'metadata' => 'array'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }
}
