<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AssignmentSubmission;
use App\Models\ClassAssignment;
use App\Models\Payment;
use App\Models\PpdbApplication;
use App\Models\SchoolClass;
use App\Models\StudentAttendance;
use App\Models\StudentInvoice;
use App\Models\StudentProfile;
use App\Models\TeacherAttendance;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(Request $request): View
    {
        $role = $request->user()->role;

        $announcements = Announcement::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn (Builder $query) => $query->whereNull('audience_role')->orWhere('audience_role', $role))
            ->latest('published_at')
            ->limit(5)
            ->get();

        $data = match ($role) {
            User::ROLE_ADMIN => $this->adminData(),
            User::ROLE_GURU => $this->teacherData($request->user()),
            User::ROLE_SISWA => $this->studentData($request->user()),
            User::ROLE_ORANG_TUA => $this->parentData($request->user()),
            default => [],
        };

        return view('dashboard', [
            'title' => 'Dashboard '.match ($role) {
                User::ROLE_ADMIN => 'Admin',
                User::ROLE_GURU => 'Guru',
                User::ROLE_SISWA => 'Siswa',
                default => 'Orang Tua',
            },
            'announcements' => $announcements,
            ...$data,
        ]);
    }

    private function adminData(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $paidThisMonth = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('amount');
        $unpaidInvoices = StudentInvoice::query()
            ->whereIn('status', [StudentInvoice::STATUS_UNPAID, StudentInvoice::STATUS_PENDING, StudentInvoice::STATUS_EXPIRED])
            ->whereDate('due_date', '<', today());
        $ppdbCounts = PpdbApplication::query()
            ->whereIn('status', [PpdbApplication::STATUS_SUBMITTED, PpdbApplication::STATUS_ACCEPTED, PpdbApplication::STATUS_REJECTED])
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
        $paymentChart = collect(range(5, 0))->mapWithKeys(function (int $monthsAgo) use ($monthNames): array {
            $start = now()->subMonths($monthsAgo)->startOfMonth();
            $total = Payment::query()
                ->where('status', Payment::STATUS_PAID)
                ->whereBetween('paid_at', [$start, $start->copy()->endOfMonth()])
                ->sum('amount');

            return [$monthNames[(int) $start->format('n')] => (int) $total];
        });

        return [
            'adminSummary' => [
                'students' => StudentProfile::query()->count(),
                'teachers' => User::query()->where('role', User::ROLE_GURU)->count(),
                'classes' => SchoolClass::query()->count(),
                'ppdb_waiting' => (int) ($ppdbCounts[PpdbApplication::STATUS_SUBMITTED] ?? 0),
                'ppdb_accepted' => (int) ($ppdbCounts[PpdbApplication::STATUS_ACCEPTED] ?? 0),
                'ppdb_rejected' => (int) ($ppdbCounts[PpdbApplication::STATUS_REJECTED] ?? 0),
                'attendance_today' => StudentAttendance::query()->whereDate('attendance_date', today())->count(),
                'payment_month' => (int) $paidThisMonth,
                'unpaid_count' => (clone $unpaidInvoices)->count(),
                'unpaid_amount' => (int) (clone $unpaidInvoices)->sum('amount'),
            ],
            'paymentChart' => $paymentChart,
        ];
    }

    private function teacherData(User $user): array
    {
        $teacher = $user->teacherProfile()->with(['classes', 'homeroomClasses'])->first();
        if ($teacher === null) {
            return ['todayTimetables' => collect(), 'teacherClasses' => collect(), 'assignmentCounts' => ['ungraded' => 0], 'ownAttendance' => null];
        }

        $todayTimetables = Timetable::query()
            ->with(['schoolClass', 'subject'])
            ->where('teacher_profile_id', $teacher->id)
            ->where('day_of_week', now()->dayOfWeekIso)
            ->whereHas('academicYear', fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('starts_at')
            ->get();

        $classIds = $teacher->classes->pluck('id')->merge($teacher->homeroomClasses->pluck('id'))->unique();
        $taughtCourses = Timetable::query()
            ->where('teacher_profile_id', $teacher->id)
            ->get(['school_class_id', 'subject_id']);
        $timetableClasses = SchoolClass::query()->whereIn('id', $taughtCourses->pluck('school_class_id'))->get();
        $ungraded = AssignmentSubmission::query()
            ->whereNull('graded_at')
            ->whereHas('assignment', function (Builder $query) use ($user, $classIds, $taughtCourses): void {
                $query->where(function (Builder $scope) use ($user, $classIds, $taughtCourses): void {
                    $scope->where('teacher_id', $user->id);
                    if ($classIds->isNotEmpty()) {
                        $scope->orWhereIn('school_class_id', $classIds);
                    }
                    foreach ($taughtCourses as $course) {
                        $scope->orWhere(fn (Builder $nested) => $nested
                            ->where('school_class_id', $course->school_class_id)
                            ->where('subject_id', $course->subject_id));
                    }
                });
            })->count();

        return [
            'todayTimetables' => $todayTimetables,
            'teacherClasses' => $teacher->classes->merge($teacher->homeroomClasses)->merge($timetableClasses)->unique('id')->values(),
            'assignmentCounts' => ['ungraded' => $ungraded],
            'ownAttendance' => TeacherAttendance::query()
                ->where('teacher_profile_id', $teacher->id)
                ->whereDate('attendance_date', today())
                ->first(),
        ];
    }

    private function studentData(User $user): array
    {
        $student = StudentProfile::query()->where('user_id', $user->id)->first();
        if ($student === null) {
            return ['todayTimetables' => collect(), 'upcomingAssignments' => collect(), 'latestGrades' => collect(), 'attendanceSummary' => [], 'unpaidInvoices' => collect(), 'assignmentCounts' => ['unsubmitted' => 0]];
        }

        $timetable = $this->timetablesForClass($student->school_class_id);

        return [
            'todayTimetables' => $timetable,
            'upcomingAssignments' => ClassAssignment::query()
                ->with(['subject', 'teacher'])
                ->where('school_class_id', $student->school_class_id)
                ->where('due_at', '>=', now())
                ->whereDoesntHave('submissions', fn (Builder $query) => $query->where('student_profile_id', $student->id))
                ->orderBy('due_at')
                ->limit(5)
                ->get(),
            'latestGrades' => AssignmentSubmission::query()
                ->with('assignment.subject')
                ->where('student_profile_id', $student->id)
                ->whereNotNull('graded_at')
                ->latest('graded_at')
                ->limit(5)
                ->get(),
            'attendanceSummary' => $this->attendanceSummary(collect([$student])),
            'unpaidInvoices' => StudentInvoice::query()
                ->where('student_profile_id', $student->id)
                ->whereIn('status', [StudentInvoice::STATUS_UNPAID, StudentInvoice::STATUS_PENDING, StudentInvoice::STATUS_EXPIRED])
                ->orderBy('due_date')
                ->limit(5)
                ->get(),
            'assignmentCounts' => ['unsubmitted' => ClassAssignment::query()
                ->where('school_class_id', $student->school_class_id)
                ->whereDoesntHave('submissions', fn (Builder $query) => $query->where('student_profile_id', $student->id))
                ->count()],
        ];
    }

    private function parentData(User $user): array
    {
        $children = $user->children()
            ->with(['studentProfile.schoolClass'])
            ->withCount([
                'studentProfile as present_this_month' => fn (Builder $query) => $query->whereHas('attendances', fn (Builder $attendances) => $attendances
                    ->where('status', StudentAttendance::STATUS_PRESENT)
                    ->whereBetween('attendance_date', [now()->startOfMonth(), today()])),
            ])
            ->get();
        $studentIds = $children->pluck('studentProfile.id')->filter()->values();
        $studentProfiles = StudentProfile::query()->whereIn('id', $studentIds)->get();
        $attendanceSummary = $this->attendanceSummary($studentProfiles);
        $recentGrades = AssignmentSubmission::query()
            ->with('assignment.subject')
            ->whereIn('student_profile_id', $studentIds)
            ->whereNotNull('graded_at')
            ->latest('graded_at')
            ->get()
            ->groupBy('student_profile_id');
        $invoices = StudentInvoice::query()
            ->whereIn('student_profile_id', $studentIds)
            ->whereIn('status', [StudentInvoice::STATUS_UNPAID, StudentInvoice::STATUS_PENDING, StudentInvoice::STATUS_EXPIRED])
            ->orderBy('due_date')
            ->get()
            ->groupBy('student_profile_id');
        $children->each(function (User $child) use ($recentGrades, $invoices, $attendanceSummary): void {
            $profileId = $child->studentProfile?->id;
            $child->setAttribute('recent_grades', $recentGrades->get($profileId, collect())->take(3));
            $child->setAttribute('unpaid_invoices', $invoices->get($profileId, collect())->take(3));
            $child->setAttribute('attendance_summary', $attendanceSummary->get($profileId, collect()));
        });

        return [
            'childrenSummary' => $children,
            'attendanceSummary' => $attendanceSummary,
            'todayTimetables' => $this->timetablesForClassIds($studentProfiles->pluck('school_class_id')->unique()),
        ];
    }

    private function timetablesForClass(int $classId): Collection
    {
        return $this->timetablesForClassIds(collect([$classId]));
    }

    private function timetablesForClassIds(Collection $classIds): Collection
    {
        if ($classIds->isEmpty()) {
            return collect();
        }

        return Timetable::query()
            ->with(['schoolClass', 'subject', 'teacher.user'])
            ->whereIn('school_class_id', $classIds)
            ->where('day_of_week', now()->dayOfWeekIso)
            ->whereHas('academicYear', fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('starts_at')
            ->get();
    }

    private function attendanceSummary(Collection $students): Collection
    {
        $studentIds = $students->pluck('id')->filter()->values();
        if ($studentIds->isEmpty()) {
            return collect();
        }

        return StudentAttendance::query()
            ->whereIn('student_profile_id', $studentIds)
            ->whereBetween('attendance_date', [now()->startOfMonth(), today()])
            ->selectRaw('student_profile_id, status, count(*) as aggregate')
            ->groupBy('student_profile_id', 'status')
            ->get()
            ->groupBy('student_profile_id')
            ->map(fn (Collection $rows) => $rows->pluck('aggregate', 'status'));
    }
}
