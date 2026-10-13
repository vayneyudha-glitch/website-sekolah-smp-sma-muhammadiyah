<?php

namespace App\Services;

use App\Models\AttendanceSetting;
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
        ?int $elapsedSeconds,
        bool $deviceUsedByAnotherAccount,
    ): array {
        $reasons = [];

        if ($accuracyMeters > 100) {
            $reasons[] = 'Akurasi GPS lebih dari 100 meter';
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
            $reasons[] = 'Perangkat terdeteksi digunakan oleh akun guru lain';
        }

        return $reasons;
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
