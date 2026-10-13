<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ppdb_application_id', 'category', 'original_name', 'path', 'mime_type',
    'status', 'review_reason', 'reviewed_by', 'reviewed_at',
])]
class PpdbDocument extends Model
{
    public const STATUS_WAITING = 'waiting';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public function application(): BelongsTo
    {
        return $this->belongsTo(PpdbApplication::class, 'ppdb_application_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }
}
