<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AssignmentSubmission;
use App\Models\AuditLog;
use App\Models\ClassAssignment;
use App\Models\GradeComponent;
use App\Models\GradeScore;
use App\Models\GradingBand;
use App\Models\ReportCard;
use App\Models\ReportCardItem;
use App\Models\SchoolClass;
use App\Models\SchoolProfileSetting;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportCardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user->role === User::ROLE_SISWA) {
            $profile = $user->studentProfile()->firstOrFail();
            $reports = $profile->reportCards()->with('academicYear')->where('status', 'terbit')->latest()->get();
        } elseif ($user->role === User::ROLE_ORANG_TUA) {
            $studentIds = $user->children()->pluck('users.id');
            $reports = ReportCard::query()->with(['student.user', 'academicYear'])
                ->where('status', 'terbit')
                ->whereHas('student', fn ($query) => $query->whereIn('user_id', $studentIds))
                ->latest()->get();
        } else {
            abort_unless(in_array($user->role, [User::ROLE_ADMIN, User::ROLE_GURU], true), 403);
            $teacher = $user->role === User::ROLE_GURU ? $user->teacherProfile()->firstOrFail() : null;
            $classIds = $teacher === null ? null : $teacher->homeroomClasses()->pluck('school_classes.id');
            $reports = ReportCard::query()->with(['student.user', 'academicYear'])
                ->when($classIds !== null, fn ($query) => $query->whereIn('school_class_id', $classIds))
                ->latest()->paginate(30);
            $classes = $teacher === null
                ? SchoolClass::query()->with('academicYear')->orderByDesc('academic_year_id')->orderBy('name')->get()
                : $teacher->classes()->with('academicYear')->get()
                    ->merge($teacher->homeroomClasses()->with('academicYear')->get())->unique('id')->values();

            $courses = Timetable::query()->with(['schoolClass.academicYear', 'subject'])
                ->when($teacher !== null, fn ($query) => $query->where('teacher_profile_id', $teacher->id))
                ->get()
                ->unique(fn (Timetable $course): string => $course->school_class_id.':'.$course->subject_id)
                ->values();

            return view('reports.index', ['reports' => $reports, 'classes' => $classes, 'teacher' => $teacher, 'courses' => $courses]);
        }

        return view('reports.index', ['reports' => $reports, 'classes' => collect(), 'teacher' => null, 'courses' => collect()]);
    }

    public function manage(Request $request, SchoolClass $schoolClass, Subject $subject): View
    {
        $this->authorizeSubject($request->user(), $schoolClass, $subject);
        $components = GradeComponent::query()
            ->where('academic_year_id', $schoolClass->academic_year_id)
            ->where('subject_id', $subject->id)
            ->with(['scores.student.user'])
            ->orderBy('id')->get();
        $students = $schoolClass->students()->with(['user', 'gradeScores.component'])
            ->where('status', 'aktif')->orderBy('id')->get();
        $assignments = ClassAssignment::query()->with(['submissions.student.user'])
            ->where('school_class_id', $schoolClass->id)
            ->where('subject_id', $subject->id)->get();
        $reports = ReportCard::query()->with('items.subject')
            ->where('academic_year_id', $schoolClass->academic_year_id)
            ->whereIn('student_profile_id', $students->pluck('id'))->get()->keyBy('student_profile_id');
        $isHomeroom = $request->user()->role === User::ROLE_ADMIN
            || $request->user()->teacherProfile()->whereHas('homeroomClasses', fn ($query) => $query->whereKey($schoolClass->id))->exists();
        $hasPublishedSubject = ReportCard::query()
            ->where('academic_year_id', $schoolClass->academic_year_id)
            ->where('status', 'terbit')
            ->whereHas('items', fn ($query) => $query->where('subject_id', $subject->id))
            ->exists();
        $hasPublishedYearReports = ReportCard::query()
            ->where('academic_year_id', $schoolClass->academic_year_id)
            ->where('status', 'terbit')->exists();

        return view('reports.manage', [
            'schoolClass' => $schoolClass->load('academicYear'),
            'subject' => $subject,
            'components' => $components,
            'students' => $students,
            'assignments' => $assignments,
            'reports' => $reports,
            'bands' => GradingBand::query()->where('academic_year_id', $schoolClass->academic_year_id)->orderByDesc('minimum_score')->get(),
            'isHomeroom' => $isHomeroom,
            'hasPublishedSubject' => $hasPublishedSubject,
            'hasPublishedYearReports' => $hasPublishedYearReports,
        ]);
    }

    public function saveComponents(Request $request, SchoolClass $schoolClass, Subject $subject): RedirectResponse
    {
        $this->authorizeSubject($request->user(), $schoolClass, $subject);
        $data = $request->validate([
            'components' => ['required', 'array', 'min:1', 'max:12'],
            'components.*.name' => ['required', 'string', 'max:100'],
            'components.*.weight' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $published = ReportCard::query()->where('academic_year_id', $schoolClass->academic_year_id)
            ->where('status', 'terbit')
            ->whereHas('items', fn ($query) => $query->where('subject_id', $subject->id));
        if ($published->exists() && blank($data['reason'] ?? null)) {
            throw ValidationException::withMessages(['reason' => 'Perubahan bobot setelah rapor terbit wajib disertai alasan.']);
        }
        $names = collect($data['components'])->pluck('name')->map(fn ($name) => mb_strtolower(trim($name)));
        if ($names->unique()->count() !== $names->count()
            || abs((float) collect($data['components'])->sum('weight') - 100.0) > 0.01) {
            throw ValidationException::withMessages(['components' => 'Nama komponen harus unik dan jumlah bobot harus tepat 100%.']);
        }

        DB::transaction(function () use ($data, $schoolClass, $subject, $published, $request): void {
            $submittedNames = [];
            foreach ($data['components'] as $component) {
                $name = trim($component['name']);
                $submittedNames[] = $name;
                GradeComponent::query()->updateOrCreate(
                    [
                        'academic_year_id' => $schoolClass->academic_year_id,
                        'subject_id' => $subject->id,
                        'name' => $name,
                    ],
                    ['weight' => $component['weight']],
                );
            }
            GradeComponent::query()
                ->where('academic_year_id', $schoolClass->academic_year_id)
                ->where('subject_id', $subject->id)
                ->whereNotIn('name', $submittedNames)
                ->whereDoesntHave('scores')
                ->delete();
            if ($published->exists()) {
                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'updated',
                    'record_type' => 'report_cards',
                    'description' => 'Mengubah komponen atau bobot rapor yang telah terbit dengan alasan.',
                    'metadata' => ['reason' => $data['reason']],
                    'ip_address' => $request->ip(),
                ]);
            }
        });

        return back()->with('status', 'Komponen dan bobot nilai berhasil disimpan.');
    }

    public function saveScores(Request $request, SchoolClass $schoolClass, Subject $subject): RedirectResponse
    {
        $this->authorizeSubject($request->user(), $schoolClass, $subject);
        $data = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*.*' => ['nullable', 'numeric', 'between:0,100'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $publishedReports = ReportCard::query()
            ->where('academic_year_id', $schoolClass->academic_year_id)
            ->where('status', 'terbit')
            ->whereHas('student', fn ($query) => $query->where('school_class_id', $schoolClass->id));
        if ($publishedReports->exists() && blank($data['reason'] ?? null)) {
            throw ValidationException::withMessages(['reason' => 'Perubahan nilai setelah rapor terbit wajib disertai alasan.']);
        }
        $students = $schoolClass->students()->where('status', 'aktif')->pluck('id')->map(fn ($id) => (int) $id);
        $components = GradeComponent::query()
            ->where('academic_year_id', $schoolClass->academic_year_id)
            ->where('subject_id', $subject->id)
            ->get()->keyBy('id');

        DB::transaction(function () use ($request, $data, $students, $components, $publishedReports): void {
            $changedScores = 0;
            foreach ($data['scores'] as $componentId => $values) {
                $component = $components->get((int) $componentId);
                abort_if($component === null, 422, 'Komponen nilai tidak sesuai dengan kelas dan mata pelajaran.');
                foreach ($values as $studentId => $score) {
                    abort_unless($students->contains((int) $studentId), 403);
                    $query = GradeScore::query()
                        ->where('grade_component_id', $component->id)
                        ->where('student_profile_id', $studentId);
                    if ($score === null || $score === '') {
                        $changedScores += $query->delete();
                        continue;
                    }
                    GradeScore::query()->updateOrCreate([
                        'grade_component_id' => $component->id,
                        'student_profile_id' => (int) $studentId,
                    ], [
                        'score' => $score,
                        'entered_by' => $request->user()->id,
                    ]);
                    $changedScores++;
                }
            }
            if ($changedScores > 0 && $publishedReports->exists()) {
                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'updated',
                    'record_type' => 'report_cards',
                    'description' => 'Mengubah nilai sumber rapor yang telah terbit dengan alasan.',
                    'metadata' => ['reason' => $data['reason'], 'score_changes' => $changedScores],
                    'ip_address' => $request->ip(),
                ]);
            }
        });

        return back()->with('status', 'Nilai murid berhasil disimpan.');
    }

    public function saveBands(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        abort_unless($request->user()->role === User::ROLE_ADMIN, 403);
        $data = $request->validate([
            'bands' => ['required', 'array', 'min:1', 'max:10'],
            'bands.*.minimum_score' => ['required', 'numeric', 'between:0,100'],
            'bands.*.predicate' => ['required', 'string', 'max:20'],
            'bands.*.description' => ['required', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $published = ReportCard::query()->where('academic_year_id', $schoolClass->academic_year_id)
            ->where('status', 'terbit');
        if ($published->exists() && blank($data['reason'] ?? null)) {
            throw ValidationException::withMessages(['reason' => 'Perubahan predikat setelah rapor terbit wajib disertai alasan.']);
        }
        $minimums = collect($data['bands'])->pluck('minimum_score')->map(fn ($score) => (float) $score);
        if ($minimums->unique()->count() !== $minimums->count() || ! $minimums->contains(0.0)) {
            throw ValidationException::withMessages(['bands' => 'Rentang predikat harus unik dan memiliki nilai minimum 0.']);
        }

        DB::transaction(function () use ($data, $schoolClass, $published, $request): void {
            GradingBand::query()->where('academic_year_id', $schoolClass->academic_year_id)->delete();
            foreach ($data['bands'] as $band) {
                GradingBand::query()->create(['academic_year_id' => $schoolClass->academic_year_id] + $band);
            }
            if ($published->exists()) {
                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'updated',
                    'record_type' => 'report_cards',
                    'description' => 'Mengubah predikat rapor yang telah terbit dengan alasan.',
                    'metadata' => ['reason' => $data['reason']],
                    'ip_address' => $request->ip(),
                ]);
            }
        });

        return back()->with('status', 'Predikat dan deskripsi nilai berhasil disimpan.');
    }

    public function saveClassReports(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorizeHomeroom($request->user(), $schoolClass);
        $data = $request->validate([
            'action' => ['required', 'in:draf,terbit'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $students = $schoolClass->students()->where('status', 'aktif')->orderBy('id')->get();
        $subjects = Subject::query()->whereHas('timetables', fn ($query) => $query
            ->where('school_class_id', $schoolClass->id)
            ->where('academic_year_id', $schoolClass->academic_year_id))->get();

        if ($subjects->isEmpty()) {
            throw ValidationException::withMessages(['report' => 'Kelas belum memiliki mata pelajaran pada tahun ajaran ini.']);
        }
        foreach ($students as $student) {
            $existing = ReportCard::query()
                ->where('student_profile_id', $student->id)
                ->where('academic_year_id', $schoolClass->academic_year_id)
                ->first();
            if ($existing?->status === 'terbit' && blank($data['reason'] ?? null)) {
                throw ValidationException::withMessages(['reason' => 'Perubahan rapor terbit wajib disertai alasan.']);
            }
            if ($existing?->status === 'terbit' && $data['action'] !== 'terbit') {
                throw ValidationException::withMessages(['action' => 'Rapor yang telah terbit tidak dapat dibuka kembali sebagai draf.']);
            }
        }

        DB::transaction(function () use ($request, $schoolClass, $students, $subjects, $data): void {
            foreach ($students as $student) {
                $report = ReportCard::query()->firstOrNew([
                    'student_profile_id' => $student->id,
                    'academic_year_id' => $schoolClass->academic_year_id,
                ]);
                if (! $report->exists) {
                    $report->school_class_id = $schoolClass->id;
                }
                $wasPublished = $report->status === 'terbit';
                $report->fill([
                    'homeroom_note' => $data['notes'][$student->id] ?? null,
                    'status' => $data['action'],
                    'updated_by' => $request->user()->id,
                    'published_at' => $data['action'] === 'terbit' ? now() : null,
                ])->save();

                $items = [];
                foreach ($subjects as $subject) {
                    $result = $this->finalGrade($student, $schoolClass, $subject);
                    if ($result === null) {
                        continue;
                    }
                    $items[] = [
                        'report_card_id' => $report->id,
                        'subject_id' => $subject->id,
                        'final_score' => $result['score'],
                        'predicate' => $result['predicate'],
                        'description' => $result['description'],
                    ];
                }
                if ($data['action'] === 'terbit' && $items === []) {
                    throw ValidationException::withMessages([
                        'report' => 'Tidak ada nilai yang dapat diterbitkan untuk seluruh murid kelas ini.',
                    ]);
                }
                $report->items()->delete();
                foreach ($items as $item) {
                    ReportCardItem::query()->create($item);
                }
                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => $wasPublished ? 'updated' : ($report->status === 'terbit' ? 'published' : 'created'),
                    'record_type' => ReportCard::class,
                    'record_id' => $report->id,
                    'description' => $wasPublished
                        ? 'Mengubah rapor yang telah terbit dengan alasan.'
                        : ($report->status === 'terbit' ? 'Menerbitkan rapor.' : 'Menyimpan draf rapor.'),
                    'metadata' => array_filter([
                        'status' => $report->status,
                        'reason' => $wasPublished ? $data['reason'] : null,
                    ]),
                    'ip_address' => $request->ip(),
                ]);
                if (! $wasPublished && $report->status === 'terbit') {
                    $this->notifyPublishedReport($report);
                }
            }
        });

        return back()->with('status', 'Rapor kelas berhasil diperbarui.');
    }

    public function homeroom(Request $request, SchoolClass $schoolClass): View
    {
        $this->authorizeHomeroom($request->user(), $schoolClass);

        return view('reports.homeroom', [
            'schoolClass' => $schoolClass->load('academicYear'),
            'students' => $schoolClass->students()->with(['user', 'reportCards' => fn ($query) => $query
                ->where('academic_year_id', $schoolClass->academic_year_id)])->where('status', 'aktif')->orderBy('id')->get(),
        ]);
    }

    public function studentReports(Request $request, ?User $student = null): View
    {
        $user = $request->user();
        if ($user->role === User::ROLE_SISWA) {
            $profile = $user->studentProfile()->firstOrFail();
        } else {
            abort_unless($user->role === User::ROLE_ORANG_TUA && $student !== null
                && $user->children()->whereKey($student->id)->exists(), 403);
            $profile = $student->studentProfile()->firstOrFail();
        }
        $reports = $profile->reportCards()->with(['academicYear', 'items.subject'])
            ->where('status', 'terbit')->latest()->get();

        return view('reports.student', ['student' => $profile->load('user'), 'reports' => $reports]);
    }

    public function pdf(Request $request, ReportCard $report): mixed
    {
        Gate::authorize('view', $report);
        abort_unless($report->status === 'terbit' || in_array($request->user()->role, [User::ROLE_ADMIN, User::ROLE_GURU], true), 404);
        $report->load(['student.user', 'schoolClass', 'academicYear', 'items.subject']);

        return Pdf::loadView('reports.pdf', [
            'report' => $report,
            'schoolProfile' => SchoolProfileSetting::query()->first(),
            'attendance' => $report->student->attendances()
                ->whereBetween('attendance_date', [
                    $report->academicYear->starts_on->toDateString(),
                    $report->academicYear->ends_on->toDateString(),
                ])->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ])->download('rapor-'.$report->student->user->id.'-'.$report->academicYear->name.'.pdf');
    }

    private function authorizeSubject(User $user, SchoolClass $schoolClass, Subject $subject): void
    {
        abort_unless($user->role === User::ROLE_ADMIN || ($user->role === User::ROLE_GURU
            && Timetable::query()
                ->where('teacher_profile_id', $user->teacherProfile()->value('id'))
                ->where('school_class_id', $schoolClass->id)
                ->where('subject_id', $subject->id)
                ->where('academic_year_id', $schoolClass->academic_year_id)
                ->exists()), 403);
    }

    private function authorizeHomeroom(User $user, SchoolClass $schoolClass): void
    {
        abort_unless($user->role === User::ROLE_ADMIN || ($user->role === User::ROLE_GURU
            && $user->teacherProfile()->whereHas('homeroomClasses', fn ($query) => $query->whereKey($schoolClass->id))->exists()), 403);
    }

    /**
     * @return array{score: float, predicate: string, description: string}|null
     */
    private function finalGrade(StudentProfile $student, SchoolClass $schoolClass, Subject $subject): ?array
    {
        $components = GradeComponent::query()
            ->with(['scores' => fn ($query) => $query->where('student_profile_id', $student->id)])
            ->where('academic_year_id', $schoolClass->academic_year_id)
            ->where('subject_id', $subject->id)->get();
        $weightedTotal = 0.0;
        $usedWeight = 0.0;
        foreach ($components as $component) {
            $values = $component->scores->pluck('score')->map(fn ($score) => (float) $score)->all();
            if (mb_strtolower($component->name) === 'tugas') {
                $assignmentIds = ClassAssignment::query()
                    ->where('school_class_id', $schoolClass->id)
                    ->where('subject_id', $subject->id)->pluck('id');
                $assignmentValues = AssignmentSubmission::query()
                    ->where('student_profile_id', $student->id)
                    ->whereIn('assignment_id', $assignmentIds)
                    ->whereNotNull('score')->pluck('score')->map(fn ($score) => (float) $score)->all();
                $values = [...$values, ...$assignmentValues];
            }
            if ($values === []) {
                continue;
            }
            $componentAverage = array_sum($values) / count($values);
            $weightedTotal += $componentAverage * (float) $component->weight;
            $usedWeight += (float) $component->weight;
        }
        if ($usedWeight === 0.0) {
            return null;
        }

        $score = round($weightedTotal / $usedWeight, 2);
        $band = GradingBand::query()->where('academic_year_id', $schoolClass->academic_year_id)
            ->where('minimum_score', '<=', $score)->orderByDesc('minimum_score')->first();

        return [
            'score' => $score,
            'predicate' => $band?->predicate ?? 'Belum diatur',
            'description' => $band?->description ?? 'Predikat nilai belum diatur.',
        ];
    }

    private function notifyPublishedReport(ReportCard $report): void
    {
        $report->loadMissing('student.user.parents');
        $student = $report->student->user;
        foreach (collect([$student])->merge($student->parents)->unique('id') as $recipient) {
            $recipient->notify(new \App\Notifications\SchoolEventNotification(
                'report.published',
                'Rapor telah diterbitkan',
                'Rapor tahun ajaran '.$report->academicYear->name.' sudah dapat dilihat.',
                ['report_card_id' => $report->id],
                $recipient->role === User::ROLE_ORANG_TUA
                    ? route('orang_tua.children.reports', ['student' => $student->id], absolute: false)
                    : route('siswa.reports.index', absolute: false),
            ));
        }
    }
}
