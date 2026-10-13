<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function index(): View
    {
        $timetables = Timetable::query()
            ->with(['academicYear', 'schoolClass', 'subject', 'teacher.user'])
            ->whereHas('academicYear', fn ($query) => $query->where('is_active', true))
            ->orderBy('day_of_week')->orderBy('starts_at')->get()
            ->filter(fn (Timetable $timetable): bool => Gate::allows('view-timetable', $timetable));

        return view('timetables.index', ['timetables' => $timetables]);
    }
}
