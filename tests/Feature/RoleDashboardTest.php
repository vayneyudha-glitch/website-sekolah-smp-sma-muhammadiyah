<?php

namespace Tests\Feature;

use App\Models\User;
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

            $response = $this->actingAs($user)->get(route($role.'.dashboard'));

            $response->assertOk();
            $response->assertSee('Dashboard '.match ($role) {
                User::ROLE_ADMIN => 'Admin',
                User::ROLE_GURU => 'Guru',
                User::ROLE_SISWA => 'Siswa',
                User::ROLE_ORANG_TUA => 'Orang Tua',
            });
        }
    }
}
