<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\BillType;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new LogicException('DemoDataSeeder hanya boleh dijalankan di lingkungan local.');
        }

        $password = trim((string) config('school.demo_password'));
        $generatedPassword = $password === '';
        if ($generatedPassword) {
            $password = Str::password(20);
        }

        DB::transaction(function () use ($password): void {
            $year = AcademicYear::query()->updateOrCreate(
                ['name' => '2026/2027 (Contoh)'],
                ['starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true],
            );

            $teachers = collect([
                ['name' => 'Guru Contoh Satu', 'email' => 'guru.satu@example.test', 'number' => 'DEMO-GURU-001'],
                ['name' => 'Guru Contoh Dua', 'email' => 'guru.dua@example.test', 'number' => 'DEMO-GURU-002'],
            ])->mapWithKeys(function (array $data) use ($password): array {
                $user = $this->demoUser($data['name'], $data['email'], User::ROLE_GURU, $password);
                $profile = TeacherProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['employee_number' => $data['number'], 'bio' => '[Profil guru data contoh lokal]'],
                );

                return [$data['number'] => $profile];
            });

            $classRows = [];
            foreach ([
                ['name' => '7 Contoh', 'grade' => '7', 'level' => 'SMP', 'teacher' => 'DEMO-GURU-001'],
                ['name' => '8 Contoh', 'grade' => '8', 'level' => 'SMP', 'teacher' => 'DEMO-GURU-001'],
                ['name' => '9 Contoh', 'grade' => '9', 'level' => 'SMP', 'teacher' => 'DEMO-GURU-001'],
                ['name' => '10 Contoh', 'grade' => '10', 'level' => 'SMA', 'teacher' => 'DEMO-GURU-002'],
                ['name' => '11 Contoh', 'grade' => '11', 'level' => 'SMA', 'teacher' => 'DEMO-GURU-002'],
                ['name' => '12 Contoh', 'grade' => '12', 'level' => 'SMA', 'teacher' => 'DEMO-GURU-002'],
            ] as $data) {
                $class = SchoolClass::query()->updateOrCreate(
                    ['academic_year_id' => $year->id, 'name' => $data['name']],
                    [
                        'grade' => $data['grade'],
                        'school_level' => $data['level'],
                        'homeroom_teacher_profile_id' => $teachers[$data['teacher']]->id,
                    ],
                );
                $classRows[] = ['class' => $class, 'teacher' => $data['teacher']];
            }

            $subjects = collect([
                ['code' => 'DEMO-MAT', 'name' => 'Matematika (Contoh)'],
                ['code' => 'DEMO-IPA', 'name' => 'Ilmu Pengetahuan Alam (Contoh)'],
            ])->mapWithKeys(fn (array $data): array => [
                $data['code'] => Subject::query()->updateOrCreate(['code' => $data['code']], ['name' => $data['name']]),
            ]);

            foreach (['DEMO-GURU-001' => ['DEMO-MAT'], 'DEMO-GURU-002' => ['DEMO-IPA']] as $teacherNumber => $subjectCodes) {
                $teachers[$teacherNumber]->subjects()->sync($subjects->only($subjectCodes)->pluck('id'));
            }
            foreach ($classRows as $row) {
                $teacher = $teachers[$row['teacher']];
                $teacher->classes()->syncWithoutDetaching([$row['class']->id]);
                $subject = $teacher->subjects()->firstOrFail();
                Timetable::query()->updateOrCreate(
                    [
                        'school_class_id' => $row['class']->id,
                        'day_of_week' => 1,
                        'starts_at' => '07:30:00',
                    ],
                    [
                        'academic_year_id' => $year->id,
                        'subject_id' => $subject->id,
                        'teacher_profile_id' => $teacher->id,
                        'ends_at' => '08:30:00',
                        'location' => '[Ruang contoh]',
                    ],
                );
            }

            foreach ([
                ['name' => 'Murid Contoh Satu', 'email' => 'murid.satu@example.test', 'number' => 'DEMO-SMP-001', 'class' => '7 Contoh'],
                ['name' => 'Murid Contoh Dua', 'email' => 'murid.dua@example.test', 'number' => 'DEMO-SMA-001', 'class' => '10 Contoh'],
            ] as $data) {
                $user = $this->demoUser($data['name'], $data['email'], User::ROLE_SISWA, $password);
                StudentProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'student_number' => $data['number'],
                        'nis' => $data['number'],
                        'nisn' => null,
                        'school_class_id' => collect($classRows)->firstWhere('class.name', $data['class'])['class']->id,
                        'status' => 'aktif',
                    ],
                );
            }

            $author = $teachers['DEMO-GURU-001']->user;
            foreach ([
                ['slug' => 'pengumuman-contoh-lokal', 'title' => 'Pengumuman Contoh (Data Lokal)'],
                ['slug' => 'informasi-kegiatan-contoh-lokal', 'title' => 'Informasi Kegiatan Contoh (Data Lokal)'],
            ] as $announcement) {
                Announcement::query()->updateOrCreate(
                    ['slug' => $announcement['slug']],
                    [
                        'author_id' => $author->id,
                        'title' => $announcement['title'],
                        'body' => '[Isi pengumuman data contoh untuk uji lokal.]',
                        'category' => 'Contoh',
                        'audience_role' => null,
                        'is_published' => true,
                        'published_at' => now(),
                    ],
                );
            }

            BillType::query()->updateOrCreate(
                ['academic_year_id' => $year->id, 'name' => 'Tagihan Contoh Lokal'],
                [
                    'amount' => 150000,
                    'school_level' => null,
                    'grade' => null,
                    'due_date' => '2027-06-30',
                    'is_active' => true,
                ],
            );
        });

        echo $generatedPassword
            ? "Kata sandi akun data demo (tampilkan sekali): {$password}\n"
            : "Kata sandi akun data demo memakai nilai DEMO_PASSWORD.\n";
    }

    private function demoUser(string $name, string $email, string $role, string $password): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password],
        );
        $user->forceFill([
            'role' => $role,
            'is_active' => true,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
