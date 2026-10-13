<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Models\SchoolClass;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\TeacherAttendance;
use App\Models\User;
use App\Services\AttendanceChallengeService;
use App\Services\SchoolGeofence;
use App\Services\SchoolNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AttendanceController extends Controller
{
    public function issueChallenge(Request $request, AttendanceChallengeService $challenges): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate(['type' => ['required', 'in:student_present,teacher_check_in,teacher_check_out']]);
        $user = $request->user();
        $allowed = match ($data['type']) {
            'student_present' => $user->role === User::ROLE_SISWA,
            'teacher_check_in', 'teacher_check_out' => $user->role === User::ROLE_GURU,
        };
        abort_unless($allowed, 403);

        $challenge = $challenges->issue($user, $data['type'], $request->session()->getId());

        return response()->json($challenge, 201, ['Cache-Control' => 'no-store']);
    }

    public function student(Request $request): View
    {
        $student = StudentProfile::query()->where('user_id', $request->user()->id)->firstOrFail();

        return $this->studentAttendancePage($request, $student, true);
    }

    public function parentStudent(Request $request, User $student): View
    {
        abort_unless($request->user()->role === User::ROLE_ORANG_TUA
            && $request->user()->children()->whereKey($student->id)->exists(), 403);

        $profile = StudentProfile::query()->where('user_id', $student->id)->firstOrFail();

        return $this->studentAttendancePage($request, $profile, false);
    }

    public function markStudent(Request $request, SchoolGeofence $geofence): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:hadir,izin,sakit'],
            'location_consent' => ['accepted_if:status,hadir'],
            'attendance_challenge' => ['required_if:status,hadir', 'nullable', 'string', 'size:64'],
            'latitude' => ['required_if:status,hadir', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:status,hadir', 'nullable', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['required_if:status,hadir', 'nullable', 'numeric', 'between:0,10000'],
            'device_token' => ['required_if:status,hadir', 'nullable', 'string', 'min:16', 'max:128'],
            'reason' => ['required_if:status,izin,sakit', 'nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'selfie' => ['prohibited'],
        ]);
        $student = StudentProfile::query()->where('user_id', $request->user()->id)->firstOrFail();
        $today = now();
        $settings = AttendanceSetting::current();
        $distance = null;
        $reasons = [];
        $deviceHash = null;

        if ($data['status'] === StudentAttendance::STATUS_PRESENT) {
            app(AttendanceChallengeService::class)->consume(
                $request->user(),
                'student_present',
                $request->session()->getId(),
                $data['attendance_challenge'],
            );
            $currentTime = $today->format('H:i:s');
            if ($currentTime < $settings->student_check_in_starts_at || $currentTime > $settings->student_check_in_ends_at) {
                $reasons[] = 'Absensi dilakukan di luar jam yang ditetapkan';
            }

            $distance = $geofence->distanceInMeters(
                (float) $data['latitude'],
                (float) $data['longitude'],
                $settings,
            );

            if ($distance > $settings->radius_meters + (float) $data['accuracy_meters']) {
                $reasons[] = 'Lokasi berada di luar radius sekolah setelah toleransi akurasi GPS';
            }
            if ($geofence->locationUsedByAnotherAccount(
                (float) $data['latitude'],
                (float) $data['longitude'],
                $request->user()->id,
            )) {
                $reasons[] = 'Koordinat yang sama tercatat pada akun pengguna lain';
            }
            $deviceHash = hash('sha256', $data['device_token']);
            if ($geofence->deviceUsedByAnotherAccount($deviceHash, $request->user()->id)) {
                $reasons[] = 'Perangkat terdeteksi digunakan oleh akun lain';
            }
            $previousAttendance = $student->attendances()
                ->whereNotNull('checked_in_at')
                ->whereNotNull('latitude')
                ->where('attendance_date', '<', $today->toDateString())
                ->orderByDesc('attendance_date')
                ->first();
            $previousPosition = $previousAttendance === null ? null : [
                'latitude' => $previousAttendance->latitude,
                'longitude' => $previousAttendance->longitude,
            ];
            $elapsedSeconds = $previousAttendance?->checked_in_at?->diffInSeconds($today);
            $reasons = [...$reasons, ...$geofence->suspiciousReasons(
                (float) $data['latitude'],
                (float) $data['longitude'],
                (float) $data['accuracy_meters'],
                $previousPosition,
                $elapsedSeconds,
                false,
                $settings->max_accuracy_meters,
            )];
            $reasons = array_values(array_unique($reasons));
        }

        if ($student->attendances()->whereDate('attendance_date', $today->toDateString())->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Absensi hari ini sudah tercatat. Hubungi guru jika perlu dikoreksi.',
            ]);
        }

        DB::transaction(function () use ($request, $student, $data, $today, $distance, $reasons, $deviceHash): void {
            $attendance = $student->attendances()->firstOrCreate(
                ['attendance_date' => $today->toDateString()],
                [
                    'status' => $data['status'],
                    'checked_in_at' => $data['status'] === StudentAttendance::STATUS_PRESENT ? $today : null,
                    'latitude' => $data['status'] === StudentAttendance::STATUS_PRESENT ? $data['latitude'] : null,
                    'longitude' => $data['status'] === StudentAttendance::STATUS_PRESENT ? $data['longitude'] : null,
                    'accuracy_meters' => $data['status'] === StudentAttendance::STATUS_PRESENT ? $data['accuracy_meters'] : null,
                    'distance_meters' => $distance,
                    'reason' => $data['reason'] ?? null,
                    'location_consent_at' => $data['status'] === StudentAttendance::STATUS_PRESENT ? now() : null,
                    'needs_review' => $reasons !== [],
                    'review_status' => $reasons !== [] ? 'perlu_ditinjau' : 'bersih',
                    'suspicious_reasons' => $reasons,
                    'device_hash' => $deviceHash,
                ],
            );

            if (! $attendance->wasRecentlyCreated) {
                throw ValidationException::withMessages([
                    'status' => 'Absensi hari ini sudah tercatat. Hubungi guru jika perlu dikoreksi.',
                ]);
            }

            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('student-attendance', 'local');
                if ($attachmentPath === false) {
                    throw new RuntimeException('Berkas absensi murid tidak dapat disimpan ke penyimpanan privat.');
                }
                $attendance->update(['attachment_path' => $attachmentPath]);
            }
        });

        if ($reasons !== []) {
            app(SchoolNotifier::class)->suspiciousStudentAttendance(
                StudentAttendance::query()
                    ->where('student_profile_id', $student->id)
                    ->whereDate('attendance_date', $today->toDateString())
                    ->firstOrFail(),
            );
        }

        return back()->with('status', 'Absensi hari ini berhasil disimpan.');
    }

    public function teacher(Request $request): View
    {
        $profile = $request->user()->teacherProfile()->firstOrFail();
        $attendance = $profile->attendances()->whereDate('attendance_date', today())->first();

        return view('attendance.teacher', [
            'attendance' => $attendance,
            'settings' => AttendanceSetting::current(),
            'challengeType' => null,
        ]);
    }

    public function teacherCheckIn(Request $request, SchoolGeofence $geofence): RedirectResponse
    {
        return $this->recordTeacherAttendance($request, $geofence, 'check_in');
    }

    public function teacherCheckOut(Request $request, SchoolGeofence $geofence): RedirectResponse
    {
        return $this->recordTeacherAttendance($request, $geofence, 'check_out');
    }

    public function classAttendance(Request $request, SchoolClass $schoolClass): View
    {
        $teacher = $request->user()->teacherProfile()->firstOrFail();
        abort_unless(
            $teacher->classes()->whereKey($schoolClass->id)->exists()
                || $teacher->homeroomClasses()->whereKey($schoolClass->id)->exists(),
            403,
        );

        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = $filters['month'] ?? now()->format('Y-m');
        $start = now()->createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $students = $schoolClass->students()
            ->with(['user', 'attendances' => fn (Builder $query) => $query
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('attendance_date')])
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->get();

        return view('attendance.teacher-class', [
            'schoolClass' => $schoolClass->load('academicYear'),
            'students' => $students,
            'month' => $month,
        ]);
    }

    public function correctStudent(Request $request, SchoolClass $schoolClass, StudentProfile $student): RedirectResponse
    {
        $teacher = $request->user()->teacherProfile()->firstOrFail();
        abort_unless(
            $teacher->classes()->whereKey($schoolClass->id)->exists()
                || $teacher->homeroomClasses()->whereKey($schoolClass->id)->exists(),
            403,
        );
        abort_unless($student->school_class_id === $schoolClass->id, 404);

        $data = $request->validate([
            'attendance_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', 'in:hadir,izin,sakit,alpa'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $date = now()->parse($data['attendance_date'])->toDateString();

        DB::transaction(function () use ($request, $student, $data, $date): void {
            $attendance = StudentAttendance::query()
                ->where('student_profile_id', $student->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->first();
            $previousStatus = $attendance?->status;

            if ($attendance === null) {
                $attendance = new StudentAttendance([
                    'student_profile_id' => $student->id,
                    'attendance_date' => $date,
                ]);
            }

            $attendance->fill([
                'status' => $data['status'],
                'edited_by' => $request->user()->id,
                'edited_at' => now(),
                'correction_reason' => $data['reason'],
            ])->save();

            $request->user()->auditLogs()->create([
                'action' => 'corrected',
                'record_type' => StudentAttendance::class,
                'record_id' => $attendance->id,
                'description' => 'Mengoreksi absensi '.$student->user()->value('name').' tanggal '.$date,
                'metadata' => [
                    'previous_status' => $previousStatus,
                    'new_status' => $data['status'],
                    'reason' => $data['reason'],
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'Koreksi absensi tersimpan dan tercatat di audit aktivitas.');
    }

    public function privateAttachment(StudentAttendance $attendance): mixed
    {
        Gate::authorize('view-student-record', $attendance->student()->firstOrFail());
        abort_if($attendance->attachment_path === null, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($attendance->attachment_path), 404);

        return $disk->download($attendance->attachment_path);
    }

    public function privateTeacherSelfie(Request $request, TeacherAttendance $attendance, string $event): mixed
    {
        $user = $request->user();
        abort_unless(
            $user->role === User::ROLE_ADMIN
                || ($user->role === User::ROLE_GURU
                    && $user->teacherProfile()->whereKey($attendance->teacher_profile_id)->exists()),
            403,
        );
        $path = match ($event) {
            'masuk' => $attendance->check_in_selfie_path,
            'pulang' => $attendance->check_out_selfie_path,
            default => null,
        };
        abort_if($path === null, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, 'selfie-guru-'.$attendance->attendance_date->format('Ymd').'-'.$event.'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function studentAttendancePage(Request $request, StudentProfile $student, bool $allowCheckIn): View
    {
        Gate::authorize('view-student-record', $student);
        $filters = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $filters['month'] ?? now()->format('Y-m');
        $start = now()->createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $attendance = $student->attendances()
            ->whereBetween('attendance_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->with('editor')
            ->orderByDesc('attendance_date')
            ->get();

        return view('attendance.student', [
            'student' => $student->load('user', 'schoolClass'),
            'attendance' => $attendance,
            'month' => $month,
            'allowCheckIn' => $allowCheckIn,
            'todayAttendance' => $student->attendances()->whereDate('attendance_date', today())->first(),
            'settings' => AttendanceSetting::current(),
        ]);
    }

    private function recordTeacherAttendance(Request $request, SchoolGeofence $geofence, string $event): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['required', 'numeric', 'between:0,10000'],
            'device_token' => ['required', 'string', 'min:16', 'max:128'],
            'attendance_challenge' => ['required', 'string', 'size:64'],
            'location_consent' => ['required', 'accepted'],
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
        ]);
        $profile = $request->user()->teacherProfile()->firstOrFail();
        $settings = AttendanceSetting::current();
        $now = now();
        app(AttendanceChallengeService::class)->consume(
            $request->user(),
            'teacher_'.$event,
            $request->session()->getId(),
            $data['attendance_challenge'],
        );

        $reasons = [];
        if ($event === 'check_in') {
            $currentTime = $now->format('H:i:s');
            if ($currentTime < $settings->teacher_check_in_starts_at || $currentTime > $settings->teacher_check_in_ends_at) {
                $reasons[] = 'Absensi masuk dilakukan di luar jam yang ditetapkan';
            }
        }

        $distance = $geofence->distanceInMeters(
            (float) $data['latitude'],
            (float) $data['longitude'],
            $settings,
        );
        if ($distance > $settings->radius_meters + (float) $data['accuracy_meters']) {
            $reasons[] = 'Lokasi berada di luar radius sekolah setelah toleransi akurasi GPS';
        }
        if ($geofence->locationUsedByAnotherAccount(
            (float) $data['latitude'],
            (float) $data['longitude'],
            $request->user()->id,
        )) {
            $reasons[] = 'Koordinat yang sama tercatat pada akun pengguna lain';
        }

        $attendance = $profile->attendances()->whereDate('attendance_date', $now->toDateString())->first();
        $wasPendingReview = $attendance?->review_status === 'perlu_ditinjau';
        if ($event === 'check_in' && $attendance?->check_in_at !== null) {
            throw ValidationException::withMessages(['attendance' => 'Absen masuk hari ini sudah tercatat.']);
        }
        if ($event === 'check_out' && $attendance?->check_in_at === null) {
            throw ValidationException::withMessages(['attendance' => 'Lakukan absen masuk terlebih dahulu.']);
        }
        if ($event === 'check_out' && $attendance?->check_out_at !== null) {
            throw ValidationException::withMessages(['attendance' => 'Absen pulang hari ini sudah tercatat.']);
        }

        $prefix = $event;
        $otherAccountUsesDevice = $geofence->deviceUsedByAnotherAccount($deviceHash, $request->user()->id);
        $previousPosition = null;
        $elapsedSeconds = null;

        if ($attendance !== null && $event === 'check_out' && $attendance->check_in_latitude !== null) {
            $previousPosition = [
                'latitude' => $attendance->check_in_latitude,
                'longitude' => $attendance->check_in_longitude,
            ];
            $elapsedSeconds = $attendance->check_in_at->diffInSeconds($now);
        } else {
            $previousAttendance = $profile->attendances()
                ->whereNotNull('check_in_at')
                ->where('attendance_date', '<', $now->toDateString())
                ->orderByDesc('attendance_date')
                ->first();
            if ($previousAttendance !== null) {
                $previousPosition = [
                    'latitude' => $previousAttendance->check_out_latitude ?? $previousAttendance->check_in_latitude,
                    'longitude' => $previousAttendance->check_out_longitude ?? $previousAttendance->check_in_longitude,
                ];
                $previousTime = $previousAttendance->check_out_at ?? $previousAttendance->check_in_at;
                $elapsedSeconds = $previousTime->diffInSeconds($now);
            }
        }

        $reasons = array_values(array_unique([...$reasons, ...$geofence->suspiciousReasons(
            (float) $data['latitude'],
            (float) $data['longitude'],
            (float) $data['accuracy_meters'],
            $previousPosition,
            $elapsedSeconds,
            $otherAccountUsesDevice,
            $settings->max_accuracy_meters,
        )]));
        DB::transaction(function () use ($profile, $event, $prefix, $data, $deviceHash, $reasons, $now, $request): void {
            $record = TeacherAttendance::query()
                ->where('teacher_profile_id', $profile->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                $record = TeacherAttendance::query()->firstOrCreate([
                    'teacher_profile_id' => $profile->id,
                    'attendance_date' => $now->toDateString(),
                ]);
            }

            if (($event === 'check_in' && $record->check_in_at !== null)
                || ($event === 'check_out' && $record->check_out_at !== null)) {
                throw ValidationException::withMessages([
                    'attendance' => $event === 'check_in'
                        ? 'Absen masuk hari ini sudah tercatat.'
                        : 'Absen pulang hari ini sudah tercatat.',
                ]);
            }

            if ($event === 'check_out' && $record->check_in_at === null) {
                throw ValidationException::withMessages(['attendance' => 'Lakukan absen masuk terlebih dahulu.']);
            }
            $selfiePath = $request->file('selfie')->store('teacher-attendance', 'local');
            if ($selfiePath === false) {
                throw new RuntimeException('Selfie absensi guru tidak dapat disimpan ke penyimpanan privat.');
            }

            $record->fill([
                $prefix.'_at' => $now,
                $prefix.'_latitude' => $data['latitude'],
                $prefix.'_longitude' => $data['longitude'],
                $prefix.'_accuracy_meters' => $data['accuracy_meters'],
                $prefix.'_ip_address' => $request->ip(),
                $prefix.'_device_hash' => $deviceHash,
                $prefix.'_selfie_path' => $selfiePath,
                'is_suspicious' => $record->is_suspicious || $reasons !== [],
                'review_status' => $reasons !== [] || $record->review_status === 'perlu_ditinjau'
                    ? 'perlu_ditinjau'
                    : $record->review_status,
                'reviewed_by' => $reasons !== [] ? null : $record->reviewed_by,
                'reviewed_at' => $reasons !== [] ? null : $record->reviewed_at,
                'review_note' => $reasons !== [] ? null : $record->review_note,
                'suspicious_reasons' => array_values(array_unique([
                    ...($record->suspicious_reasons ?? []),
                    ...$reasons,
                ])),
                $prefix.'_location_consent_at' => $now,
            ])->save();
        });
        $record = TeacherAttendance::query()
            ->where('teacher_profile_id', $profile->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->firstOrFail();
        if ($record->review_status === 'perlu_ditinjau' && ! $wasPendingReview) {
            app(SchoolNotifier::class)->suspiciousAttendance($record);
        }

        return back()->with('status', $event === 'check_in'
            ? 'Absen masuk berhasil disimpan.'
            : 'Absen pulang berhasil disimpan.');
    }
}
