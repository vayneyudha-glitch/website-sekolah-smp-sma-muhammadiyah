<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\TeacherAttendance;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AttendanceChallengeService;
use App\Services\SchoolGeofence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_an_attendance_challenge_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $service = app(AttendanceChallengeService::class);
        $challenge = $service->issue($user, 'student_present', 'session-id');

        $service->consume($user, 'student_present', 'session-id', $challenge['token']);

        try {
            $service->consume($user, 'student_present', 'session-id', $challenge['token']);
            $this->fail('A consumed attendance challenge must be rejected.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_an_attendance_challenge_is_bound_to_user_type_and_session(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $service = app(AttendanceChallengeService::class);
        $challenge = $service->issue($user, 'student_present', 'session-id');

        foreach ([
            [$otherUser, 'student_present', 'session-id'],
            [$user, 'teacher_check_in', 'session-id'],
            [$user, 'student_present', 'other-session'],
        ] as [$challengeUser, $type, $sessionId]) {
            try {
                $service->consume($challengeUser, $type, $sessionId, $challenge['token']);
                $this->fail('An incorrectly bound attendance challenge must be rejected.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        $service->consume($user, 'student_present', 'session-id', $challenge['token']);
    }

    public function test_an_expired_attendance_challenge_is_rejected(): void
    {
        $user = User::factory()->create();
        $service = app(AttendanceChallengeService::class);
        $challenge = $service->issue($user, 'student_present', 'session-id');
        $challengeRecord = \App\Models\AttendanceChallenge::query()->firstOrFail();

        try {
            $challengeRecord->forceFill(['expires_at' => now()->subSecond()])->save();
            $service->consume($user, 'student_present', 'session-id', $challenge['token']);
            $this->fail('An expired attendance challenge must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('attendance_challenges', ['id' => $challengeRecord->id]);
        }
    }

    public function test_low_accuracy_and_impossible_location_jumps_are_flagged(): void
    {
        $reasons = app(SchoolGeofence::class)->suspiciousReasons(
            -6.2,
            106.8,
            150,
            ['latitude' => -6.9, 'longitude' => 107.6],
            1,
            false,
            100,
        );

        $this->assertContains('Akurasi GPS melebihi ambang 100 meter', $reasons);
        $this->assertContains('Lompatan lokasi melebihi kecepatan perjalanan yang wajar', $reasons);
    }

    public function test_zero_accuracy_is_flagged(): void
    {
        $reasons = app(SchoolGeofence::class)->suspiciousReasons(
            -6.2,
            106.8,
            0,
            null,
            null,
            false,
            100,
        );

        $this->assertContains('Akurasi GPS dilaporkan tepat 0 meter', $reasons);
    }

    public function test_challenge_requests_are_rate_limited(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->postJson(route('attendance.challenge'), ['type' => 'student_present'])
                ->assertCreated();
        }

        $this->postJson(route('attendance.challenge'), ['type' => 'student_present'])
            ->assertStatus(429);
    }

    public function test_only_admins_can_open_the_attendance_review_queue(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $this->actingAs($admin)->get(route('admin.attendance.review'))->assertOk();

        $student = User::factory()->create();
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        $this->actingAs($student)->get(route('admin.attendance.review'))->assertForbidden();
    }

    public function test_admin_attendance_review_page_displays_flagged_students_and_teachers(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $schoolClass = SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => '7A',
            'grade' => '7',
            'school_level' => 'SMP',
        ]);

        $student = User::factory()->create(['name' => 'Murid Antrean']);
        $student->forceFill(['role' => User::ROLE_SISWA])->save();
        $studentProfile = StudentProfile::query()->create([
            'user_id' => $student->id,
            'student_number' => 'NIS-REVIEW-001',
            'school_class_id' => $schoolClass->id,
        ]);
        $studentAttendance = StudentAttendance::query()->create([
            'student_profile_id' => $studentProfile->id,
            'attendance_date' => '2026-10-14',
            'status' => StudentAttendance::STATUS_PRESENT,
            'review_status' => 'perlu_ditinjau',
            'needs_review' => true,
            'suspicious_reasons' => ['Akurasi GPS melebihi ambang'],
        ]);

        $teacher = User::factory()->create(['name' => 'Guru Antrean']);
        $teacher->forceFill(['role' => User::ROLE_GURU])->save();
        $teacherProfile = TeacherProfile::query()->create([
            'user_id' => $teacher->id,
            'employee_number' => 'T-REVIEW-001',
        ]);
        TeacherAttendance::query()->create([
            'teacher_profile_id' => $teacherProfile->id,
            'attendance_date' => '2026-10-13',
            'review_status' => 'perlu_ditinjau',
            'suspicious_reasons' => ['Lokasi perlu diperiksa'],
        ]);

        $this->actingAs($admin)->get('/admin/absensi/perlu-ditinjau')
            ->assertOk()
            ->assertSee('Murid Antrean')
            ->assertSee('Guru Antrean')
            ->assertSee('Akurasi GPS melebihi ambang')
            ->assertSee('Lokasi perlu diperiksa')
            ->assertSee(route('admin.attendance.review.update', ['murid', $studentAttendance->id]), false);
    }
}
