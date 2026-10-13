<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'teacher_profile_id', 'attendance_date', 'check_in_at',
    'check_in_latitude', 'check_in_longitude', 'check_in_accuracy_meters',
    'check_in_ip_address', 'check_in_device_hash', 'check_in_selfie_path',
    'check_out_at', 'check_out_latitude', 'check_out_longitude',
    'check_out_accuracy_meters', 'check_out_ip_address', 'check_out_device_hash',
    'check_out_selfie_path', 'is_suspicious', 'suspicious_reasons',
    'check_in_location_consent_at', 'check_out_location_consent_at',
    'review_status', 'reviewed_by', 'reviewed_at', 'review_note',
])]
class TeacherAttendance extends Model
{
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_location_consent_at' => 'datetime',
            'check_out_location_consent_at' => 'datetime',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
            'check_in_accuracy_meters' => 'float',
            'check_out_latitude' => 'float',
            'check_out_longitude' => 'float',
            'check_out_accuracy_meters' => 'float',
            'is_suspicious' => 'boolean',
            'suspicious_reasons' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }
}
