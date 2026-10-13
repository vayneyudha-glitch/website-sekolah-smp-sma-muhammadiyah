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
}
