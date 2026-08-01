<?php

namespace App\Enums;

enum HtmlProjectStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case AwaitingReview = 'awaiting_review';
    case ChangesRequested = 'changes_requested';
    case Resubmitted = 'resubmitted';
    case Approved = 'approved';
    case Completed = 'completed';
    case Archived = 'archived';
    case Invalidated = 'invalidated';
    case Abandoned = 'abandoned';
    case Expired = 'expired';
    case TeacherPreview = 'teacher_preview';
    case AdministratorPreview = 'administrator_preview';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
