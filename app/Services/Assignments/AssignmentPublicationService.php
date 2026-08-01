<?php

namespace App\Services\Assignments;

use App\Enums\ContentStatus;
use App\Models\Assignment;
use App\Models\AssignmentItem;
use App\Models\AssignmentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class AssignmentPublicationService
{
    public function __construct(
        private readonly AssignmentQuestionHandlerRegistry $registry,
    ) {}

    public function validateVersion(AssignmentVersion $version): void
    {
        $version->loadMissing(['assignment', 'items.options', 'curriculumLinks', 'skills']);

        $messages = [];

        if (blank($version->title)) {
            $messages['title'] = 'An assignment title is required before publication.';
        }

        if (blank($version->child_instructions)) {
            $messages['child_instructions'] = 'Child-facing instructions are required before publication.';
        }

        if ($version->items->isEmpty()) {
            $messages['items'] = 'At least one question or activity is required before publication.';
        }

        $totalPoints = 0;

        foreach ($version->items as $item) {
            $itemMessages = $this->validateItem($item);
            foreach ($itemMessages as $key => $message) {
                $messages["items.{$item->display_order}.{$key}"] = $message;
            }
            $totalPoints += max(0, (int) $item->points);
        }

        foreach ($version->curriculumLinks as $index => $link) {
            $targetCount = collect([
                $link->curriculum_id,
                $link->curriculum_world_id,
                $link->curriculum_unit_id,
                $link->curriculum_lesson_id,
                $link->lesson_stage_id,
            ])->filter(fn ($value): bool => filled($value))->count();

            if ($targetCount !== 1) {
                $messages["curriculum_links.{$index}"] = 'Each curriculum link must target exactly one curriculum level.';
            }
        }

        if ($totalPoints < 1) {
            $messages['total_points'] = 'Assignment points must be greater than zero.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function publishVersion(AssignmentVersion $version, ?User $publisher = null): AssignmentVersion
    {
        return DB::transaction(function () use ($version, $publisher): AssignmentVersion {
            $version->loadMissing(['assignment.versions', 'items.options', 'curriculumLinks', 'skills']);
            $this->validateVersion($version);

            $totalPoints = $version->items->sum('points');
            $version->forceFill([
                'total_points' => $totalPoints,
                'status' => ContentStatus::Published,
                'published_at' => now(),
                'published_by' => $publisher?->id,
            ])->save();

            $assignment = $version->assignment;
            $assignment->forceFill([
                'status' => ContentStatus::Published,
                'current_version_id' => $version->getKey(),
                'archived_at' => null,
            ])->save();

            app(AssignmentAuditService::class)->record('assignment.published', $assignment, $publisher, [
                'version_id' => $version->getKey(),
                'total_points' => $totalPoints,
            ]);

            return $version->fresh(['assignment', 'items.options', 'curriculumLinks', 'skills']);
        });
    }

    public function archiveAssignment(Assignment $assignment, ?User $actor = null): Assignment
    {
        $assignment->forceFill([
            'status' => ContentStatus::Archived,
            'archived_at' => now(),
        ])->save();

        app(AssignmentAuditService::class)->record('assignment.archived', $assignment, $actor, [
            'assignment_id' => $assignment->getKey(),
        ]);

        return $assignment->fresh(['currentVersion']);
    }

    public function restoreAssignment(Assignment $assignment, ?User $actor = null): Assignment
    {
        $assignment->forceFill([
            'status' => $assignment->currentVersion?->isPublished() ? ContentStatus::Published : ContentStatus::Draft,
            'archived_at' => null,
        ])->save();

        app(AssignmentAuditService::class)->record('assignment.restored', $assignment, $actor, [
            'assignment_id' => $assignment->getKey(),
        ]);

        return $assignment->fresh(['currentVersion']);
    }

    /**
     * @return array<string, string>
     */
    private function validateItem(AssignmentItem $item): array
    {
        $messages = [];
        $questionType = $item->getRawOriginal('question_type');

        if (blank($item->title)) {
            $messages['title'] = 'Each question needs a title.';
        }

        if (! is_numeric($item->points) || (int) $item->points < 1) {
            $messages['points'] = 'Question points must be at least 1.';
        }

        if (blank($questionType)) {
            $messages['question_type'] = 'Each question needs a valid type.';
        }

        if ($this->hasExecutableExtension((string) $item->audio_prompt_path) || $this->hasExecutableExtension((string) $item->hint_audio_path) || $this->hasExecutableExtension((string) $item->image_path)) {
            $messages['media'] = 'Executable file types are not allowed.';
        }

        foreach ([$item->audio_prompt_path, $item->hint_audio_path, $item->image_path] as $path) {
            if (filled($path) && ! $this->mediaExists((string) $path)) {
                $messages['media'] = 'One or more referenced media files could not be found.';
            }
        }

        try {
            $handler = $this->registry->handlerFor((string) $questionType);
            $handler->validateConfiguration($item);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $error) {
                $messages[$key] = is_array($error) ? (string) head($error) : (string) $error;
            }
        }

        return $messages;
    }

    private function mediaExists(string $path): bool
    {
        $normalized = ltrim($path, '/');

        return File::exists(public_path($normalized))
            || File::exists(storage_path('app/private/'.$normalized))
            || File::exists(storage_path('app/public/'.$normalized));
    }

    private function hasExecutableExtension(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        return (bool) preg_match('/\.(php|phtml|phar|exe|bat|cmd|sh|js|jar|com)$/i', $path);
    }
}
