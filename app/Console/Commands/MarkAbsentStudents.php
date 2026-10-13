<?php

namespace App\Console\Commands;

use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use Illuminate\Console\Command;

class MarkAbsentStudents extends Command
{
    protected $signature = 'attendance:mark-absent';

    protected $description = 'Menandai murid aktif yang belum memiliki absensi hari ini sebagai Alpa';

    public function handle(): int
    {
        $date = now()->toDateString();

        StudentProfile::query()
            ->where('status', 'aktif')
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->whereDoesntHave('attendances', fn ($query) => $query->whereDate('attendance_date', $date))
            ->with('user')
            ->chunkById(200, function ($students) use ($date): void {
                foreach ($students as $student) {
                    StudentAttendance::query()->firstOrCreate(
                        [
                            'student_profile_id' => $student->id,
                            'attendance_date' => $date,
                        ],
                        ['status' => StudentAttendance::STATUS_ABSENT],
                    );
                }
            });

        $this->info('Absensi Alpa murid yang belum tercatat berhasil diproses.');

        return self::SUCCESS;
    }
}
