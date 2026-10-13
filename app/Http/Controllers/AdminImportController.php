<?php

namespace App\Http\Controllers;

use App\Imports\ImportTemplateExport;
use App\Imports\StagedRowsImport;
use App\Models\AcademicYear;
use App\Models\ParentChild;
use App\Models\SchoolClass;
use App\Models\SchoolImportBatch;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminImportController extends Controller
{
    private const TEMPLATES = [
        'students' => ['name', 'email', 'nis', 'nisn', 'school_class_id'],
        'teachers' => ['name', 'email', 'employee_number'],
        'parents' => ['name', 'email', 'child_nisn'],
        'classes' => ['name', 'grade', 'school_level', 'academic_year_id'],
    ];

    private const INSTRUCTIONS = [
        'students' => [
            ['Kolom', 'Petunjuk'],
            ['name', 'Nama lengkap murid (wajib).'],
            ['email', 'Email akun unik dan valid (wajib).'],
            ['nis', 'NIS unik (wajib).'],
            ['nisn', 'NISN unik; boleh dikosongkan.'],
            ['school_class_id', 'ID kelas yang sudah ada (wajib); lihat menu Kelas.'],
        ],
        'teachers' => [
            ['Kolom', 'Petunjuk'],
            ['name', 'Nama lengkap guru (wajib).'],
            ['email', 'Email akun unik dan valid (wajib).'],
            ['employee_number', 'NIP unik (wajib).'],
        ],
        'parents' => [
            ['Kolom', 'Petunjuk'],
            ['name', 'Nama orang tua/wali (wajib).'],
            ['email', 'Email akun unik dan valid (wajib).'],
            ['child_nisn', 'NISN anak yang sudah diimpor; pisahkan beberapa anak dengan koma. Boleh kosong.'],
        ],
        'classes' => [
            ['Kolom', 'Petunjuk'],
            ['name', 'Nama kelas unik dalam tahun ajaran (wajib).'],
            ['grade', 'Tingkat kelas, misalnya 7, 8, 9, 10, 11, atau 12 (wajib).'],
            ['school_level', 'Jenjang SMP atau SMA (wajib).'],
            ['academic_year_id', 'ID tahun ajaran yang sudah ada (wajib).'],
        ],
    ];

    public function index(): View
    {
        return view('admin.imports.index', [
            'templates' => self::TEMPLATES,
            'passwordExportBatch' => session('password_export_batch'),
        ]);
    }

    public function template(string $type): BinaryFileResponse
    {
        abort_unless(isset(self::TEMPLATES[$type]), 404);

        return Excel::download(
            new ImportTemplateExport(self::TEMPLATES[$type], self::INSTRUCTIONS[$type]),
            'templat-impor-'.$type.'.xlsx',
        );
    }

    public function preview(Request $request): RedirectResponse
    {
        SchoolImportBatch::query()->where('expires_at', '<', now())->update([
            'payload' => '',
            'password_export' => null,
        ]);
        $data = $request->validate([
            'type' => ['required', 'in:students,teachers,parents,classes'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);
        $import = new StagedRowsImport();
        Excel::import($import, $request->file('file'));
        $rows = $import->rows ?? collect();
        $rows = $rows->filter(fn ($row): bool => $row->filter(fn ($value): bool => ! blank($value))->isNotEmpty())->values();

        if ($rows->isEmpty() || $rows->count() > 2000) {
            throw ValidationException::withMessages([
                'file' => $rows->isEmpty()
                    ? 'Berkas tidak memiliki baris data setelah judul kolom.'
                    : 'Maksimal 2.000 baris data dapat diproses dalam satu impor.',
            ]);
        }

        $stagedRows = [];
        $seenEmails = [];
        $seenNumbers = [];
        foreach ($rows as $index => $row) {
            $rowData = $row->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();
            $errors = $this->rowErrors($data['type'], $rowData, $seenEmails, $seenNumbers);
            $stagedRows[] = [
                'number' => $index + 2,
                'valid' => $errors === [],
                'errors' => $errors,
                'data' => $rowData,
            ];
        }

        $id = (string) Str::uuid();
        SchoolImportBatch::query()->create([
            'id' => $id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'payload' => Crypt::encryptString(json_encode($stagedRows, JSON_THROW_ON_ERROR)),
            'expires_at' => now()->addMinutes(30),
        ]);

        return redirect()->route('admin.imports.preview', $id);
    }

    public function showPreview(Request $request, SchoolImportBatch $batch): View
    {
        $this->authorizeBatch($request, $batch);
        if ($batch->expires_at->isPast()) {
            $batch->update(['payload' => '', 'password_export' => null]);
            abort(410, 'Pratinjau telah kedaluwarsa.');
        }
        abort_if($batch->confirmed_at, 410, 'Pratinjau telah diproses.');

        $rows = json_decode(Crypt::decryptString($batch->payload), true, flags: JSON_THROW_ON_ERROR);

        return view('admin.imports.preview', [
            'batch' => $batch,
            'rows' => collect($rows)->map(fn (array $row): array => [
                'number' => $row['number'],
                'valid' => $row['valid'],
                'errors' => $row['errors'],
            ]),
            'validCount' => collect($rows)->where('valid', true)->count(),
            'invalidCount' => collect($rows)->where('valid', false)->count(),
        ]);
    }

    public function confirm(Request $request, SchoolImportBatch $batch): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        $passwordRows = DB::transaction(function () use ($request, $batch): array {
            $locked = SchoolImportBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->confirmed_at || $locked->expires_at->isPast(), 410, 'Pratinjau telah kedaluwarsa atau sudah diproses.');
            $rows = json_decode(Crypt::decryptString($locked->payload), true, flags: JSON_THROW_ON_ERROR);
            $invalid = array_filter($rows, fn (array $row): bool => ! $row['valid']);
            if ($invalid !== []) {
                throw ValidationException::withMessages(['file' => 'Perbaiki semua baris yang salah lalu unggah kembali berkas.']);
            }
            $seenEmails = [];
            $seenNumbers = [];
            foreach ($rows as $row) {
                if ($this->rowErrors($locked->type, $row['data'], $seenEmails, $seenNumbers) !== []) {
                    throw ValidationException::withMessages(['file' => 'Data berubah setelah pratinjau. Unggah kembali berkas untuk memvalidasi data terbaru.']);
                }
            }

            $credentials = [];
            foreach ($rows as $row) {
                $data = $row['data'];
                if ($locked->type === 'classes') {
                    SchoolClass::query()->create([
                        'academic_year_id' => $data['academic_year_id'],
                        'name' => $data['name'],
                        'grade' => $data['grade'],
                        'school_level' => strtoupper($data['school_level']),
                    ]);
                    continue;
                }

                $temporaryPassword = Str::random(20);
                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => strtolower($data['email']),
                    'password' => Hash::make($temporaryPassword),
                ]);
                $user->forceFill([
                    'role' => match ($locked->type) {
                        'students' => User::ROLE_SISWA,
                        'teachers' => User::ROLE_GURU,
                        'parents' => User::ROLE_ORANG_TUA,
                    },
                    'must_change_password' => true,
                ])->save();

                match ($locked->type) {
                    'students' => $this->createStudent($user, $data),
                    'teachers' => TeacherProfile::query()->create([
                        'user_id' => $user->id,
                        'employee_number' => $data['employee_number'],
                    ]),
                    'parents' => $this->createParent($user, $data),
                };

                if ($locked->type !== 'classes') {
                    $credentials[] = ['name' => $user->name, 'email' => $user->email, 'password' => $temporaryPassword];
                }
            }

            $locked->update([
                'confirmed_at' => now(),
                'payload' => '',
                'password_export' => $credentials === [] ? null : Crypt::encryptString(json_encode($credentials, JSON_THROW_ON_ERROR)),
            ]);

            $request->user()->auditLogs()->create([
                'action' => 'imported',
                'record_type' => 'school_imports',
                'description' => 'Impor '.$locked->type.' selesai: '.count($rows).' baris.',
                'metadata' => ['type' => $locked->type, 'count' => count($rows)],
                'ip_address' => $request->ip(),
            ]);

            return $credentials;
        });

        $message = 'Impor selesai: '.count($passwordRows).' akun dibuat.';
        if ($batch->type === 'classes') {
            $message = 'Impor kelas selesai.';
        }
        if ($passwordRows !== []) {
            session()->put('password_export_batch', $batch->id);
        }

        return redirect()->route('admin.imports.index')
            ->with('status', $message);
    }

    public function downloadPasswords(Request $request, SchoolImportBatch $batch): StreamedResponse
    {
        $this->authorizeBatch($request, $batch);
        $credentials = DB::transaction(function () use ($batch): ?array {
            $locked = SchoolImportBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($locked->expires_at->isPast()) {
                $locked->update(['password_export' => null]);
                return null;
            }
            abort_if($locked->confirmed_at === null || $locked->downloaded_at !== null || $locked->password_export === null, 404);
            $credentials = json_decode(Crypt::decryptString($locked->password_export), true, flags: JSON_THROW_ON_ERROR);
            $locked->update(['password_export' => null, 'downloaded_at' => now()]);

            return $credentials;
        });
        abort_if($credentials === null, 404);
        if (session('password_export_batch') === $batch->id) {
            session()->forget('password_export_batch');
        }

        return response()->streamDownload(function () use ($credentials): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Nama', 'Email', 'Kata sandi sementara']);
            foreach ($credentials as $credential) {
                fputcsv($output, array_map($this->spreadsheetSafe(...), $credential));
            }
            fclose($output);
        }, 'akun-sementara-'.$batch->type.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function rowErrors(string $type, array $data, array &$seenEmails, array &$seenNumbers): array
    {
        $errors = [];
        foreach ($data as $value) {
            if (is_string($value) && preg_match('/^[\s\x00-\x20]*[=+\-@]/u', $value)) {
                $errors[] = 'Formula spreadsheet tidak diperbolehkan.';
                break;
            }
        }
        if (blank($data['name'] ?? null)) {
            $errors[] = 'Nama wajib diisi.';
        }
        if ($type !== 'classes') {
            $email = strtolower((string) ($data['email'] ?? ''));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email wajib valid.';
            } elseif (isset($seenEmails[$email]) || User::query()->where('email', $email)->exists()) {
                $errors[] = 'Email sudah digunakan.';
            }
            $seenEmails[$email] = true;
        }
        if ($type === 'students') {
            foreach (['nis', 'nisn'] as $field) {
                $number = trim((string) ($data[$field] ?? ''));
                if ($field === 'nis' && $number === '') {
                    $errors[] = 'NIS wajib diisi.';
                }
                if ($number !== '' && (isset($seenNumbers[$field][$number])
                    || StudentProfile::query()->where($field, $number)->exists()
                    || ($field === 'nis' && StudentProfile::query()->where('student_number', $number)->exists()))) {
                    $errors[] = strtoupper($field).' sudah digunakan.';
                }
                if ($number !== '') {
                    $seenNumbers[$field][$number] = true;
                }
            }
            if (! SchoolClass::query()->whereKey($data['school_class_id'] ?? 0)->exists()) {
                $errors[] = 'Kelas tidak ditemukan.';
            }
        } elseif ($type === 'teachers') {
            $number = trim((string) ($data['employee_number'] ?? ''));
            if ($number === '') {
                $errors[] = 'NIP wajib diisi.';
            } elseif (isset($seenNumbers['employee_number'][$number])
                || TeacherProfile::query()->where('employee_number', $number)->exists()) {
                $errors[] = 'NIP sudah digunakan.';
            }
            $seenNumbers['employee_number'][$number] = true;
        } elseif ($type === 'parents' && filled($data['child_nisn'] ?? null)) {
            $childNisns = array_filter(array_map('trim', explode(',', (string) $data['child_nisn'])));
            if (count($childNisns) !== count(array_unique($childNisns))) {
                $errors[] = 'NISN anak yang sama tidak boleh dicantumkan berulang.';
            }
            foreach ($childNisns as $nisn) {
                if (! StudentProfile::query()->where('nisn', $nisn)->exists()) {
                    $errors[] = 'NISN anak '.$nisn.' tidak ditemukan.';
                }
            }
        } elseif ($type === 'classes') {
            if (blank($data['grade'] ?? null) || ! in_array(strtoupper((string) ($data['school_level'] ?? '')), ['SMP', 'SMA'], true)) {
                $errors[] = 'Tingkat dan jenjang SMP/SMA wajib diisi.';
            }
            if (! AcademicYear::query()->whereKey($data['academic_year_id'] ?? 0)->exists()) {
                $errors[] = 'Tahun ajaran tidak ditemukan.';
            } elseif (SchoolClass::query()
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('name', $data['name'] ?? '')
                ->exists()) {
                $errors[] = 'Nama kelas sudah digunakan pada tahun ajaran tersebut.';
            } else {
                $classKey = $data['academic_year_id'].'|'.mb_strtolower((string) ($data['name'] ?? ''));
                if (isset($seenNumbers['class'][$classKey])) {
                    $errors[] = 'Nama kelas duplikat pada berkas.';
                }
                $seenNumbers['class'][$classKey] = true;
            }
        }

        return array_values(array_unique($errors));
    }

    private function createStudent(User $user, array $data): void
    {
        $profile = StudentProfile::query()->create([
            'user_id' => $user->id,
            'student_number' => $data['nis'],
            'nis' => $data['nis'],
            'nisn' => filled($data['nisn'] ?? null) ? $data['nisn'] : null,
            'school_class_id' => $data['school_class_id'],
            'status' => 'aktif',
        ]);
        $schoolClass = SchoolClass::query()->findOrFail($data['school_class_id']);
        \App\Models\StudentClassHistory::query()->create([
            'student_profile_id' => $profile->id,
            'academic_year_id' => $schoolClass->academic_year_id,
            'school_class_id' => $schoolClass->id,
            'status' => 'aktif',
        ]);
    }

    private function createParent(User $user, array $data): void
    {
        foreach (array_filter(array_map('trim', explode(',', (string) ($data['child_nisn'] ?? '')))) as $nisn) {
            $studentUserId = StudentProfile::query()->where('nisn', $nisn)->value('user_id');
            ParentChild::query()->create(['parent_id' => $user->id, 'student_id' => $studentUserId]);
        }
    }

    private function authorizeBatch(Request $request, SchoolImportBatch $batch): void
    {
        abort_unless($request->user()->role === User::ROLE_ADMIN && (int) $batch->user_id === $request->user()->id, 403);
    }

    private function spreadsheetSafe(string $value): string
    {
        return preg_match('/^[\s\x00-\x20]*[=+\-@]/u', $value) ? "'".$value : $value;
    }
}
