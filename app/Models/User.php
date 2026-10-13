<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_GURU = 'guru';

    public const ROLE_SISWA = 'siswa';

    public const ROLE_ORANG_TUA = 'orang_tua';

    public const ROLE_PENDAFTAR = 'pendaftar';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_GURU,
        self::ROLE_SISWA,
        self::ROLE_ORANG_TUA,
        self::ROLE_PENDAFTAR,
    ];

    protected $attributes = [
        'role' => self::ROLE_SISWA,
        'is_active' => true,
        'must_change_password' => false,
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
            self::ROLE_PENDAFTAR => 'pendaftar.dashboard',
            default => throw new \LogicException('Peran pengguna tidak valid.'),
        };
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'parent_child', 'parent_id', 'student_id')->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'parent_child', 'student_id', 'parent_id')->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function ppdbApplication(): HasOne
    {
        return $this->hasOne(PpdbApplication::class);
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
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'notification_email_enabled' => 'boolean',
        ];
    }
}
