<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\PpdbApplication;
use App\Models\PpdbDocument;
use App\Models\PpdbSetting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PpdbWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $today = now('Asia/Jakarta');
        AcademicYear::query()->create([
            'name' => 'Tahun Tes PPDB',
            'starts_on' => $today->copy()->startOfYear()->toDateString(),
            'ends_on' => $today->copy()->endOfYear()->toDateString(),
            'is_active' => true,
        ]);
        $settings = PpdbSetting::query()->findOrFail(1);
        $settings->forceFill([
            'is_open' => true,
            'starts_on' => $today->copy()->subDay()->toDateString(),
            'ends_on' => $today->copy()->addDay()->toDateString(),
        ])->save();
        $this->assertNull($settings->fresh()->registrationClosureReason());
    }

    public function test_public_ppdb_registration_creates_a_draft_with_the_applicant_role(): void
    {
        $this->assertTrue(PpdbSetting::query()->findOrFail(1)->is_open);
        Event::fake([Registered::class]);

        $response = $this->post(route('ppdb.register.store'), [
            'name' => 'Calon Murid',
            'email' => 'calon@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'privacy_consent' => '1',
            'role' => User::ROLE_ADMIN,
        ]);
        $user = User::query()->where('email', 'calon@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(User::ROLE_PENDAFTAR, $user->role);
        $this->assertDatabaseHas('ppdb_applications', [
            'user_id' => $user->id,
            'student_name' => 'Calon Murid',
            'status' => PpdbApplication::STATUS_DRAFT,
        ]);
        $response->assertRedirect(route('pendaftar.dashboard'));
    }

    public function test_public_ppdb_registration_requires_privacy_consent(): void
    {
        $response = $this->from(route('ppdb.register.create'))->post(route('ppdb.register.store'), [
            'name' => 'Calon Tanpa Persetujuan',
            'email' => 'tanpa-persetujuan@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertRedirect(route('ppdb.register.create'))
            ->assertSessionHasErrors('privacy_consent');
        $this->assertDatabaseMissing('users', ['email' => 'tanpa-persetujuan@example.test']);
    }

    public function test_private_ppdb_documents_require_owner_authorization_and_log_downloads(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $owner->forceFill(['role' => User::ROLE_PENDAFTAR])->save();
        $application = PpdbApplication::query()->create([
            'user_id' => $owner->id,
            'student_name' => $owner->name,
        ]);
        $path = 'ppdb/'.$application->id.'/kk.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 private');
        $document = PpdbDocument::query()->create([
            'ppdb_application_id' => $application->id,
            'category' => 'kk',
            'original_name' => 'kartu-keluarga.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
        ]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('ppdb.documents.download', $document))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('ppdb.documents.download', $document))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'record_type' => PpdbDocument::class,
            'record_id' => $document->id,
            'action' => 'downloaded',
        ]);
    }
}
