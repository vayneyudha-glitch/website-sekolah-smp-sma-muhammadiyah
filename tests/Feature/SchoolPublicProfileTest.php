<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SchoolPublicProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_school_pages_are_available_without_login(): void
    {
        foreach ([
            '/',
            '/tentang-sekolah',
            '/visi-misi',
            '/sejarah',
            '/fasilitas',
            '/galeri',
            '/kontak',
            '/jenjang/smp',
            '/jenjang/sma',
            '/ppdb',
            '/guru',
            '/berita',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_only_admin_can_open_and_update_school_profile_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teacher = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($teacher)
            ->get(route('admin.school-content.profile.edit'))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->put(route('admin.school-content.profile.update'), [])
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.school-content.profile.update'), [
                'name' => 'Nama Sekolah Contoh',
                'tagline' => '[Isi dari panel admin]',
                'address' => '[Isi dari panel admin]',
                'phone' => '[Isi dari panel admin]',
                'whatsapp' => '[Isi dari panel admin]',
                'email' => '[Isi dari panel admin]',
                'latitude' => '[Isi dari panel admin]',
                'longitude' => '[Isi dari panel admin]',
                'map_link' => '[Isi dari panel admin]',
                'service_hours' => '[Isi dari panel admin]',
                'welcome_message' => '[Isi dari panel admin]',
                'vision' => '[Isi dari panel admin]',
                'mission' => '[Isi dari panel admin]',
                'history' => '[Isi dari panel admin]',
                'smp_description' => '[Isi dari panel admin]',
                'sma_description' => '[Isi dari panel admin]',
                'social_links' => array_fill_keys(['instagram', 'facebook', 'youtube', 'tiktok'], '[Isi dari panel admin]'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('school_profile_settings', ['id' => 1, 'name' => 'Nama Sekolah Contoh']);
    }

    public function test_gallery_rejects_non_image_uploads(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.school-content.gallery.store'), [
                'title' => 'Berkas berbahaya',
                'photo' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
                'school_level' => 'SMP',
            ])
            ->assertSessionHasErrors('photo');
    }

    public function test_demo_seeder_refuses_to_run_outside_local_environment(): void
    {
        config(['app.env' => 'production']);
        $this->expectException(\LogicException::class);

        (new DemoDataSeeder())->run();
    }
}
