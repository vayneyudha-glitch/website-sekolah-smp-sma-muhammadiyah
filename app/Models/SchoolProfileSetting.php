<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'tagline', 'address', 'phone', 'whatsapp', 'email', 'latitude',
    'longitude', 'map_link', 'service_hours', 'welcome_message', 'vision',
    'mission', 'history', 'smp_description', 'sma_description', 'social_links',
])]
class SchoolProfileSetting extends Model
{
    protected function casts(): array
    {
        return ['social_links' => 'array'];
    }
}
