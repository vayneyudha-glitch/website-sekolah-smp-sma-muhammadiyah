<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PpdbApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_student_cannot_open_the_admin_dashboard(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.dashboard'));

        $response->assertForbidden();
        $response->assertSee('Akses Ditolak');
    }

    public function test_each_role_can_open_its_own_dashboard(): void
    {
        foreach (User::ROLES as $role) {
            $user = User::factory()->create();
            $user->forceFill(['role' => $role])->save();
            if ($role === User::ROLE_PENDAFTAR) {
                PpdbApplication::query()->create(['user_id' => $user->id, 'student_name' => $user->name]);
            }

            $response = $this->actingAs($user)->get(route($role.'.dashboard'));

            $response->assertOk();
            $expectedTitle = match ($role) {
                User::ROLE_ADMIN => 'Dashboard Admin',
                User::ROLE_GURU => 'Dashboard Guru',
                User::ROLE_SISWA => 'Dashboard Siswa',
                User::ROLE_ORANG_TUA => 'Dashboard Orang Tua',
                User::ROLE_PENDAFTAR => 'Pendaftaran PPDB',
            };
            $response->assertSee($expectedTitle);
        }
    }
}
