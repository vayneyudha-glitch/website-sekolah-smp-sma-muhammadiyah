<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['code', 'name'])]
class Subject extends Model
{
    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(TeacherProfile::class, 'subject_teacher')->withTimestamps();
    }
}
