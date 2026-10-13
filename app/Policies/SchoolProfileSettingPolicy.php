<?php

namespace App\Policies;

use App\Models\SchoolProfileSetting;
use App\Models\User;

class SchoolProfileSettingPolicy
{
    public function update(User $user, SchoolProfileSetting $setting): bool
    {
        return $user->role === User::ROLE_ADMIN;
    }
}
