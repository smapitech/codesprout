<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;

trait HasContentStatus
{
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Draft->value);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Archived->value);
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === ContentStatus::Draft;
    }

    public function isArchived(): bool
    {
        return $this->status === ContentStatus::Archived;
    }

    public function markPublished(): void
    {
        $this->status = ContentStatus::Published;
        $this->published_at ??= now();
    }

    public function markDraft(): void
    {
        $this->status = ContentStatus::Draft;
        $this->published_at = null;
    }

    public function markArchived(): void
    {
        $this->status = ContentStatus::Archived;
    }
}
