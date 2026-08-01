<?php

namespace App\Services\Typing;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class TypingAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(string $action, Model $subject, ?User $actor = null, array $metadata = []): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => Arr::except($metadata, ['raw_text', 'typed_text', 'password', 'token']),
            'created_at' => now(),
        ]);
    }
}
