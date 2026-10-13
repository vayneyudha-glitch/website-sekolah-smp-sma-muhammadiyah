<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolPortalController extends Controller
{
    public function student(): View
    {
        $student = StudentProfile::query()
            ->with(['user', 'schoolClass.academicYear'])
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        Gate::authorize('view-student-record', $student);

        return view('portal.student', ['student' => $student]);
    }

    public function children(): View
    {
        $children = request()->user()->children()
            ->with(['studentProfile.schoolClass.academicYear'])
            ->get()
            ->filter(fn ($child): bool => $child->studentProfile !== null
                && Gate::allows('view-student-record', $child->studentProfile));

        return view('portal.children', ['children' => $children]);
    }

    public function teacherClasses(): View
    {
        $teacher = request()->user()->teacherProfile()
            ->with([
                'classes.academicYear',
                'classes.students.user',
                'homeroomClasses.academicYear',
                'homeroomClasses.students.user',
            ])
            ->firstOrFail();

        $classes = $teacher->classes->merge($teacher->homeroomClasses)
            ->unique('id')->values()->each(function ($class): void {
            $class->setRelation(
                'students',
                $class->students->filter(fn (StudentProfile $student): bool => Gate::allows('view-student-record', $student)),
            );
        });

        return view('portal.teacher-classes', ['classes' => $classes]);
    }
}
