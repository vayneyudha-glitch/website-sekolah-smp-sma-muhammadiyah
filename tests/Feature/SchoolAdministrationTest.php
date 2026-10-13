<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchoolAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_only_an_admin_can_open_school_data_management(): void
    {
        $response = $this->get(route('admin.data.index', ['resource' => 'academic-years']));
        $response->assertRedirect(route('login'));

        $student = User::factory()->create(['name' => 'Murid Contoh']);
        $student->forceFill(['role' => User::ROLE_SISWA])->save();

        $this->actingAs($student)
            ->get(route('admin.data.index', ['resource' => 'academic-years']))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_student_account_and_audit_the_change(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $class = SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII-A',
            'grade' => 'VII',
            'school_level' => 'SMP',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.data.store', ['resource' => 'students']), [
            'name' => 'Murid Contoh',
            'email' => 'murid@example.test',
            'nis' => '2026001',
            'nisn' => '0012345678',
            'school_class_id' => $class->id,
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('admin.data.index', ['resource' => 'students']));
        $response->assertSessionHas('temporary_password');
        $user = User::query()->where('email', 'murid@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_SISWA, $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check(session('temporary_password'), $user->password));
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $user->id,
            'student_number' => '2026001',
            'nis' => '2026001',
            'nisn' => '0012345678',
            'school_class_id' => $class->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'record_type' => 'students',
        ]);
    }

    public function test_admin_can_create_a_teacher_account_for_assigned_classes(): void
    {
        [$class] = $this->makeTwoClassTimetables();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $response = $this->actingAs($admin)->post(route('admin.data.store', ['resource' => 'teachers']), [
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.test',
            'employee_number' => 'G-NEW',
            'bio' => 'Guru mata pelajaran.',
            'class_ids' => [$class->id],
            'subject_ids' => [],
        ]);

        $response->assertRedirect(route('admin.data.index', ['resource' => 'teachers']));
        $response->assertSessionHas('temporary_password');
        $user = User::query()->where('email', 'guru.baru@example.test')->firstOrFail();
        $teacher = TeacherProfile::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(User::ROLE_GURU, $user->role);
        $this->assertSame([$class->id], $teacher->classes()->pluck('school_classes.id')->all());
        $this->assertTrue(Hash::check(session('temporary_password'), $user->password));
    }

    public function test_admin_can_create_a_parent_account_and_link_that_parent_to_a_student(): void
    {
        [$class] = $this->makeTwoClassTimetables();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $student = User::factory()->create(['name' => 'Anak Terdaftar']);
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        StudentProfile::query()->create([
            'user_id' => $student->id,
            'student_number' => 'S-PARENT-01',
            'nis' => 'S-PARENT-01',
            'school_class_id' => $class->id,
        ]);

        $this->actingAs($admin)->post(route('admin.data.store', ['resource' => 'parents']), [
            'name' => 'Orang Tua Contoh',
            'email' => 'orangtua@example.test',
        ])->assertRedirect(route('admin.data.index', ['resource' => 'parents']))
            ->assertSessionHas('temporary_password');

        $parent = User::query()->where('email', 'orangtua@example.test')->firstOrFail();
        $parent->forceFill(['must_change_password' => false])->save();
        $this->post(route('admin.data.store', ['resource' => 'parent-links']), [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
        ])->assertRedirect(route('admin.data.index', ['resource' => 'parent-links']));

        $this->assertDatabaseHas('parent_child', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
        ]);
        $this->actingAs($parent)->get(route('orang_tua.children.index'))
            ->assertOk()->assertSee('Anak Terdaftar');
    }

    public function test_admin_can_create_a_valid_schedule_but_not_assign_a_teacher_to_an_unassigned_class(): void
    {
        [$classA, $classB, $subjectA, , $teacherA] = $this->makeTwoClassTimetables();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($admin)->post(route('admin.data.store', ['resource' => 'timetables']), [
            'academic_year_id' => $classA->academic_year_id,
            'school_class_id' => $classA->id,
            'subject_id' => $subjectA->id,
            'teacher_profile_id' => $teacherA->id,
            'day_of_week' => 1,
            'starts_at' => '10:00',
            'ends_at' => '11:00',
            'location' => 'Ruang 2',
        ])->assertRedirect(route('admin.data.index', ['resource' => 'timetables']));

        $this->assertDatabaseHas('timetables', [
            'school_class_id' => $classA->id,
            'teacher_profile_id' => $teacherA->id,
            'starts_at' => '10:00',
        ]);

        $this->from(route('admin.data.create', ['resource' => 'timetables']))
            ->post(route('admin.data.store', ['resource' => 'timetables']), [
                'academic_year_id' => $classB->academic_year_id,
                'school_class_id' => $classB->id,
                'subject_id' => $subjectA->id,
                'teacher_profile_id' => $teacherA->id,
                'day_of_week' => 1,
                'starts_at' => '11:00',
                'ends_at' => '12:00',
            ])->assertSessionHasErrors('teacher_profile_id');
    }

    public function test_students_parents_and_teachers_only_see_authorized_class_timetables(): void
    {
        [$classA, $classB, $subjectA, $subjectB, $teacherA, $teacherB] = $this->makeTwoClassTimetables();

        $student = User::factory()->create(['name' => 'Murid Contoh']);
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        StudentProfile::query()->create([
            'user_id' => $student->id,
            'student_number' => 'S-001',
            'nis' => 'S-001',
            'school_class_id' => $classA->id,
        ]);
        $otherStudent = User::factory()->create(['name' => 'Murid Kelas Lain']);
        $otherStudent->forceFill(['role' => User::ROLE_SISWA])->save();
        StudentProfile::query()->create([
            'user_id' => $otherStudent->id,
            'student_number' => 'S-002',
            'nis' => 'S-002',
            'school_class_id' => $classB->id,
        ]);

        $parent = User::factory()->create();
        $parent->forceFill(['role' => User::ROLE_ORANG_TUA])->save();
        $parent->children()->attach($student->id);

        $this->actingAs($student)->get(route('siswa.timetables.index'))
            ->assertOk()->assertSee($subjectA->name)->assertDontSee($subjectB->name);

        $this->actingAs($parent)->get(route('orang_tua.timetables.index'))
            ->assertOk()->assertSee($subjectA->name)->assertDontSee($subjectB->name);

        $this->actingAs($teacherA->user)->get(route('guru.timetables.index'))
            ->assertOk()->assertSee($subjectA->name)->assertDontSee($subjectB->name);

        $this->actingAs($student)->get(route('siswa.student-record.index'))
            ->assertOk()->assertSee('S-001')->assertDontSee('S-002');
        $this->actingAs($parent)->get(route('orang_tua.children.index'))
            ->assertOk()->assertSee('Murid Contoh')->assertDontSee('Murid Kelas Lain');
        $this->actingAs($teacherA->user)->get(route('guru.classes.index'))
            ->assertOk()->assertSee('Murid Contoh')->assertDontSee('Murid Kelas Lain');

        $this->assertNotSame($teacherA->id, $teacherB->id);
    }

    public function test_public_pages_only_show_published_announcements_and_teacher_names(): void
    {
        $admin = User::factory()->create();
        $teacherUser = User::factory()->create(['name' => 'Guru Terbuka']);
        $teacherUser->forceFill(['role' => User::ROLE_GURU])->save();
        TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'employee_number' => 'G-01',
        ]);

        Announcement::query()->create([
            'author_id' => $admin->id,
            'title' => 'Berita Tayang',
            'slug' => 'berita-tayang',
            'body' => 'Isi berita untuk masyarakat.',
            'is_published' => true,
            'published_at' => now()->subMinute(),
        ]);
        Announcement::query()->create([
            'author_id' => $admin->id,
            'title' => 'Berita Draf Rahasia',
            'slug' => 'berita-draf',
            'body' => 'Belum boleh terlihat publik.',
            'is_published' => false,
        ]);

        $this->get(route('public.teachers'))->assertOk()->assertSee('Guru Terbuka');
        $this->get(route('public.announcements'))->assertOk()
            ->assertSee('Berita Tayang')->assertDontSee('Berita Draf Rahasia');
        $this->get(route('public.announcement', ['announcement' => 'berita-tayang']))
            ->assertOk()->assertSee('Isi berita untuk masyarakat.');
        $this->get(route('public.announcement', ['announcement' => 'berita-draf']))->assertNotFound();
    }

    public function test_temporary_password_must_be_changed_before_accessing_the_portal(): void
    {
        $user = User::factory()->create(['password' => 'Temporary-Pass-123!']);
        $user->forceFill(['must_change_password' => true])->save();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Temporary-Pass-123!',
        ])->assertRedirect(route('password.first-change.create'));

        $this->get(route('siswa.dashboard'))->assertRedirect(route('password.first-change.create'));
        $this->get(route('password.first-change.create'))->assertOk();
        $this->put(route('password.first-change.update'), [
            'current_password' => 'Temporary-Pass-123!',
            'password' => 'New-Longer-Pass-456!',
            'password_confirmation' => 'New-Longer-Pass-456!',
        ])->assertRedirect(route('siswa.dashboard'));

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('New-Longer-Pass-456!', $user->fresh()->password));
    }

    public function test_inactive_accounts_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => 'Inactive-Pass-123!',
        ]);
        $user->forceFill(['is_active' => false])->save();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Inactive-Pass-123!',
        ])->assertSessionHasErrors('email');
    }

    public function test_role_targeted_announcements_are_hidden_from_the_public_and_shown_to_the_target_role(): void
    {
        $admin = User::factory()->create();
        Announcement::query()->create([
            'author_id' => $admin->id,
            'title' => 'Informasi Khusus Murid',
            'slug' => 'informasi-khusus-murid',
            'body' => 'Informasi untuk murid yang sedang belajar.',
            'category' => 'Akademik',
            'audience_role' => User::ROLE_SISWA,
            'is_published' => true,
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('public.announcements'))->assertDontSee('Informasi Khusus Murid');

        $student = User::factory()->create();
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        $this->actingAs($student)->get(route('siswa.dashboard'))
            ->assertOk()->assertSee('Informasi Khusus Murid');
    }

    private function makeTwoClassTimetables(): array
    {
        $year = AcademicYear::query()->create([
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => true,
        ]);
        $classA = SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'VIII-A',
            'grade' => 'VIII',
            'school_level' => 'SMP',
        ]);
        $classB = SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'VIII-B',
            'grade' => 'VIII',
            'school_level' => 'SMP',
        ]);
        $teacherUserA = User::factory()->create();
        $teacherUserA->forceFill(['role' => User::ROLE_GURU])->save();
        $teacherA = TeacherProfile::query()->create([
            'user_id' => $teacherUserA->id,
            'employee_number' => 'G-101',
        ]);
        $teacherUserB = User::factory()->create();
        $teacherUserB->forceFill(['role' => User::ROLE_GURU])->save();
        $teacherB = TeacherProfile::query()->create([
            'user_id' => $teacherUserB->id,
            'employee_number' => 'G-102',
        ]);
        $teacherA->classes()->attach($classA->id);
        $teacherB->classes()->attach($classB->id);
        $subjectA = Subject::query()->create(['code' => 'MTK', 'name' => 'Matematika A']);
        $subjectB = Subject::query()->create(['code' => 'IPA', 'name' => 'IPA B']);
        $teacherA->subjects()->attach($subjectA->id);
        $teacherB->subjects()->attach($subjectB->id);

        Timetable::query()->create([
            'academic_year_id' => $year->id,
            'school_class_id' => $classA->id,
            'subject_id' => $subjectA->id,
            'teacher_profile_id' => $teacherA->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
        ]);
        Timetable::query()->create([
            'academic_year_id' => $year->id,
            'school_class_id' => $classB->id,
            'subject_id' => $subjectB->id,
            'teacher_profile_id' => $teacherB->id,
            'day_of_week' => 1,
            'starts_at' => '09:00',
            'ends_at' => '10:00',
        ]);

        return [$classA, $classB, $subjectA, $subjectB, $teacherA, $teacherB];
    }
}
