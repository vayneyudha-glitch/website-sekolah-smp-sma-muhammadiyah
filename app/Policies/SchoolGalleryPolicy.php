<?php

namespace App\Policies;

use App\Models\SchoolGallery;
use App\Models\User;

class SchoolGalleryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === User::ROLE_ADMIN;
    }

    public function create(User $user): bool
    {
        return $user->role === User::ROLE_ADMIN;
    }

    public function delete(User $user, SchoolGallery $gallery): bool
    {
        return $user->role === User::ROLE_ADMIN;
    }
}
