<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolDataRequest;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\ParentChild;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\StudentClassHistory;
use App\Models\ReportCard;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Timetable;
use App\Models\User;
use App\Services\SchoolNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Illuminate\View\View;

class SchoolDataController extends Controller
{
    private const RESOURCES = [
        'accounts' => ['title' => 'Pengelolaan Akun', 'type' => 'accounts'],
        'academic-years' => ['title' => 'Tahun Ajaran', 'type' => 'academic-years'],
        'classes' => ['title' => 'Kelas', 'type' => 'classes'],
        'subjects' => ['title' => 'Mata Pelajaran', 'type' => 'subjects'],
        'students' => ['title' => 'Data Murid', 'type' => 'students'],
        'teachers' => ['title' => 'Data Guru', 'type' => 'teachers'],
        'parents' => ['title' => 'Akun Orang Tua', 'type' => 'parents'],
        'parent-links' => ['title' => 'Hubungan Orang Tua dan Anak', 'type' => 'parent-links'],
        'timetables' => ['title' => 'Jadwal Pelajaran', 'type' => 'timetables'],
        'announcements' => ['title' => 'Pengumuman dan Berita', 'type' => 'announcements'],
    ];

    private const DAYS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    public function index(string $resource): View
    {
        $definition = $this->definition($resource);

        return view('admin.data.index', [
            'resource' => $resource,
            'title' => $definition['title'],
            'records' => $this->records($resource),
            'summary' => fn (Model $record): string => $this->summary($resource, $record),
            'temporaryAccount' => session()->pull('temporary_account'),
            'temporaryPassword' => session()->pull('temporary_password'),
        ]);
    }

    public function create(string $resource): View
    {
        return $this->form($resource, null);
    }

    public function edit(string $resource, int $record): View
    {
        $this->definition($resource);
        $data = $this->findRecord($resource, $record);

        return $this->form($resource, $data);
    }

    public function store(SchoolDataRequest $request, string $resource): RedirectResponse
    {
        $data = $request->validated();
        $temporaryPassword = null;

        $record = DB::transaction(function () use ($data, $resource, &$temporaryPassword): Model {
            $record = $this->persist($resource, null, $data, $temporaryPassword);
            $this->audit('created', $resource, $record, 'Membuat '.$this->definition($resource)['title']);

            return $record;
        });
        if ($resource === 'announcements' && $record instanceof Announcement && $record->is_published) {
            app(SchoolNotifier::class)->announcement($record);
        }

        $response = redirect()->route('admin.data.index', ['resource' => $resource])
            ->with('status', 'Data berhasil ditambahkan.');

        if ($temporaryPassword !== null) {
            $response->with('temporary_account', $this->accountName($resource, $record))
                ->with('temporary_password', $temporaryPassword);
        }

        return $response;
    }

    public function update(SchoolDataRequest $request, string $resource, int $record): RedirectResponse
    {
        $this->definition($resource);
        $data = $request->validated();
        $model = $this->findRecord($resource, $record);
        $before = $model->getAttributes();

        DB::transaction(function () use ($data, $resource, $model, $before): void {
            $this->persist($resource, $model, $data);
            $description = 'Memperbarui '.$this->definition($resource)['title'];
            $metadata = null;

            if ($resource === 'accounts') {
                $description = 'Memperbarui akun '.$model->name;
                $metadata = [
                    'before' => [
                        'name' => $before['name'],
                        'email' => $before['email'],
                        'role' => $before['role'],
                        'is_active' => (bool) $before['is_active'],
                    ],
                    'after' => [
                        'name' => $model->name,
                        'email' => $model->email,
                        'role' => $model->role,
                        'is_active' => $model->is_active,
                    ],
                ];
            }

            $this->audit('updated', $resource, $model, $description, $metadata);
        });
        if ($resource === 'announcements'
            && $model instanceof Announcement
            && $model->is_published
            && (! (bool) ($before['is_published'] ?? false) || empty($before['published_at']))) {
            app(SchoolNotifier::class)->announcement($model);
        }

        return redirect()->route('admin.data.index', ['resource' => $resource])
            ->with('status', 'Perubahan berhasil disimpan.');
    }

