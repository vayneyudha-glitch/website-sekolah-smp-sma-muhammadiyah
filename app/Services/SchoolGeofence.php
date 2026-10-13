<?php

namespace App\Services;

use App\Models\AttendanceSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolGeofence
{
    public function distanceInMeters(float $latitude, float $longitude, AttendanceSetting $settings): float
    {
        if (! $settings->hasSchoolLocation()) {
            throw ValidationException::withMessages([
                'location' => 'Lokasi sekolah belum diatur. Hubungi administrator sekolah.',
            ]);
        }

        $earthRadiusMeters = 6371000;
        $latitudeDelta = deg2rad($latitude - $settings->latitude);
        $longitudeDelta = deg2rad($longitude - $settings->longitude);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($settings->latitude))
            * cos(deg2rad($latitude))
            * sin($longitudeDelta / 2) ** 2;
        $a = min(1.0, max(0.0, $a));

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @return list<string>
     */
    public function suspiciousReasons(
        float $latitude,
        float $longitude,
        float $accuracyMeters,
        ?array $previousPosition,
        ?float $elapsedSeconds,
        bool $deviceUsedByAnotherAccount,
        int $maxAccuracyMeters = 100,
    ): array {
        $reasons = [];

        if ($accuracyMeters === 0.0) {
            $reasons[] = 'Akurasi GPS dilaporkan tepat 0 meter';
        } elseif ($accuracyMeters > $maxAccuracyMeters) {
            $reasons[] = 'Akurasi GPS melebihi ambang '.$maxAccuracyMeters.' meter';
        }

        if ($previousPosition !== null && $elapsedSeconds !== null && $elapsedSeconds >= 0) {
            $distance = $this->distance(
                $latitude,
                $longitude,
                $previousPosition['latitude'],
                $previousPosition['longitude'],
            );
            $maximumPlausibleDistance = (300 / 3.6) * $elapsedSeconds + 100;

            if ($distance > $maximumPlausibleDistance) {
                $reasons[] = 'Lompatan lokasi melebihi kecepatan perjalanan yang wajar';
            }
        }

        if ($deviceUsedByAnotherAccount) {
            $reasons[] = 'Perangkat terdeteksi digunakan oleh akun lain';
        }

        return $reasons;
    }

    public function locationUsedByAnotherAccount(float $latitude, float $longitude, int $userId): bool
    {
        $latitudeRange = [$latitude - 0.0000005, $latitude + 0.0000005];
        $longitudeRange = [$longitude - 0.0000005, $longitude + 0.0000005];

        $studentUserIds = DB::table('student_attendances')
            ->join('student_profiles', 'student_profiles.id', '=', 'student_attendances.student_profile_id')
            ->where('student_profiles.user_id', '!=', $userId)
            ->where('student_attendances.attendance_date', '>=', now()->subDays(30)->toDateString())
            ->whereBetween('student_attendances.latitude', $latitudeRange)
            ->whereBetween('student_attendances.longitude', $longitudeRange)
            ->distinct()
            ->pluck('student_profiles.user_id')
            ->all();
        $teacherUserIds = DB::table('teacher_attendances')
            ->join('teacher_profiles', 'teacher_profiles.id', '=', 'teacher_attendances.teacher_profile_id')
            ->where('teacher_profiles.user_id', '!=', $userId)
            ->where('teacher_attendances.attendance_date', '>=', now()->subDays(30)->toDateString())
            ->where(function ($query) use ($latitudeRange, $longitudeRange): void {
                $query->where(function ($checkIn) use ($latitudeRange, $longitudeRange): void {
                    $checkIn->whereBetween('teacher_attendances.check_in_latitude', $latitudeRange)
                        ->whereBetween('teacher_attendances.check_in_longitude', $longitudeRange);
                })->orWhere(function ($checkOut) use ($latitudeRange, $longitudeRange): void {
                    $checkOut->whereBetween('teacher_attendances.check_out_latitude', $latitudeRange)
                        ->whereBetween('teacher_attendances.check_out_longitude', $longitudeRange);
                });
            })
            ->distinct()
            ->pluck('teacher_profiles.user_id')
            ->all();

        return count(array_unique([...$studentUserIds, ...$teacherUserIds])) >= 2;
    }

    public function deviceUsedByAnotherAccount(string $deviceHash, int $userId): bool
    {
        return DB::table('student_attendances')
            ->join('student_profiles', 'student_profiles.id', '=', 'student_attendances.student_profile_id')
            ->where('student_profiles.user_id', '!=', $userId)
            ->where('student_attendances.device_hash', $deviceHash)
            ->exists()
            || DB::table('teacher_attendances')
                ->join('teacher_profiles', 'teacher_profiles.id', '=', 'teacher_attendances.teacher_profile_id')
                ->where('teacher_profiles.user_id', '!=', $userId)
                ->where(function ($query) use ($deviceHash): void {
                    $query->where('check_in_device_hash', $deviceHash)
                        ->orWhere('check_out_device_hash', $deviceHash);
                })
                ->exists();
    }

    private function distance(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($longitudeDelta / 2) ** 2;
        $a = min(1.0, max(0.0, $a));

        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
