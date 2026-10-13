<?php

namespace App\Policies;

use App\Models\ExcuseRequest;
use App\Models\User;

class ExcuseRequestPolicy
{
    public function view(User $user, ExcuseRequest $request): bool
    {
        return $this->isRequester($user, $request)
            || $this->canReview($user, $request);
    }

    public function review(User $user, ExcuseRequest $request): bool
    {
        return $this->canReview($user, $request);
    }

    private function isRequester(User $user, ExcuseRequest $request): bool
    {
        $studentUserId = $request->student()->value('user_id');

        return ($user->role === User::ROLE_SISWA && $studentUserId === $user->id)
            || ($user->role === User::ROLE_ORANG_TUA && $user->children()->whereKey($studentUserId)->exists());
    }

    private function canReview(User $user, ExcuseRequest $request): bool
    {
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }
        if ($user->role !== User::ROLE_GURU) {
            return false;
        }

        $classId = $request->student()->value('school_class_id');

        return $user->teacherProfile()->where(function ($query) use ($classId): void {
            $query->whereHas('classes', fn ($classes) => $classes->whereKey($classId))
                ->orWhereHas('homeroomClasses', fn ($classes) => $classes->whereKey($classId));
        })->exists();
    }
}
