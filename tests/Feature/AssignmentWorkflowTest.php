<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssignmentSubmission;
use App\Models\ClassAssignment;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_teacher_can_create_a_class_assignment_with_a_private_attachment(): void
    {
        [$teacher, $schoolClass, $subject] = $this->teacherWithTimetable();

        $response = $this->actingAs($teacher)->post(route('guru.assignments.store'), [
            'timetable_id' => Timetable::query()->value('id'),
            'title' => 'Latihan Bab 1',
            'description' => 'Kerjakan soal nomor 1 sampai 5.',
            'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'attachment' => UploadedFile::fake()->create('materi.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $assignment = ClassAssignment::query()->firstOrFail();
        $this->assertSame($teacher->id, $assignment->teacher_id);
        $this->assertSame($schoolClass->id, $assignment->school_class_id);
        $this->assertSame($subject->id, $assignment->subject_id);
        Storage::disk('local')->assertExists($assignment->attachment_path);
    }

    public function test_submission_status_uses_server_time_and_file_download_is_limited_to_authorized_users(): void
    {
        [$teacher, $schoolClass] = $this->teacherWithTimetable();
        $student = $this->studentIn($schoolClass, 'Murid Pemilik');
        $otherStudent = $this->studentIn($schoolClass, 'Murid Lain');
        $assignment = $this->assignment($teacher, $schoolClass);
        $assignment->update(['due_at' => now()->subMinute()]);

        $this->actingAs($student)->post(route('siswa.assignments.submit', $assignment), [
            'attachment' => UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $submission = AssignmentSubmission::query()->firstOrFail();
        $this->assertTrue($submission->is_late);
        Storage::disk('local')->assertExists($submission->file_path);
        $this->actingAs($student)->get(route('siswa.assignments.index'))->assertSee('Terlambat');

        $this->actingAs($student)
            ->get(route('assignments.submission-attachment', $submission))
            ->assertOk();
        $this->actingAs($otherStudent)
            ->get(route('assignments.submission-attachment', $submission))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->get(route('assignments.submission-attachment', $submission))
            ->assertOk();

        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $this->actingAs($admin)
            ->get(route('assignments.submission-attachment', $submission))
            ->assertOk();
    }

    public function test_linked_parent_can_only_see_the_childs_graded_results(): void
    {
        [$teacher, $schoolClass] = $this->teacherWithTimetable();
        $student = $this->studentIn($schoolClass, 'Murid Saya');
        $assignment = $this->assignment($teacher, $schoolClass);
        AssignmentSubmission::query()->create([
            'assignment_id' => $assignment->id,
            'student_profile_id' => $student->studentProfile->id,
            'body' => 'Jawaban',
            'submitted_at' => now(),
            'is_late' => false,
            'score' => 92,
            'feedback' => 'Bagus.',
            'graded_by' => $teacher->id,
            'graded_at' => now(),
        ]);
        $parent = User::factory()->create();
        $parent->forceFill(['role' => User::ROLE_ORANG_TUA])->save();
        $parent->children()->attach($student->id);
        $unrelatedParent = User::factory()->create();
        $unrelatedParent->forceFill(['role' => User::ROLE_ORANG_TUA])->save();

        $this->actingAs($parent)
            ->get(route('orang_tua.children.assignments', $student))
            ->assertOk()
            ->assertSee('92')
            ->assertSee('Bagus.');
        $this->actingAs($unrelatedParent)
            ->get(route('orang_tua.children.assignments', $student))
            ->assertForbidden();
    }

    /**
     * @return array{User, SchoolClass, Subject}
     */
    private function teacherWithTimetable(): array
    {
        $teacher = User::factory()->create();
        $teacher->forceFill(['role' => User::ROLE_GURU])->save();
        $profile = TeacherProfile::query()->create([
            'user_id' => $teacher->id,
            'employee_number' => 'T-'.str_pad((string) $teacher->id, 5, '0', STR_PAD_LEFT),
        ]);
        $academicYear = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $schoolClass = SchoolClass::query()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'VII A',
            'grade' => '7',
            'school_level' => 'SMP',
        ]);
        $subject = Subject::query()->create(['code' => 'MTK', 'name' => 'Matematika']);
        Timetable::query()->create([
            'academic_year_id' => $academicYear->id,
            'school_class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'teacher_profile_id' => $profile->id,
            'day_of_week' => 1,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);

        return [$teacher, $schoolClass, $subject];
    }

    private function studentIn(SchoolClass $schoolClass, string $name): User
    {
        $student = User::factory()->create(['name' => $name]);
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        StudentProfile::query()->create([
            'user_id' => $student->id,
            'student_number' => 'S-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
            'nis' => 'N-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
            'school_class_id' => $schoolClass->id,
            'status' => 'aktif',
        ]);

        return $student->load('studentProfile');
    }

    private function assignment(User $teacher, SchoolClass $schoolClass): ClassAssignment
    {
        return ClassAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'school_class_id' => $schoolClass->id,
            'subject_id' => Subject::query()->value('id'),
            'title' => 'Tugas Pengujian',
            'due_at' => now()->setTime(17, 0),
        ]);
    }
}
