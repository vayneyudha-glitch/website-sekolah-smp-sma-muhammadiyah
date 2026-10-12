<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('admin.name');
        $email = config('admin.email');
        $password = config('admin.password');

        $missing = [];

        foreach ([
            'ADMIN_NAME' => $name,
            'ADMIN_EMAIL' => $email,
            'ADMIN_PASSWORD' => $password,
        ] as $key => $value) {
            if (! is_string($value) || trim($value) === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new InvalidArgumentException(
                'Konfigurasi admin belum lengkap. Isi nilai: '.implode(', ', $missing).' di environment.'
            );
        }

        if (Str::length($password) < 12) {
            throw new InvalidArgumentException('ADMIN_PASSWORD harus memiliki minimal 12 karakter.');
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password],
        );

        $user->forceFill(['role' => User::ROLE_ADMIN])->save();
    }
}
