<?php

namespace App\Services;

use App\Models\AttendanceChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceChallengeService
{
    /**
     * @return array{token: string, expires_at: string}
     */
    public function issue(User $user, string $type, string $sessionId): array
    {
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(2);

        AttendanceChallenge::query()->create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'attendance_type' => $type,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'expires_at' => $expiresAt->toIso8601String()];
    }

    public function consume(User $user, string $type, string $sessionId, string $token): void
    {
        $valid = DB::transaction(function () use ($user, $type, $sessionId, $token): bool {
            $challenge = AttendanceChallenge::query()
                ->where('token_hash', hash('sha256', $token))
                ->where('user_id', $user->id)
                ->where('attendance_type', $type)
                ->where('session_id', $sessionId)
                ->lockForUpdate()
                ->first();

            if ($challenge === null) {
                return false;
            }

            $isValid = $challenge->expires_at->isFuture();
            $challenge->delete();

            return $isValid;
        });

        if (! $valid) {
            throw ValidationException::withMessages([
                'challenge' => 'Tantangan absensi tidak valid, sudah digunakan, atau telah kedaluwarsa. Muat ulang halaman dan coba lagi.',
            ]);
        }
    }
}
