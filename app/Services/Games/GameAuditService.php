<?php

namespace App\Services\Games;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GameAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(string $action, Model $subject, ?User $actor = null, array $metadata = []): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
