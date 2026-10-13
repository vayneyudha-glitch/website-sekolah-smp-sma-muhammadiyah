<?php

namespace App\Http\Controllers;

use App\Models\AssignmentSubmission;
use App\Models\ClassAssignment;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Timetable;
use App\Models\User;
use App\Services\SchoolNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->role === User::ROLE_GURU) {
            $teacher = $user->teacherProfile()->firstOrFail();
            $timetables = Timetable::query()
                ->with(['schoolClass.academicYear', 'subject'])
                ->where('teacher_profile_id', $teacher->id)
                ->get()
                ->unique(fn (Timetable $timetable): string => $timetable->school_class_id.':'.$timetable->subject_id)
                ->values();
            $relatedClassIds = $teacher->classes()->pluck('school_classes.id')
                ->merge($teacher->homeroomClasses()->pluck('school_classes.id'))
                ->unique()
                ->values();
            $classes = $teacher->classes()->with('academicYear')->get()
                ->merge($teacher->homeroomClasses()->with('academicYear')->get())
                ->merge($timetables->pluck('schoolClass'))
                ->unique('id')
                ->values();
            $assignments = ClassAssignment::query()
                ->with(['schoolClass', 'subject', 'teacher'])
                ->withCount(['submissions as ungraded_submissions_count' => fn (Builder $query) => $query->whereNull('graded_at')])
                ->where(function (Builder $query) use ($user, $relatedClassIds, $timetables): void {
                    $query->where('teacher_id', $user->id);
                    if ($relatedClassIds->isNotEmpty()) {
                        $query->orWhereIn('school_class_id', $relatedClassIds);
                    }
                    foreach ($timetables as $timetable) {
                        $query->orWhere(fn (Builder $course) => $course
                            ->where('school_class_id', $timetable->school_class_id)
                            ->where('subject_id', $timetable->subject_id));
                    }
                })
                ->latest('due_at')
                ->get();

            return view('assignments.teacher-index', [
                'assignments' => $assignments,
                'timetables' => $timetables,
                'classes' => $classes,
            ]);
        }

        $student = $user->studentProfile()->with('schoolClass')->firstOrFail();
        $assignments = ClassAssignment::query()
            ->with(['teacher', 'subject'])
            ->with(['submissions' => fn (Builder $query) => $query->where('student_profile_id', $student->id)])
            ->where('school_class_id', $student->school_class_id)
            ->orderBy('due_at')
            ->get();

        return view('assignments.student-index', [
            'student' => $student,
            'assignments' => $assignments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = $request->user()->teacherProfile()->firstOrFail();
        $data = $request->validate([
            'timetable_id' => ['required', 'integer', 'exists:timetables,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'due_at' => ['required', 'date', 'after:now'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,docx,jpg,jpeg,png', 'max:5120'],
        ]);
        $timetable = Timetable::query()
            ->whereKey($data['timetable_id'])
            ->where('teacher_profile_id', $teacher->id)
            ->firstOrFail();

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('class-assignments', 'local');
            if ($path === false) {
                throw new RuntimeException('Lampiran tugas tidak dapat disimpan ke penyimpanan privat.');
            }
        }

        $assignment = ClassAssignment::query()->create([
            'teacher_id' => $request->user()->id,
            'school_class_id' => $timetable->school_class_id,
            'subject_id' => $timetable->subject_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'attachment_path' => $path,
            'due_at' => $data['due_at'],
        ]);
        app(SchoolNotifier::class)->assignment($assignment);

        return back()->with('status', 'Tugas berhasil dibuat.');
    }

    public function submit(Request $request, ClassAssignment $assignment): RedirectResponse
    {
        $student = $request->user()->studentProfile()->firstOrFail();
        abort_unless($student->school_class_id === $assignment->school_class_id, 403);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,docx,jpg,jpeg,png', 'max:5120'],
        ]);
        $newPath = null;
        $oldPath = null;
        $submittedAt = now();

        DB::transaction(function () use ($request, $assignment, $student, $data, $submittedAt, &$newPath, &$oldPath): void {
            $submission = AssignmentSubmission::query()
                ->where('assignment_id', $assignment->id)
                ->where('student_profile_id', $student->id)
                ->lockForUpdate()
                ->first();
            if ($submission?->graded_at !== null) {
                throw ValidationException::withMessages([
                    'submission' => 'Kiriman yang sudah dinilai tidak dapat diubah.',
                ]);
            }

            $body = array_key_exists('body', $data) ? ($data['body'] ?? null) : $submission?->body;
            $hasNewFile = $request->hasFile('attachment');
            if (blank($body) && ! $hasNewFile && $submission?->file_path === null) {
                throw ValidationException::withMessages([
                    'body' => 'Kirim teks atau unggah berkas sebelum menyimpan.',
                ]);
            }
            if ($hasNewFile) {
                $newPath = $request->file('attachment')->store('assignment-submissions', 'local');
                if ($newPath === false) {
                    throw new RuntimeException('Kiriman tugas tidak dapat disimpan ke penyimpanan privat.');
                }
            }

            $oldPath = $submission?->file_path;
            $values = [
                'body' => $body,
                'file_path' => $newPath ?? $submission?->file_path,
                'submitted_at' => $submittedAt,
                'is_late' => $submittedAt->greaterThan($assignment->due_at),
            ];

            if ($submission === null) {
                AssignmentSubmission::query()->create($values + [
                    'assignment_id' => $assignment->id,
                    'student_profile_id' => $student->id,
                ]);
            } else {
                $submission->update($values);
            }
        });

        if ($newPath !== null && $oldPath !== null && ! Storage::disk('local')->delete($oldPath)
            && Storage::disk('local')->exists($oldPath)) {
            throw new RuntimeException('Kiriman baru tersimpan, tetapi berkas kiriman sebelumnya gagal dihapus.');
        }

        return back()->with('status', 'Kiriman tugas berhasil disimpan.');
    }

    public function show(Request $request, ClassAssignment $assignment): View
    {
        $this->authorizeTeacherForAssignment($request->user(), $assignment);

        $students = StudentProfile::query()
            ->with(['user', 'assignmentSubmissions' => fn (Builder $query) => $query
                ->where('assignment_id', $assignment->id)])
            ->where('school_class_id', $assignment->school_class_id)
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('student_number')
            ->get();

        return view('assignments.teacher-show', [
            'assignment' => $assignment->load(['schoolClass', 'subject']),
            'students' => $students,
        ]);
    }

    public function grade(Request $request, AssignmentSubmission $submission): RedirectResponse
    {
        $submission->load('assignment');
        $this->authorizeTeacherForAssignment($request->user(), $submission->assignment);
        abort_if($submission->submitted_at === null, 404);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'between:0,100'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);
        DB::transaction(function () use ($submission, $data, $request): void {
            $lockedSubmission = AssignmentSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $lockedSubmission->update([
                'score' => $data['score'],
                'feedback' => $data['feedback'] ?? null,
                'graded_by' => $request->user()->id,
                'graded_at' => now(),
            ]);
        });
        app(SchoolNotifier::class)->grade($submission->refresh());

        return back()->with('status', 'Nilai dan komentar berhasil disimpan.');
    }

    public function classReport(Request $request, SchoolClass $schoolClass): View
    {
        $teacher = $request->user()->teacherProfile()->firstOrFail();
        $subjectIds = Timetable::query()
            ->where('teacher_profile_id', $teacher->id)
            ->where('school_class_id', $schoolClass->id)
            ->pluck('subject_id');
        $isClassTeacher = $teacher->classes()->whereKey($schoolClass->id)->exists()
            || $teacher->homeroomClasses()->whereKey($schoolClass->id)->exists();
        abort_unless($isClassTeacher || $subjectIds->isNotEmpty(), 403);
        $assignments = ClassAssignment::query()
            ->with(['subject', 'submissions.student.user'])
            ->where('school_class_id', $schoolClass->id)
            ->when(! $isClassTeacher, fn (Builder $query) => $query->whereIn('subject_id', $subjectIds))
            ->orderBy('due_at')
            ->get();

        return view('assignments.class-report', [
            'schoolClass' => $schoolClass->load('academicYear'),
            'assignments' => $assignments,
            'students' => StudentProfile::query()
                ->with('user')
                ->where('school_class_id', $schoolClass->id)
                ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
                ->orderBy('student_number')
                ->get(),
        ]);
    }

    public function exportClass(Request $request, SchoolClass $schoolClass): StreamedResponse
    {
        $teacher = $request->user()->teacherProfile()->firstOrFail();
        $subjectIds = Timetable::query()
            ->where('teacher_profile_id', $teacher->id)
            ->where('school_class_id', $schoolClass->id)
            ->pluck('subject_id');
        $isClassTeacher = $teacher->classes()->whereKey($schoolClass->id)->exists()
            || $teacher->homeroomClasses()->whereKey($schoolClass->id)->exists();
        abort_unless($isClassTeacher || $subjectIds->isNotEmpty(), 403);
        $filters = $request->validate([
            'student_profile_id' => ['nullable', 'integer', 'exists:student_profiles,id'],
        ]);
        if (isset($filters['student_profile_id'])) {
            abort_unless(StudentProfile::query()
                ->whereKey($filters['student_profile_id'])
                ->where('school_class_id', $schoolClass->id)
                ->exists(), 404);
        }

        $assignments = ClassAssignment::query()
            ->with(['subject', 'submissions.student.user'])
            ->where('school_class_id', $schoolClass->id)
            ->when(! $isClassTeacher, fn (Builder $query) => $query->whereIn('subject_id', $subjectIds))
            ->orderBy('due_at')
            ->get();
        $studentsQuery = StudentProfile::query()
            ->with('user')
            ->where('school_class_id', $schoolClass->id)
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('student_number');
        if (isset($filters['student_profile_id'])) {
            $studentsQuery->whereKey($filters['student_profile_id']);
        }
        $students = $studentsQuery->get();

        return response()->streamDownload(function () use ($assignments, $students): void {
            echo "\xEF\xBB\xBF";
            $output = fopen('php://output', 'w');
            if (! is_resource($output)) {
                throw new RuntimeException('Ekspor nilai tidak dapat membuka aliran CSV.');
            }
            $this->writeCsvRow($output, [
                'NIS', 'Nama Murid', 'Mata Pelajaran', 'Tugas', 'Batas Waktu',
                'Waktu Pengumpulan', 'Status', 'Nilai', 'Komentar Guru',
            ]);

            foreach ($assignments as $assignment) {
                foreach ($students as $student) {
                    $submission = $assignment->submissions->firstWhere('student_profile_id', $student->id);
                    $this->writeCsvRow($output, [
                        $student->nis,
                        $student->user->name,
                        $assignment->subject->name,
                        $assignment->title,
                        $assignment->due_at->format('Y-m-d H:i:s'),
                        $submission?->submitted_at?->format('Y-m-d H:i:s') ?? '',
                        $submission === null ? 'Belum dikumpulkan' : ($submission->is_late ? 'Terlambat' : 'Tepat Waktu'),
                        $submission?->score ?? '',
                        $submission?->feedback ?? '',
                    ]);
                }
            }
            fclose($output);
        }, 'rekap-nilai-'.$schoolClass->id.'-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function childGrades(Request $request, User $student): View
    {
        abort_unless($request->user()->children()->whereKey($student->id)->exists(), 403);
        $profile = StudentProfile::query()
            ->with('schoolClass')
            ->where('user_id', $student->id)
            ->firstOrFail();
        $submissions = AssignmentSubmission::query()
            ->with(['assignment.subject', 'grader'])
            ->where('student_profile_id', $profile->id)
            ->whereNotNull('graded_at')
            ->latest('graded_at')
            ->get();

        return view('assignments.parent-grades', [
            'student' => $student,
            'profile' => $profile,
            'submissions' => $submissions,
        ]);
    }

    public function downloadAssignment(Request $request, ClassAssignment $assignment)
    {
        $user = $request->user();
        abort_if($assignment->attachment_path === null, 404);
        if ($user->role === User::ROLE_SISWA) {
            abort_unless($user->studentProfile()
                ->where('school_class_id', $assignment->school_class_id)
                ->exists(), 403);
        } else {
            $this->authorizeTeacherForAssignment($user, $assignment);
        }

        return $this->privateDownload(
            $assignment->attachment_path,
            'lampiran-tugas-'.$assignment->id.'.'.pathinfo($assignment->attachment_path, PATHINFO_EXTENSION),
        );
    }

    public function downloadSubmission(Request $request, AssignmentSubmission $submission)
    {
        abort_if($submission->file_path === null, 404);
        $user = $request->user();

        if ($user->role === User::ROLE_SISWA) {
            abort_unless($user->studentProfile()->whereKey($submission->student_profile_id)->exists(), 403);
        } else {
            $this->authorizeTeacherForAssignment($user, $submission->assignment);
        }

        return $this->privateDownload(
            $submission->file_path,
            'kiriman-tugas-'.$submission->assignment_id.'-'.$submission->student_profile_id.'.'.pathinfo($submission->file_path, PATHINFO_EXTENSION),
        );
    }

    private function authorizeTeacherForAssignment(User $user, ClassAssignment $assignment): void
    {
        if ($user->role === User::ROLE_ADMIN) {
            return;
        }

        abort_unless($user->role === User::ROLE_GURU, 403);
        $teacher = $user->teacherProfile()->firstOrFail();
        $isClassTeacher = $teacher->classes()->whereKey($assignment->school_class_id)->exists()
            || $teacher->homeroomClasses()->whereKey($assignment->school_class_id)->exists();
        $teachesSubject = Timetable::query()
            ->where('teacher_profile_id', $teacher->id)
            ->where('school_class_id', $assignment->school_class_id)
            ->where('subject_id', $assignment->subject_id)
            ->exists();
        abort_unless($isClassTeacher || $teachesSubject, 403);
    }

    private function privateDownload(string $path, string $filename)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, $filename, ['Cache-Control' => 'private, no-store']);
    }

    /**
     * @param resource $output
     * @param list<mixed> $values
     */
    private function writeCsvRow($output, array $values): void
    {
        $safeValues = array_map(static function (mixed $value): string {
            $value = (string) ($value ?? '');

            return preg_match('/^[\s]*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
        }, $values);

        if (fputcsv($output, $safeValues, ',', '"', '') === false) {
            throw new RuntimeException('Baris ekspor nilai gagal ditulis.');
        }
    }
}
