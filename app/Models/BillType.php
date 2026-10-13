<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year_id', 'name', 'amount', 'school_level', 'grade', 'due_date', 'is_active'])]
class BillType extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'integer', 'due_date' => 'date', 'is_active' => 'boolean'];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(StudentInvoice::class);
    }
}
