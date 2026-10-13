<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'registration_number', 'student_name', 'nisn', 'birth_place',
    'birth_date', 'address', 'previous_school', 'guardian_name', 'guardian_relation',
    'guardian_phone', 'guardian_email', 'school_level', 'major', 'status',
    'decision_reason', 'school_class_id', 'submitted_at', 'decided_at',
    'privacy_consent_at', 'privacy_policy_version',
])]
class PpdbApplication extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return ['privacy_consent_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PpdbDocument::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }
}
