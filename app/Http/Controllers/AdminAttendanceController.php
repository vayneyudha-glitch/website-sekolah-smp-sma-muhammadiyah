<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Models\AuditLog;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceController extends Controller
{
    public function settings(): View
    {
        return view('admin.attendance.settings', [
            'settings' => AttendanceSetting::current(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'between:20,5000'],
            'max_accuracy_meters' => ['required', 'integer', 'between:1,10000'],
            'student_check_in_starts_at' => ['required', 'date_format:H:i'],
            'student_check_in_ends_at' => ['required', 'date_format:H:i', 'after:student_check_in_starts_at'],
            'teacher_check_in_starts_at' => ['required', 'date_format:H:i'],
            'teacher_check_in_ends_at' => ['required', 'date_format:H:i', 'after:teacher_check_in_starts_at'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $settings = AttendanceSetting::current();
            $settings->update($data);
            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'updated',
                'record_type' => AttendanceSetting::class,
                'record_id' => $settings->id,
                'description' => 'Memperbarui pengaturan lokasi dan jam absensi sekolah',
                'metadata' => [
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'radius_meters' => $data['radius_meters'],
                    'max_accuracy_meters' => $data['max_accuracy_meters'],
                    'student_check_in_starts_at' => $data['student_check_in_starts_at'],
                    'student_check_in_ends_at' => $data['student_check_in_ends_at'],
                    'teacher_check_in_starts_at' => $data['teacher_check_in_starts_at'],
                    'teacher_check_in_ends_at' => $data['teacher_check_in_ends_at'],
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'Pengaturan absensi sekolah berhasil diperbarui.');
    }

    public function teacherReport(Request $request): View
    {
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? now()->format('Y-m');

        return view('admin.attendance.teachers', [
            'teachers' => $this->teachersForMonth($month)->get(),
            'month' => $month,
        ]);
    }

    public function exportTeachers(Request $request): StreamedResponse
    {
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? now()->format('Y-m');
        $teachers = $this->teachersForMonth($month)->get();

        return response()->streamDownload(function () use ($teachers, $month): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Ekspor absensi guru tidak dapat dibuat.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            $this->writeCsvRow($output, [
                'Nama Guru', 'NIP', 'Tanggal', 'Jam Masuk', 'Latitude Masuk', 'Longitude Masuk', 'Akurasi Masuk (m)', 'IP Masuk',
                'Jam Pulang', 'Latitude Pulang', 'Longitude Pulang', 'Akurasi Pulang (m)', 'IP Pulang', 'Mencurigakan', 'Catatan Deteksi',
            ]);
            foreach ($teachers as $teacher) {
                if ($teacher->attendances->isEmpty()) {
                    $this->writeCsvRow($output, [$teacher->user->name, $teacher->employee_number, '', '', '', '', '', '', '', '', '', '', '', 'Tidak', 'Belum ada absensi bulan ini']);
                    continue;
                }

                foreach ($teacher->attendances as $attendance) {
                    $this->writeCsvRow($output, [
                        $teacher->user->name,
                        $teacher->employee_number,
                        $attendance->attendance_date->format('d/m/Y'),
                        $attendance->check_in_at?->format('H:i:s') ?? '',
                        (string) ($attendance->check_in_latitude ?? ''),
                        (string) ($attendance->check_in_longitude ?? ''),
                        (string) ($attendance->check_in_accuracy_meters ?? ''),
                        $attendance->check_in_ip_address ?? '',
                        $attendance->check_out_at?->format('H:i:s') ?? '',
                        (string) ($attendance->check_out_latitude ?? ''),
                        (string) ($attendance->check_out_longitude ?? ''),
                        (string) ($attendance->check_out_accuracy_meters ?? ''),
                        $attendance->check_out_ip_address ?? '',
                        $attendance->is_suspicious ? 'Ya' : 'Tidak',
                        implode('; ', $attendance->suspicious_reasons ?? []),
                    ]);
                }
            }

            fclose($output);
        }, 'rekap-absensi-guru-'.$month.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function reviewQueue(): View
    {
        $students = StudentAttendance::query()
            ->with('student.user')
            ->where('review_status', 'perlu_ditinjau')
            ->orderByDesc('attendance_date')
            ->get()
            ->map(fn (StudentAttendance $attendance): array => [
                'type' => 'murid',
                'id' => $attendance->id,
                'name' => $attendance->student->user->name,
                'date' => $attendance->attendance_date,
                'summary' => 'Status hadir murid',
                'reasons' => $attendance->suspicious_reasons ?? [],
                'record' => $attendance,
            ]);

        $teachers = TeacherAttendance::query()
            ->with('teacher.user')
            ->where('review_status', 'perlu_ditinjau')
            ->orderByDesc('attendance_date')
            ->get()
            ->map(fn (TeacherAttendance $attendance): array => [
                'type' => 'guru',
                'id' => $attendance->id,
                'name' => $attendance->teacher->user->name,
                'date' => $attendance->attendance_date,
                'summary' => 'Absensi guru'.($attendance->check_in_at ? ' masuk' : '').($attendance->check_out_at ? ' dan pulang' : ''),
                'reasons' => $attendance->suspicious_reasons ?? [],
                'record' => $attendance,
            ]);

        return view('admin.attendance.review', [
            'items' => $students->concat($teachers)->sortByDesc(fn (array $item) => $item['date']->timestamp)->values(),
        ]);
    }

    public function review(Request $request, string $type, int $attendance): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:disetujui,ditolak'],
            'note' => ['required', 'string', 'max:2000'],
        ]);
        $modelClass = match ($type) {
            'murid' => StudentAttendance::class,
            'guru' => TeacherAttendance::class,
            default => abort(404),
        };

        DB::transaction(function () use ($request, $modelClass, $attendance, $type, $data): void {
            $record = $modelClass::query()->lockForUpdate()->findOrFail($attendance);
            abort_unless($record->review_status === 'perlu_ditinjau', 409);

            $attributes = [
                'review_status' => $data['decision'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $data['note'],
            ];
            if ($type === 'murid') {
                $attributes['needs_review'] = false;
            }
            $record->forceFill($attributes)->save();

            $request->user()->auditLogs()->create([
                'action' => 'reviewed',
                'record_type' => $modelClass,
                'record_id' => $record->id,
                'description' => 'Meninjau absensi '.($type === 'murid' ? 'murid' : 'guru').' tanggal '.$record->attendance_date->format('Y-m-d'),
                'metadata' => ['decision' => $data['decision'], 'note' => $data['note']],
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'Hasil tinjauan absensi berhasil disimpan dan dicatat di audit aktivitas.');
    }

    private function teachersForMonth(string $month): Builder
    {
        [$year, $monthNumber] = explode('-', $month);
        $start = now()->setDate((int) $year, (int) $monthNumber, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return TeacherProfile::query()
            ->with(['user', 'attendances' => fn ($query) => $query
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('attendance_date')])
            ->orderBy('employee_number');
    }

    /**
     * @param resource $output
     * @param list<string> $values
     */
    private function writeCsvRow($output, array $values): void
    {
        $safeValues = array_map(static function (string $value): string {
            return preg_match('/^[\s]*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
        }, $values);

        if (fputcsv($output, $safeValues, ',', '"', '') === false) {
            throw new \RuntimeException('Baris ekspor absensi guru gagal ditulis.');
        }
    }
}
