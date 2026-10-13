<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_profile_id', 'attendance_date', 'status', 'checked_in_at',
    'latitude', 'longitude', 'accuracy_meters', 'distance_meters',
    'reason', 'attachment_path', 'edited_by', 'edited_at', 'correction_reason', 'location_consent_at',
    'needs_review', 'review_status', 'suspicious_reasons', 'reviewed_by', 'reviewed_at', 'review_note',
    'device_hash',
])]
class StudentAttendance extends Model
{
    public const STATUS_PRESENT = 'hadir';

    public const STATUS_PERMISSION = 'izin';

    public const STATUS_SICK = 'sakit';

    public const STATUS_ABSENT = 'alpa';

    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_PERMISSION,
        self::STATUS_SICK,
        self::STATUS_ABSENT,
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'checked_in_at' => 'datetime',
            'edited_at' => 'datetime',
            'location_consent_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_meters' => 'float',
            'distance_meters' => 'float',
            'needs_review' => 'boolean',
            'suspicious_reasons' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
