<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_GURU = 'guru';

    public const ROLE_SISWA = 'siswa';

    public const ROLE_ORANG_TUA = 'orang_tua';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_GURU,
        self::ROLE_SISWA,
        self::ROLE_ORANG_TUA,
    ];

    protected $attributes = [
        'role' => self::ROLE_SISWA,
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_GURU => 'guru.dashboard',
            self::ROLE_SISWA => 'siswa.dashboard',
            self::ROLE_ORANG_TUA => 'orang_tua.dashboard',
            default => throw new \LogicException('Peran pengguna tidak valid.'),
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
