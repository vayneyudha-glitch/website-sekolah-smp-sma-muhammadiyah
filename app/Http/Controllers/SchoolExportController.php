<?php

namespace App\Http\Controllers;

use App\Exports\SchoolDatasetExport;
use App\Exports\PaymentsExport;
use App\Models\AssignmentSubmission;
use App\Models\Payment;
use App\Models\PpdbApplication;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\TeacherAttendance;
use App\Models\SchoolClass;
use App\Models\TeacherProfile;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class SchoolExportController extends Controller
{
    private const HEADINGS = [
        'students' => ['NIS', 'NISN', 'Nama murid', 'Kelas', 'Status'],
        'teachers' => ['NIP', 'Nama guru', 'Email'],
        'student-attendance' => ['Tanggal', 'NIS', 'Nama murid', 'Kelas', 'Status', 'Jam absen'],
        'attendance' => ['Tanggal', 'NIS', 'Nama murid', 'Kelas', 'Status', 'Jam absen'],
        'teacher-attendance' => ['Tanggal', 'NIP', 'Nama guru', 'Jam masuk', 'Jam pulang', 'Status verifikasi'],
        'grades' => ['NIS', 'Nama murid', 'Kelas', 'Mata pelajaran', 'Tugas', 'Nilai', 'Waktu dinilai'],
        'payments' => ['Nomor tagihan', 'NIS', 'Nama murid', 'Jenis tagihan', 'Nominal', 'Status', 'Waktu bayar'],
        'ppdb' => ['Nomor pendaftaran', 'Nama calon murid', 'Jenjang', 'Status', 'Tanggal daftar', 'Nama wali', 'Telepon wali'],
    ];

    public function adminIndex(): \Illuminate\View\View
    {
        return view('exports.index', [
            'datasets' => [
                'students' => 'Data murid',
                'teachers' => 'Data guru',
                'student-attendance' => 'Rekap absensi murid',
                'teacher-attendance' => 'Rekap absensi guru',
                'grades' => 'Rekap nilai',
                'payments' => 'Rekap pembayaran',
                'ppdb' => 'Daftar pendaftar PPDB',
            ],
            'classes' => SchoolClass::query()->orderBy('school_level')->orderBy('grade')->orderBy('name')->get(),
            'routeName' => 'admin.exports.download',
        ]);
    }

    public function teacherIndex(Request $request): \Illuminate\View\View
    {
        $teacher = $request->user()->teacherProfile()->firstOrFail();
        $classes = $teacher->classes()->with('academicYear')->get()
            ->merge($teacher->homeroomClasses()->with('academicYear')->get())
            ->merge(
                Timetable::query()->with('schoolClass.academicYear')
                    ->where('teacher_profile_id', $teacher->id)
                    ->get()->pluck('schoolClass'),
            )
            ->unique('id')
            ->values();

        return view('exports.index', [
            'datasets' => ['attendance' => 'Rekap absensi kelas', 'grades' => 'Rekap nilai kelas'],
            'classes' => $classes,
            'routeName' => 'guru.exports.download',
        ]);
    }

    public function admin(Request $request, string $dataset, string $format)
    {
        $filters = $this->filters($request);
        $query = $this->queryForDataset($dataset, $filters);

        return $this->download($query, $dataset, $format);
    }

    public function teacher(Request $request, string $dataset, string $format)
    {
        $filters = $this->filters($request);
        $teacher = $request->user()->teacherProfile()->firstOrFail();
        $courses = Timetable::query()->where('teacher_profile_id', $teacher->id)->get(['school_class_id', 'subject_id']);
        $classIds = $teacher->classes()->pluck('school_classes.id')
            ->merge($teacher->homeroomClasses()->pluck('school_classes.id'))
            ->merge($courses->pluck('school_class_id'))
            ->unique();
        $query = $dataset === 'attendance'
            ? $this->queryForDataset('student-attendance', $filters)->whereHas('student', fn (Builder $students) => $students->whereIn('school_class_id', $classIds))
            : $this->queryForDataset('grades', $filters)
                ->whereHas('assignment', function (Builder $assignments) use ($request, $classIds, $courses): void {
                    $assignments->where(function (Builder $allowed) use ($request, $classIds, $courses): void {
                        $allowed->where('teacher_id', $request->user()->id);
                        if ($classIds->isNotEmpty()) {
                            $allowed->orWhereIn('school_class_id', $classIds);
                        }
                        foreach ($courses as $course) {
                            $allowed->orWhere(fn (Builder $scope) => $scope
                                ->where('school_class_id', $course->school_class_id)
                                ->where('subject_id', $course->subject_id));
                        }
                    });
                });

        if (isset($filters['school_class_id'])) {
            abort_unless($classIds->contains((int) $filters['school_class_id'])
                || $courses->contains(fn (Timetable $course): bool => $course->school_class_id === (int) $filters['school_class_id']), 403);
            $query->whereHas('student', fn (Builder $students) => $students->where('school_class_id', $filters['school_class_id']));
        }

        return $this->download($query, $dataset === 'attendance' ? 'attendance' : 'grades', $format);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    private function queryForDataset(string $dataset, array $filters): Builder
    {
        $query = match ($dataset) {
            'students' => StudentProfile::query()->with(['user', 'schoolClass']),
            'teachers' => TeacherProfile::query()->with('user'),
            'student-attendance', 'attendance' => StudentAttendance::query()->with(['student.user', 'student.schoolClass']),
            'teacher-attendance' => TeacherAttendance::query()->with('teacher.user'),
            'grades' => AssignmentSubmission::query()->with(['student.user', 'student.schoolClass', 'assignment.subject'])->whereNotNull('graded_at'),
            'payments' => Payment::query()->with(['invoice.studentProfile.user', 'invoice.studentProfile.schoolClass']),
            'ppdb' => PpdbApplication::query(),
            default => abort(404),
        };

        if (isset($filters['school_class_id'])) {
            match ($dataset) {
                'students' => $query->where('school_class_id', $filters['school_class_id']),
                'ppdb' => $query->where('school_class_id', $filters['school_class_id']),
                'student-attendance', 'attendance' => $query->whereHas('student', fn (Builder $students) => $students->where('school_class_id', $filters['school_class_id'])),
                'grades' => $query->whereHas('student', fn (Builder $students) => $students->where('school_class_id', $filters['school_class_id'])),
                'payments' => $query->whereHas('invoice.studentProfile', fn (Builder $students) => $students->where('school_class_id', $filters['school_class_id'])),
                default => null,
            };
        }

        if (isset($filters['from']) || isset($filters['to'])) {
            $dateColumn = match ($dataset) {
                'student-attendance', 'attendance', 'teacher-attendance' => 'attendance_date',
                'grades' => 'graded_at',
                'payments' => 'paid_at',
                'ppdb' => 'submitted_at',
                default => 'created_at',
            };
            $query->when($filters['from'] ?? null, fn (Builder $builder, string $from) => $builder->whereDate($dateColumn, '>=', $from))
                ->when($filters['to'] ?? null, fn (Builder $builder, string $to) => $builder->whereDate($dateColumn, '<=', $to));
        }

        if (isset($filters['status'])) {
            match ($dataset) {
                'student-attendance' => $query->where('status', $filters['status']),
                'teacher-attendance' => $query->where('is_suspicious', $filters['status'] === 'mencurigakan'),
                'payments' => $query->where('status', $filters['status']),
                'ppdb' => $query->where('status', $filters['status']),
                default => null,
            };
        }

        return $query->orderBy('id');
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function download(Builder $query, string $dataset, string $format)
    {
        $filename = 'ekspor-'.$dataset.'-'.now()->format('Ymd-His');
        if ($format === 'xlsx') {
            $export = $dataset === 'payments'
                ? new PaymentsExport($query)
                : new SchoolDatasetExport($query, self::HEADINGS[$dataset], $dataset);

            return Excel::download($export, $filename.'.xlsx');
        }

        $total = (clone $query)->count();
        abort_if($total > 1000, 422, 'PDF dibatasi hingga 1.000 baris. Tambahkan filter tanggal atau kelas untuk memperkecil hasil.');
        $rows = $query->lazyById(250)->collect();

        return Pdf::loadView('exports.dataset', [
            'title' => $this->datasetTitle($dataset),
            'headings' => self::HEADINGS[$dataset],
            'rows' => $this->pdfRows($rows, $dataset),
        ])->setPaper('a4', 'landscape')->download($filename.'.pdf');
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'school_class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'status' => ['nullable', 'in:hadir,izin,sakit,alpa,mencurigakan,normal,lunas,menunggu,gagal,kedaluwarsa,dibatalkan,belum_bayar,draft,submitted,accepted,rejected'],
        ]);
    }

    /**
     * @param  Collection<int, \Illuminate\Database\Eloquent\Model>  $rows
     * @return Collection<int, array<int, mixed>>
     */
    private function pdfRows(Collection $rows, string $dataset): Collection
    {
        return $rows->map(fn ($row) => array_map(
            fn (mixed $value): mixed => is_string($value) && preg_match('/^[=+\-@]/u', $value) === 1 ? "'".$value : $value,
            SchoolDatasetExport::valuesFor($row, $dataset),
        ));
    }

    private function datasetTitle(string $dataset): string
    {
        return match ($dataset) {
            'students' => 'Data murid',
            'teachers' => 'Data guru',
            'student-attendance', 'attendance' => 'Rekap absensi murid',
            'teacher-attendance' => 'Rekap absensi guru',
            'grades' => 'Rekap nilai',
            'payments' => 'Rekap pembayaran',
            'ppdb' => 'Daftar pendaftar PPDB',
        };
    }
}
