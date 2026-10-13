<?php

namespace App\Services\Payments;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PaymentAuditLogger
{
    public function __construct(private readonly Request $request) {}

    /** @param array<string, mixed>|null $metadata */
    public function record(?User $user, string $action, Model $record, string $description, ?array $metadata = null): void
    {
        AuditLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'record_type' => $record::class,
            'record_id' => $record->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $this->request->ip(),
        ]);
    }
}
