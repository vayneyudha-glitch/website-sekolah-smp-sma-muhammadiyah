<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\SchoolFacility;
use App\Models\SchoolGallery;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class PublicSchoolController extends Controller
{
    public function home(): View
    {
        return view('welcome', [
            'announcements' => $this->latestAnnouncements(),
            'facilities' => SchoolFacility::query()->orderBy('sort_order')->orderBy('name')->limit(3)->get(),
        ]);
    }

    public function page(string $page): View
    {
        $pages = [
            'tentang' => ['Tentang Sekolah', 'welcome_message'],
            'visi-misi' => ['Visi dan Misi', null],
            'sejarah' => ['Sejarah Sekolah', 'history'],
            'jenjang/smp' => ['Jenjang SMP', 'smp_description'],
            'jenjang/sma' => ['Jenjang SMA', 'sma_description'],
        ];
        abort_unless(isset($pages[$page]), 404);

        return view('public.page', [
            'title' => $pages[$page][0],
            'page' => $page,
            'contentField' => $pages[$page][1],
        ]);
    }

    public function facilities(): View
    {
        return view('public.facilities', [
            'facilities' => SchoolFacility::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function gallery(): View
    {
        return view('public.gallery', [
            'galleries' => SchoolGallery::query()->latest()->paginate(18),
        ]);
    }

    public function contact(): View
    {
        return view('public.contact');
    }

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
            'announcements' => $this->latestAnnouncements(10),
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

    private function latestAnnouncements(int $perPage = 5): LengthAwarePaginator
    {
        return Announcement::query()->where('is_published', true)
            ->whereNull('audience_role')
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->latest('published_at')->paginate($perPage);
    }
}
