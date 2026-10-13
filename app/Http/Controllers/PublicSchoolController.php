<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\View\View;

class PublicSchoolController extends Controller
{
    public function teachers(): View
    {
        return view('public.teachers', [
            'teachers' => TeacherProfile::query()
                ->whereHas('user', fn ($query) => $query->where('is_active', true)->where('role', User::ROLE_GURU))
                ->with(['user', 'classes.academicYear', 'homeroomClasses.academicYear', 'subjects'])
                ->latest()->paginate(18),
        ]);
    }

    public function announcements(): View
    {
        return view('public.announcements', [
            'announcements' => Announcement::query()->where('is_published', true)
                ->whereNull('audience_role')
                ->whereNotNull('published_at')->where('published_at', '<=', now())
                ->latest('published_at')->paginate(10),
        ]);
    }

    public function announcement(Announcement $announcement): View
    {
        abort_unless(
            $announcement->is_published
                && $announcement->audience_role === null
                && $announcement->published_at !== null
                && $announcement->published_at->lte(now()),
            404,
        );

        return view('public.announcement', ['announcement' => $announcement]);
    }
}
