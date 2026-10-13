<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_profile_id', 'requested_by', 'attendance_date', 'type', 'reason',
    'attachment_path', 'status', 'reviewed_by', 'review_note', 'reviewed_at',
])]
class ExcuseRequest extends Model
{
    public const STATUS_PENDING = 'menunggu';

    public const STATUS_APPROVED = 'disetujui';

    public const STATUS_REJECTED = 'ditolak';

    protected function casts(): array
    {
        return ['attendance_date' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
