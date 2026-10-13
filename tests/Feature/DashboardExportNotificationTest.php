<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Notifications\SchoolEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DashboardExportNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_non_admin_cannot_download_admin_exports(): void
    {
        $student = User::factory()->create();
        $student->forceFill(['role' => User::ROLE_SISWA])->save();

        $response = $this->actingAs($student)->get(route('admin.exports.download', [
            'dataset' => 'students',
            'format' => 'xlsx',
        ]));

        $response->assertForbidden();
    }

    public function test_parent_dashboard_only_shows_linked_children(): void
    {
        $parent = User::factory()->create(['name' => 'Wali Terhubung']);
        $parent->forceFill(['role' => User::ROLE_ORANG_TUA])->save();
        $linkedChild = User::factory()->create(['name' => 'Anak Terhubung']);
        $unlinkedChild = User::factory()->create(['name' => 'Anak Orang Lain']);
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $schoolClass = SchoolClass::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII-A',
            'grade' => 'VII',
            'school_level' => 'SMP',
        ]);
        foreach ([$linkedChild, $unlinkedChild] as $index => $child) {
            StudentProfile::query()->create([
                'user_id' => $child->id,
                'student_number' => '2026-'.($index + 1),
                'school_class_id' => $schoolClass->id,
            ]);
        }
        $parent->children()->attach($linkedChild->id);

        $response = $this->actingAs($parent)->get(route('orang_tua.dashboard'));

        $response->assertOk()
            ->assertSee('Anak Terhubung')
            ->assertDontSee('Anak Orang Lain');
    }

    public function test_notification_inbox_only_shows_the_signed_in_users_messages(): void
    {
        Mail::fake();
        $recipient = User::factory()->create();
        $otherUser = User::factory()->create();

        Notification::sendNow($recipient, new SchoolEventNotification(
            'announcement.created',
            'Pesan khusus penerima',
            'Konten notifikasi yang ditujukan ke penerima ini.',
        ));
        Notification::sendNow($otherUser, new SchoolEventNotification(
            'announcement.created',
            'Pesan milik pengguna lain',
            'Konten ini tidak boleh tampil pada kotak masuk pengguna pertama.',
        ));

        $response = $this->actingAs($recipient)->get(route('notifications.index'));

        $response->assertOk()
            ->assertSee('Pesan khusus penerima')
            ->assertDontSee('Pesan milik pengguna lain');
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        Mail::fake();
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        Notification::sendNow($owner, new SchoolEventNotification(
            'announcement.created',
            'Notifikasi pribadi',
            'Konten pribadi.',
        ));
        $notificationId = $owner->notifications()->firstOrFail()->id;

        $response = $this->actingAs($otherUser)->patch(route('notifications.read', $notificationId));

        $response->assertNotFound();
    }
}
