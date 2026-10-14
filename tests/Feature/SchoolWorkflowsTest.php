<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ExcuseRequest;
use App\Models\GradeComponent;
use App\Models\GradeScore;
use App\Models\GradingBand;
use App\Models\ParentChild;
use App\Models\ReportCard;
use App\Models\SchoolClass;
use App\Models\SchoolImportBatch;
use App\Models\StudentAttendance;
use App\Models\StudentClassHistory;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SchoolWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_only_admin_can_open_the_excel_import_area(): void
    {
        $student = User::factory()->create();
        $student->forceFill(['role' => User::ROLE_SISWA])->save();

        $this->actingAs($student)->get(route('admin.imports.index'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $this->actingAs($admin)->get(route('admin.imports.index'))->assertOk();
    }

    public function test_import_rejects_unsupported_file_types(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($admin)
            ->from(route('admin.imports.index'))
            ->post(route('admin.imports.preview.store'), [
                'type' => 'students',
                'file' => UploadedFile::fake()->create('murid.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_import_preview_shows_row_errors_without_exposing_personal_data(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $batch = SchoolImportBatch::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $admin->id,
            'type' => 'students',
            'payload' => Crypt::encryptString(json_encode([[
                'number' => 2,
                'valid' => false,
                'errors' => ['Email wajib valid.', 'Formula spreadsheet tidak diperbolehkan.'],
                'data' => ['name' => 'Nama Privat', 'email' => '=formula'],
            ]], JSON_THROW_ON_ERROR)),
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->actingAs($admin)->get(route('admin.imports.preview', $batch))
            ->assertOk()
            ->assertSee('Baris 2')
            ->assertSee('Email wajib valid.')
            ->assertDontSee('Nama Privat')
            ->assertDontSee('=formula')
            ->assertDontSee('Konfirmasi dan simpan');
    }

    public function test_import_confirmation_creates_accounts_with_hashed_temporary_passwords_and_one_time_export(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $year = $this->academicYear('2026/2027', true);
        $schoolClass = $this->schoolClass($year, '7A');
        $batch = SchoolImportBatch::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $admin->id,
            'type' => 'students',
            'payload' => Crypt::encryptString(json_encode([[
                'number' => 2,
                'valid' => true,
                'errors' => [],
                'data' => [
                    'name' => 'Murid Baru',
                    'email' => 'murid.baru@example.test',
                    'nis' => 'NIS-001',
                    'nisn' => 'NISN-001',
                    'school_class_id' => $schoolClass->id,
                ],
            ]], JSON_THROW_ON_ERROR)),
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.imports.confirm', $batch));
        $response->assertRedirect(route('admin.imports.index'));
        $this->assertDatabaseHas('student_profiles', [
            'nis' => 'NIS-001',
            'school_class_id' => $schoolClass->id,
        ]);
        $student = User::query()->where('email', 'murid.baru@example.test')->firstOrFail();
        $this->assertTrue($student->must_change_password);
        $this->assertNotSame('password', $student->password);
        $this->assertDatabaseHas('student_class_histories', [
            'student_profile_id' => $student->studentProfile->id,
            'academic_year_id' => $year->id,
        ]);

        $download = $this->get(route('admin.imports.passwords', $batch));
        $download->assertDownload('akun-sementara-students.csv');
        $this->assertDatabaseHas('school_import_batches', [
            'id' => $batch->id,
            'password_export' => null,
        ]);
        $this->get(route('admin.imports.passwords', $batch))->assertNotFound();
    }

    public function test_excuse_approval_creates_a_status_that_absent_marking_does_not_overwrite(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $student = User::factory()->create();
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        $year = $this->academicYear('2026/2027', true);
        $schoolClass = $this->schoolClass($year, '8A');
        $profile = StudentProfile::query()->create([
            'user_id' => $student->id,
            'student_number' => 'NIS-002',
            'nis' => 'NIS-002',
            'school_class_id' => $schoolClass->id,
            'status' => 'aktif',
        ]);
        $excuse = ExcuseRequest::query()->create([
            'student_profile_id' => $profile->id,
            'requested_by' => $student->id,
            'attendance_date' => today()->toDateString(),
            'type' => StudentAttendance::STATUS_SICK,
            'reason' => 'Surat keterangan sakit.',
        ]);

        $this->actingAs($admin)->put(route('admin.excuses.review', $excuse), [
            'decision' => ExcuseRequest::STATUS_APPROVED,
            'review_note' => 'Disetujui.',
        ])->assertRedirect();

        $this->artisan('attendance:mark-absent')->assertSuccessful();
        $this->assertTrue(StudentAttendance::query()
            ->where('student_profile_id', $profile->id)
            ->whereDate('attendance_date', today()->toDateString())
            ->where('status', StudentAttendance::STATUS_SICK)
            ->exists());
    }

    public function test_teacher_cannot_review_an_excuse_for_another_teachers_class(): void
    {
        $teacherUser = User::factory()->create();
        $teacherUser->forceFill(['role' => User::ROLE_GURU])->save();
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'employee_number' => 'T-001',
        ]);
        $student = User::factory()->create();
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        $year = $this->academicYear('2026/2027', true);
        $schoolClass = $this->schoolClass($year, '9A');
        $profile = StudentProfile::query()->create([
            'user_id' => $student->id,
            'student_number' => 'NIS-003',
            'nis' => 'NIS-003',
            'school_class_id' => $schoolClass->id,
            'status' => 'aktif',
        ]);
        $excuse = ExcuseRequest::query()->create([
            'student_profile_id' => $profile->id,
            'requested_by' => $student->id,
            'attendance_date' => today()->toDateString(),
            'type' => 'izin',
            'reason' => 'Keperluan keluarga.',
        ]);
        $this->assertDatabaseMissing('class_teacher', ['teacher_profile_id' => $teacher->id, 'school_class_id' => $schoolClass->id]);

        $this->actingAs($teacherUser)->put(route('guru.excuses.review', $excuse), [
            'decision' => ExcuseRequest::STATUS_APPROVED,
            'review_note' => 'Setuju.',
        ])->assertForbidden();
    }

    public function test_parent_can_submit_an_excuse_only_for_a_linked_child(): void
    {
        $parent = User::factory()->create();
        $parent->forceFill(['role' => User::ROLE_ORANG_TUA])->save();
        $year = $this->academicYear('2026/2027', true);
        $schoolClass = $this->schoolClass($year, '7B');
        $linkedChild = User::factory()->create();
        $linkedChild->forceFill(['role' => User::ROLE_SISWA])->save();
        $linkedProfile = StudentProfile::query()->create([
            'user_id' => $linkedChild->id,
            'student_number' => 'NIS-008',
            'nis' => 'NIS-008',
            'school_class_id' => $schoolClass->id,
            'status' => 'aktif',
        ]);
        ParentChild::query()->create(['parent_id' => $parent->id, 'student_id' => $linkedChild->id]);
        $otherChild = User::factory()->create();
        $otherChild->forceFill(['role' => User::ROLE_SISWA])->save();
        StudentProfile::query()->create([
            'user_id' => $otherChild->id,
            'student_number' => 'NIS-009',
            'nis' => 'NIS-009',
            'school_class_id' => $schoolClass->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($parent)->post(route('orang_tua.children.excuses.store', $linkedChild), [
            'attendance_date' => today()->toDateString(),
            'type' => 'izin',
            'reason' => 'Keperluan keluarga.',
        ])->assertRedirect(route('orang_tua.excuses.index'));
        $this->assertDatabaseHas('excuse_requests', [
            'student_profile_id' => $linkedProfile->id,
            'requested_by' => $parent->id,
        ]);

        $this->post(route('orang_tua.children.excuses.store', $otherChild), [
            'attendance_date' => today()->toDateString(),
            'type' => 'sakit',
            'reason' => 'Tidak terkait.',
        ])->assertForbidden();
    }

    public function test_year_advancement_keeps_old_class_history_and_records_the_new_class_transactionally(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $source = $this->academicYear('2026/2027', true);
        $target = $this->academicYear('2027/2028');
        $oldClass = $this->schoolClass($source, '8A');
        $newClass = $this->schoolClass($target, '9A');
        $student = User::factory()->create();
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        $profile = StudentProfile::query()->create([
            'user_id' => $student->id,
            'student_number' => 'NIS-004',
            'nis' => 'NIS-004',
            'school_class_id' => $oldClass->id,
            'status' => 'aktif',
        ]);
        StudentClassHistory::query()->create([
            'student_profile_id' => $profile->id,
            'academic_year_id' => $source->id,
            'school_class_id' => $oldClass->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)->post(route('admin.school-years.advance.execute'), [
            'academic_year_id' => $target->id,
            'mapping' => [$oldClass->id => (string) $newClass->id],
        ])->assertRedirect(route('admin.school-years.advance.index'));

        $this->assertSame($newClass->id, $profile->fresh()->school_class_id);
        $this->assertDatabaseHas('student_class_histories', [
            'student_profile_id' => $profile->id,
            'academic_year_id' => $source->id,
            'school_class_id' => $oldClass->id,
        ]);
        $this->assertDatabaseHas('student_class_histories', [
            'student_profile_id' => $profile->id,
            'academic_year_id' => $target->id,
            'school_class_id' => $newClass->id,
        ]);
        $this->assertTrue($target->fresh()->is_active);
        $this->assertFalse($source->fresh()->is_active);
    }

    public function test_published_report_is_locked_without_a_reason_for_changes(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $teacherUser = User::factory()->create();
        $teacher = TeacherProfile::query()->create(['user_id' => $teacherUser->id, 'employee_number' => 'T-002']);
        $year = $this->academicYear('2026/2027', true);
        $schoolClass = $this->schoolClass($year, '10A');
        $subject = Subject::query()->create(['code' => 'MAT', 'name' => 'Matematika']);
        Timetable::query()->create([
            'academic_year_id' => $year->id,
            'school_class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'teacher_profile_id' => $teacher->id,
            'day_of_week' => 1,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);
        $student = User::factory()->create();
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        $profile = StudentProfile::query()->create([
            'user_id' => $student->id,
            'student_number' => 'NIS-005',
            'nis' => 'NIS-005',
            'school_class_id' => $schoolClass->id,
            'status' => 'aktif',
        ]);
        $component = GradeComponent::query()->create([
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'name' => 'Tugas',
            'weight' => 100,
        ]);
        GradeScore::query()->create([
            'grade_component_id' => $component->id,
            'student_profile_id' => $profile->id,
            'score' => 90,
            'entered_by' => $admin->id,
        ]);
        GradingBand::query()->create([
            'academic_year_id' => $year->id,
            'minimum_score' => 0,
            'predicate' => 'A',
            'description' => 'Baik.',
        ]);
        $this->actingAs($admin)->post(route('admin.reports.publish', $schoolClass), [
            'action' => 'terbit',
            'notes' => [$profile->id => 'Catatan awal.'],
        ])->assertRedirect();

        $report = ReportCard::query()->where('student_profile_id', $profile->id)->firstOrFail();
        $this->assertSame('terbit', $report->status);
        $this->assertSame($schoolClass->id, $report->school_class_id);
        $this->assertCount(1, $report->items);

        $this->from(route('admin.reports.homeroom', $schoolClass))
            ->post(route('admin.reports.publish', $schoolClass), [
                'action' => 'terbit',
                'notes' => [$profile->id => 'Catatan berubah.'],
            ])
            ->assertSessionHasErrors('reason');
        $this->assertSame('Catatan awal.', $report->fresh()->homeroom_note);
    }

    public function test_report_publication_rolls_back_all_student_reports_if_one_student_has_no_grade(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $teacherUser = User::factory()->create();
        $teacher = TeacherProfile::query()->create(['user_id' => $teacherUser->id, 'employee_number' => 'T-003']);
        $year = $this->academicYear('2026/2027', true);
        $schoolClass = $this->schoolClass($year, '11A');
        $subject = Subject::query()->create(['code' => 'BIO', 'name' => 'Biologi']);
        Timetable::query()->create([
            'academic_year_id' => $year->id,
            'school_class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'teacher_profile_id' => $teacher->id,
            'day_of_week' => 2,
            'starts_at' => '09:00:00',
            'ends_at' => '10:00:00',
        ]);
        $profiles = collect(['NIS-006', 'NIS-007'])->map(function (string $nis) use ($schoolClass): StudentProfile {
            $user = User::factory()->create();
            $user->forceFill(['role' => User::ROLE_SISWA])->save();

            return StudentProfile::query()->create([
                'user_id' => $user->id,
                'student_number' => $nis,
                'nis' => $nis,
                'school_class_id' => $schoolClass->id,
                'status' => 'aktif',
            ]);
        });
        $component = GradeComponent::query()->create([
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'name' => 'UAS',
            'weight' => 100,
        ]);
        GradeScore::query()->create([
            'grade_component_id' => $component->id,
            'student_profile_id' => $profiles->first()->id,
            'score' => 85,
            'entered_by' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('admin.reports.publish', $schoolClass), [
            'action' => 'terbit',
            'notes' => [$profiles->first()->id => 'Catatan.'],
        ])->assertSessionHasErrors('report');

        $this->assertDatabaseCount('report_cards', 0);
        $this->assertDatabaseCount('report_card_items', 0);
        $this->assertDatabaseMissing('audit_logs', ['record_type' => ReportCard::class]);
    }

    private function academicYear(string $name, bool $active = false): AcademicYear
    {
        return AcademicYear::query()->create([
            'name' => $name,
            'starts_on' => $name === '2026/2027' ? '2026-07-01' : '2027-07-01',
            'ends_on' => $name === '2026/2027' ? '2027-06-30' : '2028-06-30',
            'is_active' => $active,
        ]);
    }

    private function schoolClass(AcademicYear $year, string $name): SchoolClass
    {
        return SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => $name,
            'grade' => preg_replace('/\D/', '', $name),
            'school_level' => str_starts_with($name, '1') ? 'SMA' : 'SMP',
        ]);
    }
}
