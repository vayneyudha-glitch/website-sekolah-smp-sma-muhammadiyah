<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PpdbApplication;
use App\Models\PpdbDocument;
use App\Models\PpdbSetting;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\SchoolNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPpdbController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'school_level' => ['nullable', 'in:SMP,SMA'],
            'status' => ['nullable', 'in:draft,submitted,accepted,rejected'],
        ]);
        $applications = PpdbApplication::query()->with('user')->latest();

        if (isset($filters['school_level'])) {
            $applications->where('school_level', $filters['school_level']);
        }
        if (isset($filters['status'])) {
            $applications->where('status', $filters['status']);
        }

        return view('admin.ppdb.index', [
            'applications' => $applications->paginate(25)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(PpdbApplication $application): View
    {
        return view('admin.ppdb.show', [
            'application' => $application->load(['user', 'documents.reviewer', 'schoolClass']),
            'classes' => SchoolClass::query()
                ->where('school_level', $application->school_level)
                ->with('academicYear')
                ->orderByDesc('academic_year_id')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_open' => ['nullable', 'boolean'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'wave_schedule' => ['required', 'string', 'max:10000'],
            'registration_flow' => ['required', 'string', 'max:10000'],
            'costs' => ['required', 'string', 'max:10000'],
        ]);
        $data['is_open'] = (bool) ($data['is_open'] ?? false);

        DB::transaction(function () use ($request, $data): void {
            $setting = PpdbSetting::query()->firstOrCreate(['id' => 1]);
            $setting->update($data);
            $this->audit($request, 'updated', $setting->id, 'Memperbarui informasi publik PPDB');
        });

        return back()->with('status', 'Informasi PPDB berhasil diperbarui.');
    }

    public function reviewDocument(Request $request, PpdbDocument $document): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'review_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000'],
        ]);
        $application = $document->application;
        abort_unless($application->status === PpdbApplication::STATUS_SUBMITTED, 403);

        DB::transaction(function () use ($request, $document, $data, $application): void {
            $document->update([
                'status' => $data['status'],
                'review_reason' => $data['status'] === PpdbDocument::STATUS_REJECTED ? $data['review_reason'] : null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
            $this->audit($request, 'reviewed', $document->id, 'Meninjau berkas '.$document->category.' pendaftaran '.$application->registration_number, [
                'status' => $data['status'],
            ], PpdbDocument::class);
        });

        return back()->with('status', 'Status verifikasi berkas berhasil disimpan.');
    }

    public function decide(Request $request, PpdbApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
            'decision_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:2000'],
            'school_class_id' => ['required_if:decision,accepted', 'nullable', 'integer', 'exists:school_classes,id'],
        ]);
        abort_unless($application->status === PpdbApplication::STATUS_SUBMITTED, 403);

        $class = null;
        if ($data['decision'] === PpdbApplication::STATUS_ACCEPTED) {
            $class = SchoolClass::query()->findOrFail($data['school_class_id']);
            abort_unless($class->school_level === $application->school_level, 422, 'Kelas yang dipilih tidak sesuai dengan jenjang pendaftar.');
            abort_unless(
                ! StudentProfile::query()
                    ->where('nisn', $application->nisn)
                    ->where('user_id', '!=', $application->user_id)
                    ->exists(),
                422,
                'NISN pendaftar sudah digunakan oleh murid lain.',
            );

            $requiredApproved = $application->documents()
                ->whereIn('category', ['kk', 'akta', 'ijazah_rapor', 'pas_foto'])
                ->where('status', PpdbDocument::STATUS_APPROVED)
                ->distinct()
                ->count('category');
            abort_unless($requiredApproved === 4, 422, 'Semua berkas wajib harus berstatus Disetujui sebelum pendaftar diterima.');
        }

        DB::transaction(function () use ($request, $application, $data, $class): void {
            if ($data['decision'] === PpdbApplication::STATUS_ACCEPTED) {
                $student = $application->user;
                $student->forceFill(['role' => User::ROLE_SISWA, 'is_active' => true])->save();
                StudentProfile::query()->updateOrCreate(
                    ['user_id' => $student->id],
                    [
                        'student_number' => $application->registration_number,
                        'nis' => $application->registration_number,
                        'nisn' => $application->nisn,
                        'school_class_id' => $class->id,
                        'status' => 'aktif',
                        'birth_place' => $application->birth_place,
                        'birth_date' => $application->birth_date,
                        'address' => $application->address,
                        'previous_school' => $application->previous_school,
                    ],
                );
            }

            $application->update([
                'status' => $data['decision'],
                'decision_reason' => $data['decision'] === PpdbApplication::STATUS_REJECTED ? $data['decision_reason'] : null,
                'school_class_id' => $class?->id,
                'decided_at' => now(),
            ]);
            $this->audit($request, 'decided', $application->id, 'Keputusan PPDB '.$application->registration_number.': '.$data['decision'], [
                'status' => $data['decision'],
                'school_class_id' => $class?->id,
            ]);
        });
        app(SchoolNotifier::class)->ppdbStatusChanged($application->user, $data['decision']);

        return back()->with('status', 'Keputusan PPDB berhasil disimpan.');
    }

    public function settings(): View
    {
        return view('admin.ppdb.settings', [
            'settings' => PpdbSetting::query()->firstOrCreate(['id' => 1]),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'school_level' => ['nullable', 'in:SMP,SMA'],
            'status' => ['nullable', 'in:draft,submitted,accepted,rejected'],
        ]);
        $applications = PpdbApplication::query()->with('user')->latest();

        if (isset($filters['school_level'])) {
            $applications->where('school_level', $filters['school_level']);
        }
        if (isset($filters['status'])) {
            $applications->where('status', $filters['status']);
        }

        return response()->streamDownload(function () use ($applications): void {
            echo "\xEF\xBB\xBF";
            $output = fopen('php://output', 'w');
            if (! is_resource($output)) {
                throw new RuntimeException('Ekspor PPDB gagal membuka aliran CSV.');
            }
            if (fputcsv($output, ['Nomor Pendaftaran', 'Nama', 'Email', 'NISN', 'Jenjang', 'Jurusan', 'Asal Sekolah', 'Wali', 'Telepon Wali', 'Status', 'Tanggal Daftar']) === false) {
                throw new RuntimeException('Ekspor PPDB gagal menulis judul CSV.');
            }

            foreach ($applications->get() as $application) {
                $values = [
                    $application->registration_number,
                    $application->student_name,
                    $application->user->email,
                    $application->nisn,
                    $application->school_level,
                    $application->major,
                    $application->previous_school,
                    $application->guardian_name,
                    $application->guardian_phone,
                    $application->status,
                    $application->submitted_at?->format('Y-m-d H:i:s'),
                ];
                if (fputcsv($output, array_map($this->safeSpreadsheetCell(...), $values)) === false) {
                    throw new RuntimeException('Ekspor PPDB gagal menulis baris pendaftar.');
                }
            }
            fclose($output);
        }, 'data-ppdb-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function audit(
        Request $request,
        string $action,
        int $recordId,
        string $description,
        ?array $metadata = null,
        string $recordType = PpdbApplication::class,
    ): void {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'record_type' => $recordType,
            'record_id' => $recordId,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
        ]);
    }

    private function safeSpreadsheetCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');

        return preg_match('/^[\s]*[=+\-@]/', $cell) === 1 ? "'".$cell : $cell;
    }
}
