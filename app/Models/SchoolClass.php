<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year_id', 'name', 'grade', 'school_level', 'homeroom_teacher_profile_id'])]
class SchoolClass extends Model
{
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'homeroom_teacher_profile_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(TeacherProfile::class, 'class_teacher')->withTimestamps();
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }
}
