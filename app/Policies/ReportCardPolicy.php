<?php

namespace App\Policies;

use App\Models\ReportCard;
use App\Models\User;

class ReportCardPolicy
{
    public function view(User $user, ReportCard $report): bool
    {
        $studentUserId = $report->student()->value('user_id');
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }
        if ($user->role === User::ROLE_SISWA) {
            return (int) $studentUserId === $user->id && $report->status === 'terbit';
        }
        if ($user->role === User::ROLE_ORANG_TUA) {
            return $report->status === 'terbit' && $user->children()->whereKey($studentUserId)->exists();
        }
        if ($user->role !== User::ROLE_GURU) {
            return false;
        }

        return $user->teacherProfile()->where(function ($teacher) use ($report): void {
            $teacher->whereHas('classes', fn ($classes) => $classes->whereKey($report->school_class_id))
                ->orWhereHas('homeroomClasses', fn ($classes) => $classes->whereKey($report->school_class_id));
        })->exists();
    }

    public function manage(User $user, ReportCard $report): bool
    {
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }
        if ($user->role !== User::ROLE_GURU) {
            return false;
        }

        return $user->teacherProfile()->whereHas('homeroomClasses', fn ($classes) => $classes->whereKey($report->school_class_id))->exists();
    }
}
