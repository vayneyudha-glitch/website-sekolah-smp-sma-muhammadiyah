<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'photo_path', 'sort_order', 'school_level'])]
class SchoolFacility extends Model
{
}
