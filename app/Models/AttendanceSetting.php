<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'id', 'latitude', 'longitude', 'radius_meters',
    'student_check_in_starts_at', 'student_check_in_ends_at',
    'teacher_check_in_starts_at', 'teacher_check_in_ends_at',
])]
class AttendanceSetting extends Model
{
    protected $table = 'attendance_settings';

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meters' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1]);
    }

    public function hasSchoolLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
