<?php

namespace App\Services\Games;

use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GameReportService
{
    /**
     * @return array<string, mixed>
     */
    public function teacherSummary(User $teacher): array
    {
        $query = $this->teacherResultsQuery($teacher);
        $count = (clone $query)->count();

        return [
            'sessions_started' => $this->teacherSessionsQuery($teacher)->count(),
            'sessions_completed' => (clone $query)->where('completion_status', 'completed')->count(),
            'sessions_abandoned' => $this->teacherSessionsQuery($teacher)->where('status', 'abandoned')->count(),
            'completion_rate' => $count > 0 ? round(((clone $query)->where('completion_status', 'completed')->count() / $count) * 100, 2) : 0,
            'average_accuracy' => round((float) (clone $query)->avg('accuracy'), 2),
            'average_completion_time' => round((float) (clone $query)->avg('completion_time'), 2),
            'hints_used' => (int) (clone $query)->sum('hints_used'),
            'results' => $this->teacherResults($teacher),
        ];
    }

    public function teacherResults(User $teacher): Collection
    {
        return $this->teacherResultsQuery($teacher)
            ->with(['session.child.childProfile', 'session.gameVersion.definition', 'session.assignmentAllocation.classroom'])
            ->latest('calculated_at')
            ->get()
            ->map(fn (GameResult $result): array => $this->resultRow($result));
    }

    public function parentResults(User $parent): Collection
    {
        $childIds = $parent->children()->pluck('users.id');

        return GameResult::query()
            ->where('released_to_parent', true)
            ->whereHas('session', fn (Builder $query) => $query->whereIn('child_id', $childIds))
            ->with(['session.child.childProfile', 'session.gameVersion.definition'])
            ->latest('calculated_at')
            ->get()
            ->map(fn (GameResult $result): array => $this->resultRow($result));
    }

    private function teacherResultsQuery(User $teacher): Builder
    {
        return GameResult::query()->whereHas('session', fn (Builder $query) => $this->scopeTeacherSessions($query, $teacher));
    }

    private function teacherSessionsQuery(User $teacher): Builder
    {
        return GameSession::query()->where(fn (Builder $query) => $this->scopeTeacherSessions($query, $teacher));
    }

    private function scopeTeacherSessions(Builder $query, User $teacher): void
    {
        if ($teacher->hasRole('administrator')) {
            return;
        }

        $classIds = $teacher->teachingClasses()->pluck('classes.id')->all();

        $query->whereHas('child.enrolledClasses', fn (Builder $classQuery) => $classQuery->whereIn('classes.id', $classIds));
    }

    private function resultRow(GameResult $result): array
    {
        return [
            'child' => $result->session->child->name,
            'learner_id' => $result->session->child->childProfile?->learner_id,
            'game' => $result->session->gameVersion->definition->name,
            'category' => $result->session->gameVersion->definition->category->label(),
            'game_type' => $result->session->gameVersion->definition->game_type->label(),
            'difficulty' => $result->session->difficulty->label(),
            'completion_status' => $result->completion_status->value,
            'accuracy' => (float) $result->accuracy,
            'completion_time' => $result->completion_time,
            'hints_used' => $result->hints_used,
            'score' => (float) $result->score,
            'maximum_score' => (float) $result->maximum_score,
            'completed_at' => $result->session->completed_at?->toIso8601String(),
        ];
    }
}
