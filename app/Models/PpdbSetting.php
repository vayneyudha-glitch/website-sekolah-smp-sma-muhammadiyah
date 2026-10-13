<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['is_open', 'wave_schedule', 'registration_flow', 'costs', 'starts_on', 'ends_on'])]
class PpdbSetting extends Model
{
    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function registrationClosureReason(): ?string
    {
        if (! $this->is_open) {
            return 'belum diaktifkan';
        }

        if (! AcademicYear::query()->where('is_active', true)->exists()) {
            return 'tahun ajaran aktif belum ada';
        }

        $today = now('Asia/Jakarta')->toDateString();

        if ($this->starts_on === null) {
            return 'tanggal buka belum diatur';
        }

        if ($today < $this->starts_on->toDateString()) {
            return 'belum masuk tanggal buka';
        }

        if ($this->ends_on === null) {
            return 'tanggal tutup belum diatur';
        }

        if ($today > $this->ends_on->toDateString()) {
            return 'sudah lewat tanggal tutup';
        }

        return null;
    }
}