    public function destroy(string $resource, int $record): RedirectResponse
    {
        $this->definition($resource);
        abort_if($resource === 'accounts', 404);
        $model = $this->findRecord($resource, $record);
        $reason = $this->deletionBlockReason($resource, $model);

        if ($reason !== null) {
            return back()->with('error', $reason);
        }

        DB::transaction(function () use ($resource, $model): void {
            $this->audit('deleted', $resource, $model, 'Menghapus '.$this->definition($resource)['title']);

            if (in_array($resource, ['students', 'teachers', 'parents'], true)) {
                $model->user->delete();
            } else {
                $model->delete();
            }
        });

        return redirect()->route('admin.data.index', ['resource' => $resource])
            ->with('status', 'Data berhasil dihapus.');
    }

    public function auditIndex(): View
    {
        return view('admin.audit-index', [
            'logs' => AuditLog::query()->with('user')->latest()->paginate(30),
        ]);
    }

    private function form(string $resource, ?Model $record): View
    {
        $definition = $this->definition($resource);

        return view('admin.data.form', [
            'resource' => $resource,
            'title' => $definition['title'],
            'record' => $record,
            'fields' => $this->fields($resource),
            'options' => $this->options(),
            'values' => $this->values($resource, $record),
        ]);
    }

    private function definition(string $resource): array
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        return self::RESOURCES[$resource];
    }

    private function records(string $resource)
    {
        return match ($resource) {
            'accounts' => User::query()->latest()->paginate(20),
            'academic-years' => AcademicYear::query()->latest()->paginate(20),
            'classes' => SchoolClass::query()->with('academicYear')->latest()->paginate(20),
            'subjects' => Subject::query()->latest()->paginate(20),
            'students' => StudentProfile::query()->with(['user', 'schoolClass'])->latest()->paginate(20),
            'teachers' => TeacherProfile::query()->with(['user', 'classes'])->latest()->paginate(20),
            'parents' => User::query()->where('role', User::ROLE_ORANG_TUA)->latest()->paginate(20),
            'parent-links' => ParentChild::query()->with(['parent', 'student'])->latest()->paginate(20),
            'timetables' => Timetable::query()->with(['academicYear', 'schoolClass', 'subject', 'teacher.user'])->latest()->paginate(20),
            'announcements' => Announcement::query()->latest()->paginate(20),
        };
    }

    private function findRecord(string $resource, int $id): Model
    {
        return match ($resource) {
            'accounts' => User::query()->findOrFail($id),
            'academic-years' => AcademicYear::query()->findOrFail($id),
            'classes' => SchoolClass::query()->findOrFail($id),
            'subjects' => Subject::query()->findOrFail($id),
            'students' => StudentProfile::query()->with('user')->findOrFail($id),
            'teachers' => TeacherProfile::query()->with('user')->findOrFail($id),
            'parents' => User::query()->where('role', User::ROLE_ORANG_TUA)->findOrFail($id),
            'parent-links' => ParentChild::query()->findOrFail($id),
            'timetables' => Timetable::query()->findOrFail($id),
            'announcements' => Announcement::query()->findOrFail($id),
        };
    }

    private function fields(string $resource): array
    {
        $fields = [
            'accounts' => [
                ['name' => 'name', 'label' => 'Nama lengkap', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email akun', 'type' => 'email'],
                ['name' => 'role', 'label' => 'Peran akun', 'type' => 'select', 'choices' => [
                    User::ROLE_ADMIN => 'Admin',
                    User::ROLE_GURU => 'Guru',
                    User::ROLE_SISWA => 'Murid',
                    User::ROLE_ORANG_TUA => 'Orang tua',
                    User::ROLE_PENDAFTAR => 'Pendaftar',
                ]],
                ['name' => 'is_active', 'label' => 'Akun aktif', 'type' => 'checkbox', 'default' => true],
            ],
            'academic-years' => [
                ['name' => 'name', 'label' => 'Nama tahun ajaran', 'type' => 'text'],
                ['name' => 'starts_on', 'label' => 'Tanggal mulai', 'type' => 'date'],
                ['name' => 'ends_on', 'label' => 'Tanggal berakhir', 'type' => 'date'],
                ['name' => 'is_active', 'label' => 'Tahun ajaran aktif', 'type' => 'checkbox', 'default' => true],
            ],
            'classes' => [
                ['name' => 'academic_year_id', 'label' => 'Tahun ajaran', 'type' => 'select', 'options' => 'academic_years'],
                ['name' => 'school_level', 'label' => 'Jenjang', 'type' => 'select', 'choices' => ['SMP' => 'SMP', 'SMA' => 'SMA']],
                ['name' => 'grade', 'label' => 'Tingkat kelas', 'type' => 'text'],
                ['name' => 'name', 'label' => 'Nama kelas', 'type' => 'text'],
                ['name' => 'homeroom_teacher_profile_id', 'label' => 'Wali kelas', 'type' => 'select', 'options' => 'teachers', 'optional' => true],
            ],
            'subjects' => [
                ['name' => 'code', 'label' => 'Kode mata pelajaran', 'type' => 'text'],
                ['name' => 'name', 'label' => 'Nama mata pelajaran', 'type' => 'text'],
            ],
            'students' => [
                ['name' => 'name', 'label' => 'Nama lengkap murid', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email akun', 'type' => 'email'],
                ['name' => 'nis', 'label' => 'NIS', 'type' => 'text'],
                ['name' => 'nisn', 'label' => 'NISN', 'type' => 'text', 'optional' => true],
                ['name' => 'school_class_id', 'label' => 'Kelas', 'type' => 'select', 'options' => 'classes'],
                ['name' => 'status', 'label' => 'Status murid', 'type' => 'select', 'choices' => [
                    'aktif' => 'Aktif',
                    'lulus' => 'Lulus',
                    'pindah' => 'Pindah',
                    'nonaktif' => 'Nonaktif',
                ]],
            ],
            'teachers' => [
                ['name' => 'name', 'label' => 'Nama lengkap guru', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email akun', 'type' => 'email'],
                ['name' => 'employee_number', 'label' => 'NIP', 'type' => 'text'],
                ['name' => 'bio', 'label' => 'Perkenalan singkat', 'type' => 'textarea'],
                ['name' => 'class_ids', 'label' => 'Kelas yang diampu', 'type' => 'select', 'options' => 'classes', 'multiple' => true],
                ['name' => 'subject_ids', 'label' => 'Mata pelajaran yang diampu', 'type' => 'select', 'options' => 'subjects', 'multiple' => true],
                ['name' => 'photo', 'label' => 'Foto guru', 'type' => 'file', 'optional' => true],
            ],
            'parents' => [
                ['name' => 'name', 'label' => 'Nama orang tua', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email akun', 'type' => 'email'],
            ],
            'parent-links' => [
                ['name' => 'parent_id', 'label' => 'Akun orang tua', 'type' => 'select', 'options' => 'parents'],
                ['name' => 'student_id', 'label' => 'Anak / murid', 'type' => 'select', 'options' => 'students'],
            ],
            'timetables' => [
                ['name' => 'academic_year_id', 'label' => 'Tahun ajaran', 'type' => 'select', 'options' => 'academic_years'],
                ['name' => 'school_class_id', 'label' => 'Kelas', 'type' => 'select', 'options' => 'classes'],
                ['name' => 'subject_id', 'label' => 'Mata pelajaran', 'type' => 'select', 'options' => 'subjects'],
                ['name' => 'teacher_profile_id', 'label' => 'Guru pengampu', 'type' => 'select', 'options' => 'teachers'],
                ['name' => 'day_of_week', 'label' => 'Hari', 'type' => 'select', 'choices' => self::DAYS],
                ['name' => 'starts_at', 'label' => 'Waktu mulai', 'type' => 'time'],
                ['name' => 'ends_at', 'label' => 'Waktu selesai', 'type' => 'time'],
                ['name' => 'location', 'label' => 'Ruang kelas', 'type' => 'text'],
            ],
            'announcements' => [
                ['name' => 'title', 'label' => 'Judul', 'type' => 'text'],
                ['name' => 'body', 'label' => 'Isi pengumuman / berita', 'type' => 'textarea'],
                ['name' => 'category', 'label' => 'Kategori', 'type' => 'text'],
                ['name' => 'audience_role', 'label' => 'Ditujukan kepada', 'type' => 'select', 'choices' => [
                    User::ROLE_ADMIN => 'Admin',
                    User::ROLE_GURU => 'Guru',
                    User::ROLE_SISWA => 'Murid',
                    User::ROLE_ORANG_TUA => 'Orang tua',
                    User::ROLE_PENDAFTAR => 'Pendaftar',
                ], 'optional' => true],
                ['name' => 'published_at', 'label' => 'Tanggal terbit', 'type' => 'datetime-local', 'optional' => true],
                ['name' => 'image', 'label' => 'Gambar berita', 'type' => 'file', 'optional' => true],
                ['name' => 'is_published', 'label' => 'Tayangkan', 'type' => 'checkbox'],
            ],
        ];

        return $fields[$resource];
    }

    private function options(): array
    {
        return [
            'academic_years' => AcademicYear::query()->orderByDesc('starts_on')->pluck('name', 'id'),
            'classes' => SchoolClass::query()->with('academicYear')->orderBy('name')
                ->get()->mapWithKeys(fn (SchoolClass $class): array => [$class->id => $class->academicYear->name.' - '.$class->name]),
            'subjects' => Subject::query()->orderBy('name')->pluck('name', 'id'),
            'teachers' => TeacherProfile::query()->with('user')->orderBy('id')
                ->get()->mapWithKeys(fn (TeacherProfile $teacher): array => [$teacher->id => $teacher->user->name]),
            'parents' => User::query()->where('role', User::ROLE_ORANG_TUA)->orderBy('name')->pluck('name', 'id'),
            'students' => StudentProfile::query()->with('user')->orderBy('student_number')
                ->get()->mapWithKeys(fn (StudentProfile $student): array => [
                    $student->user_id => $student->user->name.' — '.$student->student_number,
                ]),
        ];
    }

    private function values(string $resource, ?Model $record): array
    {
        if ($record === null) {
            return [];
        }

        return match ($resource) {
            'accounts' => [
                'name' => $record->name,
                'email' => $record->email,
                'role' => $record->role,
                'is_active' => $record->is_active,
            ],
            'students' => [
                'name' => $record->user->name,
                'email' => $record->user->email,
                'nis' => $record->nis,
                'nisn' => $record->nisn,
                'school_class_id' => $record->school_class_id,
                'status' => $record->status,
            ],
            'teachers' => [
                'name' => $record->user->name,
                'email' => $record->user->email,
                'employee_number' => $record->employee_number,
                'bio' => $record->bio,
                'class_ids' => $record->classes()->pluck('school_classes.id')->all(),
                'subject_ids' => $record->subjects()->pluck('subjects.id')->all(),
            ],
            'announcements' => [
                ...$record->getAttributes(),
                'published_at' => $record->published_at?->format('Y-m-d\TH:i'),
                'audience_role' => $record->audience_role ?? '',
            ],
            'timetables' => [
                'academic_year_id' => $record->academic_year_id,
                'school_class_id' => $record->school_class_id,
                'subject_id' => $record->subject_id,
                'teacher_profile_id' => $record->teacher_profile_id,
                'day_of_week' => $record->day_of_week,
                'starts_at' => Str::substr((string) $record->starts_at, 0, 5),
                'ends_at' => Str::substr((string) $record->ends_at, 0, 5),
                'location' => $record->location,
            ],
            'parents' => ['name' => $record->name, 'email' => $record->email],
            'parent-links' => ['parent_id' => $record->parent_id, 'student_id' => $record->student_id],
            default => $record->getAttributes(),
        };
    }

    private function persist(string $resource, ?Model $record, array $data, ?string &$temporaryPassword = null): Model
    {
        if ($resource === 'accounts') {
            if ($record === null) {
                $temporaryPassword = Str::password(14);
                $record = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $temporaryPassword,
                ]);
                $record->forceFill([
                    'role' => $data['role'],
                    'is_active' => (bool) ($data['is_active'] ?? false),
                    'must_change_password' => true,
                    'email_verified_at' => now(),
                ])->save();
                if ($record->role === User::ROLE_PENDAFTAR && ! $record->ppdbApplication()->exists()) {
                    $record->ppdbApplication()->create(['student_name' => $record->name]);
                }
            } else {
                $record->forceFill([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'is_active' => (bool) ($data['is_active'] ?? false),
                ])->save();
                if ($record->role === User::ROLE_PENDAFTAR && ! $record->ppdbApplication()->exists()) {
                    $record->ppdbApplication()->create(['student_name' => $record->name]);
                }
            }

            return $record;
        }

        if (in_array($resource, ['students', 'teachers', 'parents'], true)) {
            $user = $record instanceof StudentProfile || $record instanceof TeacherProfile
                ? $record->user
                : ($record instanceof User ? $record : null);

            if ($user === null) {
                $temporaryPassword = Str::password(14);
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $temporaryPassword,
                ]);
                $user->forceFill([
                    'role' => match ($resource) {
                        'students' => User::ROLE_SISWA,
                        'teachers' => User::ROLE_GURU,
                        default => User::ROLE_ORANG_TUA,
                    },
                    'is_active' => true,
                    'must_change_password' => true,
                    'email_verified_at' => now(),
                ])->save();
            } else {
                $user->update(['name' => $data['name'], 'email' => $data['email']]);
            }

            if ($resource === 'students') {
                $profile = StudentProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nis' => $data['nis'],
                        'nisn' => $data['nisn'] ?? null,
                        'student_number' => $data['nis'],
                        'school_class_id' => $data['school_class_id'],
                        'status' => $data['status'],
                    ],
                );
                $schoolClass = SchoolClass::query()->findOrFail($data['school_class_id']);
                StudentClassHistory::query()->updateOrCreate(
                    [
                        'student_profile_id' => $profile->id,
                        'academic_year_id' => $schoolClass->academic_year_id,
                    ],
                    [
                        'school_class_id' => $schoolClass->id,
                        'status' => $data['status'],
                    ],
                );

                return $profile;
            }

            if ($resource === 'teachers') {
                /** @var UploadedFile|null $photo */
                $photo = $data['photo'] ?? null;
                unset($data['photo']);
                $teacher = TeacherProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'employee_number' => $data['employee_number'],
                        'bio' => $data['bio'] ?? null,
                        ...($photo ? ['photo_path' => $this->storePublicImage($photo, 'teachers')] : []),
                    ],
                );
                $teacher->classes()->sync($data['class_ids'] ?? []);
                $teacher->subjects()->sync($data['subject_ids'] ?? []);

                return $teacher;
            }

            return $user;
        }

        if ($resource === 'parent-links') {
            if ($record !== null) {
                $record->update($data);

                return $record;
            }

            return ParentChild::query()->create($data);
        }

        if ($resource === 'announcements') {
            /** @var UploadedFile|null $image */
            $image = $data['image'] ?? null;
            unset($data['image']);
            $data['author_id'] = $record instanceof Announcement ? $record->author_id : request()->user()->id;
            $slug = Str::slug($data['title']);
            $data['slug'] = $record instanceof Announcement && $record->title === $data['title']
                ? $record->slug
                : (Str::substr($slug !== '' ? $slug : 'berita', 0, 240).'-'.Str::lower(Str::random(6)));
            $data['image_path'] = $image
                ? $this->storePublicImage($image, 'announcements')
                : ($record instanceof Announcement ? $record->image_path : null);
            $data['audience_role'] = $data['audience_role'] ?: null;
            $data['is_published'] = (bool) ($data['is_published'] ?? false);
            $data['published_at'] = $data['is_published']
                ? ($data['published_at'] ?? ($record instanceof Announcement && $record->published_at ? $record->published_at : now()))
                : null;
        }

        if ($resource === 'academic-years') {
            $data['is_active'] = (bool) ($data['is_active'] ?? false);
            if ($data['is_active']) {
                AcademicYear::query()->where('id', '!=', $record?->getKey() ?? 0)->update(['is_active' => false]);
            }
        }

        if ($record !== null) {
            $record->update($data);

            return $record;
        }

        return match ($resource) {
            'academic-years' => AcademicYear::query()->create($data),
            'classes' => SchoolClass::query()->create($data),
            'subjects' => Subject::query()->create($data),
            'timetables' => Timetable::query()->create($data),
            'announcements' => Announcement::query()->create($data),
            default => abort(404),
        };
    }

    private function summary(string $resource, Model $record): string
    {
        return match ($resource) {
            'accounts' => $record->name.' — '.$record->email.' — '.match ($record->role) {
                User::ROLE_ADMIN => 'Admin',
                User::ROLE_GURU => 'Guru',
                User::ROLE_SISWA => 'Murid',
                User::ROLE_ORANG_TUA => 'Orang tua',
                User::ROLE_PENDAFTAR => 'Pendaftar',
            }.' — '.($record->is_active ? 'Aktif' : 'Nonaktif'),
            'academic-years' => $record->name.' ('.($record->is_active ? 'Aktif' : 'Tidak aktif').')',
            'classes' => $record->academicYear->name.' - '.$record->school_level.' '.$record->grade.' '.$record->name,
            'subjects' => $record->code.' - '.$record->name,
            'students' => $record->user->name.' — NIS '.$record->nis.' — NISN '.($record->nisn ?: 'belum diisi').' — '.$record->schoolClass->name.' — '.ucfirst($record->status),
            'teachers' => $record->user->name.' — NIP '.$record->employee_number,
            'parents' => $record->name.' — '.$record->email,
            'parent-links' => $record->parent->name.' — '.$record->student->name,
            'timetables' => $record->schoolClass->name.' — '.$record->subject->name.' — '.$record->teacher->user->name.' — '.self::DAYS[$record->day_of_week].' '.$record->starts_at,
            'announcements' => $record->title.' — '.($record->is_published ? 'Tayang' : 'Draf'),
        };
    }

    private function deletionBlockReason(string $resource, Model $record): ?string
    {
        $used = match ($resource) {
            'academic-years' => SchoolClass::query()->where('academic_year_id', $record->id)->exists()
                || Timetable::query()->where('academic_year_id', $record->id)->exists()
                || StudentClassHistory::query()->where('academic_year_id', $record->id)->exists()
                || ReportCard::query()->where('academic_year_id', $record->id)->exists()
                || ($record->is_active && ! AcademicYear::query()
                    ->where('is_active', true)
                    ->where('id', '!=', $record->id)
                    ->exists()),
            'classes' => StudentProfile::query()->where('school_class_id', $record->id)->exists()
                || StudentClassHistory::query()->where('school_class_id', $record->id)->exists()
                || ReportCard::query()->where('school_class_id', $record->id)->exists()
                || Timetable::query()->where('school_class_id', $record->id)->exists(),
            'students' => StudentClassHistory::query()->where('student_profile_id', $record->id)->exists(),
            'subjects' => Timetable::query()->where('subject_id', $record->id)->exists()
                || \App\Models\GradeComponent::query()->where('subject_id', $record->id)->exists()
                || \App\Models\ReportCardItem::query()->where('subject_id', $record->id)->exists(),
            'teachers' => Timetable::query()->where('teacher_profile_id', $record->id)->exists(),
            default => false,
        };

        return $used ? 'Data masih memiliki hubungan atau riwayat yang dipakai sekolah dan tidak dapat dihapus.' : null;
    }

    private function audit(string $action, string $resource, Model $record, string $description, ?array $metadata = null): void
    {
        AuditLog::query()->create([
            'user_id' => request()->user()->id,
            'action' => $action,
            'record_type' => $resource,
            'record_id' => $record->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }

    private function accountName(string $resource, Model $record): string
    {
        return $record instanceof User ? $record->name : $record->user->name;
    }

    private function storePublicImage(UploadedFile $image, string $directory): string
    {
        $path = $image->storePublicly($directory, 'public');

        if (! is_string($path)) {
            throw new RuntimeException('Gagal menyimpan berkas gambar sekolah.');
        }

        return $path;
    }
}
