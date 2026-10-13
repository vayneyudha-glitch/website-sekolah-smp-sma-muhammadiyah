<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\StudentClassHistory;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolYearAdvancementController extends Controller
{
    public function index(): View
    {
        $source = AcademicYear::query()->where('is_active', true)->firstOrFail();

        return view('admin.school-year-advancement.index', [
            'source' => $source,
            'years' => AcademicYear::query()->where('id', '!=', $source->id)->orderByDesc('starts_on')->get(),
            'classes' => $source->classes()->withCount(['students' => fn ($query) => $query->where('status', 'aktif')])
                ->orderBy('name')->get(),
            'targetClasses' => SchoolClass::query()->with('academicYear')->where('academic_year_id', '!=', $source->id)->orderBy('name')->get(),
            'students' => StudentProfile::query()->with(['user', 'schoolClass'])
                ->where('status', 'aktif')
                ->whereHas('schoolClass', fn ($query) => $query->where('academic_year_id', $source->id))
                ->orderBy('school_class_id')->orderBy('id')->get(),
        ]);
    }

    public function preview(Request $request): View
    {
        [$year, $mappings, $repeatTo] = $this->validatedPlan($request);
        $source = AcademicYear::query()->where('is_active', true)->firstOrFail();
        $students = StudentProfile::query()
            ->with(['user', 'schoolClass'])
            ->where('status', 'aktif')
            ->whereHas('schoolClass', fn ($query) => $query->where('academic_year_id', $source->id))
            ->orderBy('school_class_id')
            ->orderBy('id')
            ->get();
        $preview = $students->map(fn (StudentProfile $student): array => [
            'id' => $student->id,
            'name' => $student->user->name,
            'from' => $student->schoolClass->name,
            'to' => isset($repeatTo[$student->id])
                ? SchoolClass::query()->findOrFail($repeatTo[$student->id])->name.' (mengulang)'
                : ($mappings[$student->school_class_id] === 'lulus'
                    ? 'Lulus / alumni'
                    : SchoolClass::query()->findOrFail($mappings[$student->school_class_id])->name),
        ]);

        return view('admin.school-year-advancement.preview', [
            'source' => $source,
            'target' => $year,
            'preview' => $preview,
            'mappings' => $mappings,
            'repeatTo' => $repeatTo,
        ]);
    }

    public function execute(Request $request): RedirectResponse
    {
        [$target, $mappings, $repeatTo] = $this->validatedPlan($request);
        $source = AcademicYear::query()->where('is_active', true)->firstOrFail();

        $summary = DB::transaction(function () use ($request, $source, $target, $mappings, $repeatTo): array {
            AcademicYear::query()->where('is_active', true)->update(['is_active' => false]);
            $target->update(['is_active' => true]);
            $counts = ['promoted' => 0, 'repeated' => 0, 'graduated' => 0];

            $students = StudentProfile::query()
                ->with('user')
                ->where('status', 'aktif')
                ->whereHas('schoolClass', fn ($query) => $query->where('academic_year_id', $source->id))
                ->lockForUpdate()
                ->get();

            foreach ($students as $student) {
                $destination = $repeatTo[$student->id] ?? $mappings[$student->school_class_id];
                $isRepeating = isset($repeatTo[$student->id]);
                $history = [
                    'student_profile_id' => $student->id,
                    'academic_year_id' => $target->id,
                    'school_class_id' => $destination === 'lulus' ? null : (int) $destination,
                    'status' => $destination === 'lulus' ? 'alumni' : 'aktif',
                ];
                StudentClassHistory::query()->create($history);

                if ($destination === 'lulus') {
                    $student->update(['status' => 'alumni']);
                    $student->user->forceFill(['is_active' => false])->save();
                    $counts['graduated']++;
                } else {
                    $student->update(['school_class_id' => (int) $destination, 'status' => 'aktif']);
                    $counts[$isRepeating ? 'repeated' : 'promoted']++;
                }
            }

            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'advanced',
                'record_type' => 'student_class_histories',
                'description' => 'Memproses kenaikan kelas ke tahun ajaran '.$target->name.'.',
                'metadata' => $counts + ['academic_year_id' => $target->id],
                'ip_address' => $request->ip(),
            ]);

            return $counts;
        });

        return redirect()->route('admin.school-years.advance.index')
            ->with('status', 'Kenaikan kelas tersimpan: '.$summary['promoted'].' murid naik, '.$summary['repeated'].' mengulang, '.$summary['graduated'].' lulus.');
    }

    /**
     * @return array{AcademicYear, array<int, string>, array<int, int>}
     */
    private function validatedPlan(Request $request): array
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'mapping' => ['required', 'array'],
            'mapping.*' => ['required', 'string', 'max:20'],
            'repeat_to' => ['nullable', 'array'],
            'repeat_to.*' => ['nullable', 'integer', 'exists:school_classes,id'],
        ]);
        $source = AcademicYear::query()->where('is_active', true)->firstOrFail();
        $target = AcademicYear::query()->findOrFail($data['academic_year_id']);
        if ($source->is($target)) {
            throw ValidationException::withMessages(['academic_year_id' => 'Pilih tahun ajaran yang berbeda dari tahun ajaran aktif.']);
        }
        if ($target->starts_on->lessThanOrEqualTo($source->starts_on)
            || $target->ends_on->lessThanOrEqualTo($source->ends_on)) {
            throw ValidationException::withMessages(['academic_year_id' => 'Pilih tahun ajaran yang tanggal mulai dan berakhirnya setelah tahun ajaran aktif.']);
        }

        $sourceClasses = $source->classes()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if (array_diff($sourceClasses, array_map('intval', array_keys($data['mapping']))) !== []) {
            throw ValidationException::withMessages(['mapping' => 'Petakan semua kelas pada tahun ajaran aktif.']);
        }
        foreach ($data['mapping'] as $classId => $destination) {
            if ($destination !== 'lulus' && ! $target->classes()->whereKey($destination)->exists()) {
                throw ValidationException::withMessages(['mapping.'.$classId => 'Pilih kelas tujuan yang tersedia pada tahun ajaran baru atau tandai lulus.']);
            }
        }

        $activeStudentIds = StudentProfile::query()->where('status', 'aktif')
            ->whereIn('school_class_id', $sourceClasses)->pluck('id')->map(fn ($id): int => (int) $id);
        if (StudentClassHistory::query()->where('academic_year_id', $target->id)
            ->whereIn('student_profile_id', $activeStudentIds)->exists()) {
            throw ValidationException::withMessages(['academic_year_id' => 'Tahun ajaran tujuan sudah memiliki riwayat kelas untuk murid aktif; pilih tahun ajaran yang belum diproses.']);
        }
        $repeatTo = [];
        foreach ($data['repeat_to'] ?? [] as $studentId => $destination) {
            if (blank($destination)) {
                continue;
            }
            if (! $activeStudentIds->contains((int) $studentId)
                || ! $target->classes()->whereKey($destination)->exists()) {
                throw ValidationException::withMessages(['repeat_to.'.$studentId => 'Pilih kelas pengulangan pada tahun ajaran baru untuk murid aktif.']);
            }
            $repeatTo[(int) $studentId] = (int) $destination;
        }

        return [$target, $data['mapping'], $repeatTo];
    }
}
