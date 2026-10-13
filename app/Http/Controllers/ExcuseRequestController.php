<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ExcuseRequest;
use App\Models\SchoolClass;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\SchoolNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class ExcuseRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $requests = ExcuseRequest::query()->with(['student.user', 'requester', 'reviewer']);
        $classes = collect();

        if ($user->role === User::ROLE_ADMIN) {
            $requests->latest();
        } elseif ($user->role === User::ROLE_GURU) {
            $teacher = $user->teacherProfile()->firstOrFail();
            $classIds = $teacher->classes()->pluck('school_classes.id')
                ->merge($teacher->homeroomClasses()->pluck('school_classes.id'))->unique();
            $requests->whereHas('student', fn ($query) => $query->whereIn('school_class_id', $classIds))->latest();
            $classes = SchoolClass::query()->whereIn('id', $classIds)->orderBy('name')->get();
        } else {
            $studentUserIds = $user->role === User::ROLE_SISWA
                ? collect([$user->id])
                : $user->children()->pluck('users.id');
            $requests->whereHas('student', fn ($query) => $query->whereIn('user_id', $studentUserIds))->latest();
        }

        return view('excuses.index', [
            'requests' => $requests->paginate(20),
            'classes' => $classes,
            'canApply' => in_array($user->role, [User::ROLE_SISWA, User::ROLE_ORANG_TUA], true),
            'children' => $user->role === User::ROLE_ORANG_TUA
                ? $user->children()->with('studentProfile')->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function create(Request $request, ?User $student = null): View
    {
        $user = $request->user();
        if ($user->role === User::ROLE_SISWA) {
            $profile = $user->studentProfile()->firstOrFail();
        } else {
            abort_unless($user->role === User::ROLE_ORANG_TUA && $student !== null
                && $user->children()->whereKey($student->id)->exists(), 403);
            $profile = $student->studentProfile()->firstOrFail();
        }

        return view('excuses.create', ['student' => $profile->load('user')]);
    }

    public function store(Request $request, ?User $student = null): RedirectResponse
    {
        $user = $request->user();
        if ($user->role === User::ROLE_SISWA) {
            $profile = $user->studentProfile()->firstOrFail();
        } else {
            abort_unless($user->role === User::ROLE_ORANG_TUA && $student !== null
                && $user->children()->whereKey($student->id)->exists(), 403);
            $profile = $student->studentProfile()->firstOrFail();
        }
        $data = $request->validate([
            'attendance_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
                Rule::unique('excuse_requests', 'attendance_date')->where('student_profile_id', $profile->id),
            ],
            'type' => ['required', 'in:izin,sakit'],
            'reason' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('excuse-requests', 'local');
            if ($path === false) {
                throw new RuntimeException('Lampiran izin/sakit tidak dapat disimpan ke penyimpanan privat.');
            }
        }

        try {
            $excuse = DB::transaction(function () use ($request, $profile, $data, $path): ExcuseRequest {
                $excuse = ExcuseRequest::query()->create([
                    'student_profile_id' => $profile->id,
                    'requested_by' => $request->user()->id,
                    'attendance_date' => $data['attendance_date'],
                    'type' => $data['type'],
                    'reason' => $data['reason'],
                    'attachment_path' => $path,
                ]);
                app(SchoolNotifier::class)->excuseRequested($excuse);

                return $excuse;
            });
        } catch (\Illuminate\Database\QueryException $exception) {
            if ($path !== null && ! Storage::disk('local')->delete($path) && Storage::disk('local')->exists($path)) {
                throw new RuntimeException('Permohonan gagal disimpan dan lampiran sementara tidak dapat dihapus.', previous: $exception);
            }
            throw $exception;
        }

        return redirect()->route($user->role === User::ROLE_ORANG_TUA ? 'orang_tua.excuses.index' : 'siswa.excuses.index')
            ->with('status', 'Permohonan izin/sakit berhasil diajukan.');
    }

    public function review(Request $request, ExcuseRequest $excuse): RedirectResponse
    {
        Gate::authorize('review', $excuse);
        $data = $request->validate([
            'decision' => ['required', 'in:disetujui,ditolak'],
            'review_note' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $excuse, $data): void {
            $locked = ExcuseRequest::query()->whereKey($excuse->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== ExcuseRequest::STATUS_PENDING) {
                throw ValidationException::withMessages(['decision' => 'Permohonan ini sudah diputuskan.']);
            }

            if ($data['decision'] === ExcuseRequest::STATUS_APPROVED) {
                $attendance = StudentAttendance::query()
                    ->where('student_profile_id', $locked->student_profile_id)
                    ->whereDate('attendance_date', $locked->attendance_date)
                    ->lockForUpdate()
                    ->first();
                if ($attendance !== null && $attendance->status === StudentAttendance::STATUS_PRESENT) {
                    throw ValidationException::withMessages([
                        'decision' => 'Absensi Hadir sudah tercatat pada tanggal tersebut sehingga izin/sakit tidak dapat disetujui.',
                    ]);
                }
                $attendance ??= new StudentAttendance([
                    'student_profile_id' => $locked->student_profile_id,
                    'attendance_date' => $locked->attendance_date->toDateString(),
                ]);
                $attendance->fill([
                    'status' => $locked->type,
                    'reason' => $locked->reason,
                    'edited_by' => $request->user()->id,
                    'edited_at' => now(),
                    'correction_reason' => 'Permohonan '.$locked->type.' disetujui.',
                ])->save();
            }

            $locked->update([
                'status' => $data['decision'],
                'reviewed_by' => $request->user()->id,
                'review_note' => $data['review_note'],
                'reviewed_at' => now(),
            ]);
            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'reviewed',
                'record_type' => ExcuseRequest::class,
                'record_id' => $locked->id,
                'description' => 'Memutuskan permohonan izin/sakit murid.',
                'metadata' => ['decision' => $data['decision'], 'student_profile_id' => $locked->student_profile_id],
                'ip_address' => $request->ip(),
            ]);
            app(SchoolNotifier::class)->excuseDecision($locked);
        });

        return back()->with('status', 'Keputusan permohonan berhasil disimpan.');
    }

    public function attachment(ExcuseRequest $excuse): mixed
    {
        Gate::authorize('view', $excuse);
        abort_if($excuse->attachment_path === null, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($excuse->attachment_path), 404);

        return $disk->download($excuse->attachment_path, headers: ['Cache-Control' => 'private, no-store']);
    }
}
