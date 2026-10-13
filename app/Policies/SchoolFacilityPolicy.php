<?php

namespace App\Policies;

use App\Models\SchoolFacility;
use App\Models\User;

class SchoolFacilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === User::ROLE_ADMIN;
    }

    public function create(User $user): bool
    {
        return $user->role === User::ROLE_ADMIN;
    }

    public function update(User $user, SchoolFacility $facility): bool
    {
        return $user->role === User::ROLE_ADMIN;
    }

    public function delete(User $user, SchoolFacility $facility): bool
    {
        return $user->role === User::ROLE_ADMIN;
    }
}
