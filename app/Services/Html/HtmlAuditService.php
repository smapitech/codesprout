<?php

namespace App\Services\Html;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class HtmlAuditService
{
    public function record(string $action, Model $subject, ?User $actor = null, array $metadata = []): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => Arr::except($metadata, ['source_html', 'unsanitised_html', 'raw_project_source']),
            'created_at' => now(),
        ]);
    }
}
