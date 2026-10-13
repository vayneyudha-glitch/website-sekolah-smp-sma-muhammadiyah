<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'bill_type_id', 'student_profile_id', 'invoice_number', 'bill_type_name',
    'billing_period', 'amount', 'due_date', 'status',
])]
class StudentInvoice extends Model
{
    public const STATUS_UNPAID = 'belum_bayar';

    public const STATUS_PENDING = 'menunggu';

    public const STATUS_PAID = 'lunas';

    public const STATUS_EXPIRED = 'kedaluwarsa';

    public const STATUS_CANCELLED = 'dibatalkan';

    public const STATUSES = [
        self::STATUS_UNPAID,
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'due_date' => 'date'];
    }

    public function billType(): BelongsTo
    {
        return $this->belongsTo(BillType::class);
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
