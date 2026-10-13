<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SchoolDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $resource = $this->route('resource');
        $recordId = $this->route('record');
        $userId = match ($resource) {
            'students' => StudentProfile::query()->whereKey($recordId)->value('user_id'),
            'teachers' => TeacherProfile::query()->whereKey($recordId)->value('user_id'),
            'parents', 'accounts' => $recordId,
            default => null,
        };

        return match ($resource) {
            'accounts' => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
                'role' => ['required', Rule::in(User::ROLES)],
                'is_active' => ['nullable', 'boolean'],
            ],
            'academic-years' => [
                'name' => ['required', 'string', 'max:255', Rule::unique('academic_years', 'name')->ignore($recordId)],
                'starts_on' => ['required', 'date'],
                'ends_on' => ['required', 'date', 'after:starts_on'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'classes' => [
                'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('school_classes', 'name')
                        ->where('academic_year_id', $this->input('academic_year_id'))
                        ->ignore($recordId),
                ],
                'grade' => ['required', 'string', 'max:20'],
                'school_level' => ['required', Rule::in(['SMP', 'SMA'])],
                'homeroom_teacher_profile_id' => ['nullable', 'integer', 'exists:teacher_profiles,id'],
            ],
            'subjects' => [
                'code' => ['required', 'string', 'max:30', Rule::unique('subjects', 'code')->ignore($recordId)],
                'name' => ['required', 'string', 'max:255', Rule::unique('subjects', 'name')->ignore($recordId)],
            ],
            'students' => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
                'nis' => ['required', 'string', 'max:50', Rule::unique('student_profiles', 'nis')->ignore($recordId)],
                'nisn' => ['nullable', 'digits:10', Rule::unique('student_profiles', 'nisn')->ignore($recordId)],
                'school_class_id' => ['required', 'integer', 'exists:school_classes,id'],
                'status' => ['required', Rule::in(['aktif', 'lulus', 'pindah', 'nonaktif'])],
            ],
            'teachers' => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
                'employee_number' => ['required', 'string', 'max:50', Rule::unique('teacher_profiles', 'employee_number')->ignore($recordId)],
                'bio' => ['nullable', 'string', 'max:5000'],
                'class_ids' => ['nullable', 'array'],
                'class_ids.*' => ['integer', 'distinct', 'exists:school_classes,id'],
                'subject_ids' => ['nullable', 'array'],
                'subject_ids.*' => ['integer', 'distinct', 'exists:subjects,id'],
                'photo' => ['nullable', 'image', 'max:5120'],
            ],
            'parents' => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            ],
            'parent-links' => [
                'parent_id' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where('role', User::ROLE_ORANG_TUA),
                    Rule::unique('parent_child', 'parent_id')
                        ->where('student_id', $this->input('student_id'))
                        ->ignore($recordId),
                ],
                'student_id' => ['required', 'integer', Rule::exists('student_profiles', 'user_id')],
            ],
            'timetables' => [
                'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
                'school_class_id' => ['required', 'integer', 'exists:school_classes,id'],
                'subject_id' => ['required', 'integer', 'exists:subjects,id'],
                'teacher_profile_id' => ['required', 'integer', 'exists:teacher_profiles,id'],
                'day_of_week' => ['required', 'integer', 'between:1,7'],
                'starts_at' => ['required', 'date_format:H:i'],
                'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
                'location' => ['nullable', 'string', 'max:100'],
            ],
            'announcements' => [
                'title' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string', 'max:30000'],
                'category' => ['required', 'string', 'max:80'],
                'audience_role' => ['nullable', Rule::in(User::ROLES)],
                'is_published' => ['nullable', 'boolean'],
                'published_at' => ['nullable', 'date'],
                'image' => ['nullable', 'image', 'max:5120'],
            ],
            default => [],
        };
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->route('resource') === 'academic-years'
                && ! $this->boolean('is_active')
                && ! \App\Models\AcademicYear::query()
                    ->where('is_active', true)
                    ->when($this->route('record'), fn ($query) => $query->where('id', '!=', $this->route('record')))
                    ->exists()) {
                $validator->errors()->add('is_active', 'Harus ada satu tahun ajaran yang aktif.');
            }

            if ($this->route('resource') === 'accounts'
                && (int) $this->route('record') === $this->user()?->id
                && ($this->input('role') !== $this->user()?->role || ! $this->boolean('is_active'))) {
                $validator->errors()->add('role', 'Anda tidak dapat mengubah peran atau menonaktifkan akun sendiri.');
            }

            if ($this->route('resource') !== 'timetables'
                || $validator->errors()->isNotEmpty()
                || ! $this->filled('teacher_profile_id')
                || ! $this->filled('school_class_id')
                || ! $this->filled('academic_year_id')
                || ! $this->filled('subject_id')
                || ! $this->filled('day_of_week')
                || ! $this->filled('starts_at')
                || ! $this->filled('ends_at')) {
                return;
            }

            $classId = $this->integer('school_class_id');
            $teacherId = $this->integer('teacher_profile_id');
            $yearId = $this->integer('academic_year_id');

            if (! SchoolClass::query()->whereKey($classId)->where('academic_year_id', $yearId)->exists()) {
                $validator->errors()->add('school_class_id', 'Kelas tidak termasuk dalam tahun ajaran yang dipilih.');
            }

            if (! DB::table('class_teacher')->where('school_class_id', $classId)->where('teacher_profile_id', $teacherId)->exists()) {
                $validator->errors()->add('teacher_profile_id', 'Guru harus ditugaskan ke kelas yang dipilih terlebih dahulu.');
            }

            if (! DB::table('subject_teacher')->where('teacher_profile_id', $teacherId)->where('subject_id', $this->integer('subject_id'))->exists()) {
                $validator->errors()->add('subject_id', 'Mata pelajaran harus ditugaskan kepada guru tersebut terlebih dahulu.');
            }

            $overlaps = fn (string $column, int $id): bool => Timetable::query()
                ->where('academic_year_id', $yearId)
                ->where($column, $id)
                ->where('day_of_week', $this->integer('day_of_week'))
                ->where('id', '!=', $this->route('record') ?? 0)
                ->where('starts_at', '<', $this->input('ends_at'))
                ->where('ends_at', '>', $this->input('starts_at'))
                ->exists();

            $teacherConflict = $overlaps('teacher_profile_id', $teacherId);
            $classConflict = $overlaps('school_class_id', $classId);

            if ($teacherConflict || $classConflict) {
                $validator->errors()->add('starts_at', $teacherConflict
                    ? 'Jadwal guru bertabrakan dengan jadwal lain.'
                    : 'Jadwal kelas bertabrakan dengan jadwal lain.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'email' => 'Masukkan alamat email yang valid.',
            'max.string' => ':attribute tidak boleh lebih dari :max karakter.',
            'unique' => ':attribute sudah digunakan.',
            'exists' => 'Pilihan :attribute tidak ditemukan.',
            'integer' => ':attribute harus berupa angka.',
            'boolean' => ':attribute harus berupa pilihan yang valid.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'in' => 'Pilihan :attribute tidak valid.',
            'array' => ':attribute tidak valid.',
            'distinct' => 'Pilihan :attribute tidak boleh berulang.',
            'between' => ':attribute harus di antara :min dan :max.',
            'date_format' => 'Format :attribute tidak valid.',
            'ends_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'nisn.digits' => 'NISN harus terdiri dari 10 angka.',
            'starts_on.required' => 'Tanggal mulai tahun ajaran wajib diisi.',
            'ends_on.after' => 'Tanggal akhir harus setelah tanggal mulai.',
            'photo.image' => 'Foto guru harus berupa berkas gambar.',
            'image.image' => 'Gambar berita harus berupa berkas gambar.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'alamat email',
            'bio' => 'perkenalan singkat',
            'is_published' => 'status tayang',
            'starts_on' => 'tanggal mulai',
            'ends_on' => 'tanggal berakhir',
            'is_active' => 'status aktif',
            'academic_year_id' => 'tahun ajaran',
            'school_level' => 'jenjang',
            'grade' => 'tingkat kelas',
            'code' => 'kode mata pelajaran',
            'nis' => 'NIS',
            'nisn' => 'NISN',
            'status' => 'status murid',
            'school_class_id' => 'kelas',
            'employee_number' => 'NIP',
            'class_ids' => 'kelas yang diampu',
            'subject_ids' => 'mata pelajaran yang diampu',
            'homeroom_teacher_profile_id' => 'wali kelas',
            'parent_id' => 'akun orang tua',
            'student_id' => 'murid',
            'subject_id' => 'mata pelajaran',
            'teacher_profile_id' => 'guru pengampu',
            'day_of_week' => 'hari',
            'starts_at' => 'waktu mulai',
            'ends_at' => 'waktu selesai',
            'location' => 'ruang kelas',
            'title' => 'judul',
            'body' => 'isi pengumuman atau berita',
            'category' => 'kategori',
            'audience_role' => 'peran penerima',
            'published_at' => 'tanggal terbit',
            'role' => 'peran akun',
            'photo' => 'foto',
            'image' => 'gambar',
        ];
    }
}
