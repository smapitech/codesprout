<?php

namespace App\Services\Typing;

use App\Models\LearningClass;
use App\Models\TypingExercise;
use App\Models\TypingKeyStatistic;
use App\Models\TypingResult;
use App\Models\TypingSession;
use App\Models\User;
use Illuminate\Support\Collection;

class TypingReportService
{
    /**
     * @return array<string, mixed>
     */
    public function adminSummary(): array
    {
        return [
            'exercises' => TypingExercise::query()->count(),
            'published_exercises' => TypingExercise::query()->where('status', 'published')->count(),
            'sessions' => TypingSession::query()->count(),
            'completed_sessions' => TypingSession::query()->whereIn('status', ['completed', 'submitted', 'awaiting_review'])->count(),
            'average_first_attempt_accuracy' => round((float) TypingResult::query()->avg('first_attempt_accuracy'), 2),
            'average_final_text_accuracy' => round((float) TypingResult::query()->avg('final_text_accuracy'), 2),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function teacherRows(User $teacher): Collection
    {
        $childIds = $this->teacherChildIds($teacher);

        return TypingResult::query()
            ->with(['child.profile', 'exerciseVersion.exercise'])
            ->whereIn('child_id', $childIds)
            ->latest('completed_at')
            ->limit(50)
            ->get()
            ->map(fn (TypingResult $result): array => $this->resultRow($result));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function parentRows(User $parent): Collection
    {
        return TypingResult::query()
            ->with(['child.profile', 'exerciseVersion.exercise'])
            ->whereIn('child_id', $parent->children()->pluck('users.id'))
            ->latest('completed_at')
            ->limit(30)
            ->get()
            ->map(fn (TypingResult $result): array => $this->resultRow($result, false));
    }

    /**
     * @return array<string, mixed>
     */
    public function childSummary(User $child): array
    {
        $results = TypingResult::query()
            ->with('exerciseVersion.exercise')
            ->where('child_id', $child->id)
            ->latest('completed_at')
            ->limit(10)
            ->get();

        return [
            'recent' => $results->map(fn (TypingResult $result): array => $this->resultRow($result, false))->all(),
            'averageAccuracy' => round((float) TypingResult::query()->where('child_id', $child->id)->avg('first_attempt_accuracy'), 2),
            'practiceMinutes' => (int) ceil((int) TypingResult::query()->where('child_id', $child->id)->sum('active_duration_ms') / 60000),
            'confidentKeys' => TypingKeyStatistic::query()->where('child_id', $child->id)->whereIn('mastery_label', ['confident', 'ready'])->count(),
            'continueExercise' => TypingExercise::query()
                ->with('currentVersion')
                ->where('status', 'published')
                ->orderBy('title')
                ->first()?->only(['slug', 'title']),
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function teacherChildIds(User $teacher): Collection
    {
        return LearningClass::query()
            ->whereHas('teachers', fn ($query) => $query->where('users.id', $teacher->id))
            ->with('learners:id')
            ->get()
            ->flatMap(fn (LearningClass $class) => $class->learners->pluck('id'))
            ->unique()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function resultRow(TypingResult $result, bool $includeAdultMetrics = true): array
    {
        return [
            'id' => $result->id,
            'child' => $result->child?->profile?->preferred_name ?? $result->child?->name,
            'exercise' => $result->exerciseVersion->exercise->title,
            'type' => $result->exerciseVersion->exercise->exercise_type->label(),
            'completedAt' => $result->completed_at?->toFormattedDateString(),
            'firstAttemptAccuracy' => (float) $result->first_attempt_accuracy,
            'finalTextAccuracy' => (float) $result->final_text_accuracy,
            'wordsPerMinute' => $includeAdultMetrics ? $result->gross_words_per_minute : null,
            'inputMethod' => $result->metadata['input_method'] ?? 'unknown',
            'validity' => $result->validity_status->value,
            'message' => $result->gross_words_per_minute === null
                ? 'Not enough practice time for a fair speed measure yet.'
                : 'Typing rhythm is ready for adult review.',
        ];
    }
}
