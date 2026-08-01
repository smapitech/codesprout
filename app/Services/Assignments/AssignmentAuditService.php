<?php

namespace App\Services\Assignments;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AssignmentAuditService
{
    public function record(string $action, Model $subject, ?User $actor = null, array $metadata = []): AuditLog
    {
        return AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
