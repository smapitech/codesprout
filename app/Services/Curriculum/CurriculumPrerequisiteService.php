<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumLesson;
use App\Models\CurriculumWorld;
use App\Models\LessonStage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumPrerequisiteService
{
    /**
     * @param  array<int, int|string>  $prerequisiteIds
     */
    public function syncWorldPrerequisites(CurriculumWorld $world, array $prerequisiteIds): void
    {
        $prerequisiteIds = $this->normalizeIds($prerequisiteIds);
        $this->assertWorldPrerequisitesAreValid($world, $prerequisiteIds);

        $world->prerequisites()->sync($prerequisiteIds);
    }

    /**
     * @param  array<int, int|string>  $prerequisiteIds
     */
    public function syncLessonPrerequisites(CurriculumLesson $lesson, array $prerequisiteIds): void
    {
        $prerequisiteIds = $this->normalizeIds($prerequisiteIds);
        $this->assertLessonPrerequisitesAreValid($lesson, $prerequisiteIds);

        $lesson->prerequisites()->sync($prerequisiteIds);
    }

    /**
     * @param  array<int, int|string>  $prerequisiteIds
     */
    public function syncStagePrerequisites(LessonStage $stage, array $prerequisiteIds): void
    {
        $prerequisiteIds = $this->normalizeIds($prerequisiteIds);
        $this->assertStagePrerequisitesAreValid($stage, $prerequisiteIds);

        $stage->prerequisites()->sync($prerequisiteIds);
    }

    /**
     * @param  array<int, int|string>  $prerequisiteIds
     */
    public function assertWorldPrerequisitesAreValid(CurriculumWorld $world, array $prerequisiteIds): void
    {
        $prerequisiteIds = $this->normalizeIds($prerequisiteIds);
        $messages = [];

        if ($world->curriculum_id === null) {
            $messages['world'] = 'The world must belong to a curriculum before prerequisites can be managed.';
        }

        foreach ($prerequisiteIds as $prerequisiteId) {
            if ($prerequisiteId === (int) $world->getKey()) {
                $messages['curriculum_world_id'] = 'A world cannot depend on itself.';
                break;
            }
        }

        if ($messages === [] && $prerequisiteIds !== []) {
            $existingIds = CurriculumWorld::query()
                ->where('curriculum_id', (int) $world->curriculum_id)
                ->whereIn('id', $prerequisiteIds)
                ->pluck('id')
                ->all();

            if (count($existingIds) !== count($prerequisiteIds)) {
                $messages['curriculum_world_id'] = 'All world prerequisites must belong to the same curriculum.';
            }
        }

        if ($messages === []) {
            foreach ($prerequisiteIds as $prerequisiteId) {
                if ($this->wouldCreateCycle('curriculum_world_prerequisites', 'curriculum_world_id', 'prerequisite_world_id', (int) $world->getKey(), $prerequisiteId)) {
                    $messages['curriculum_world_id'] = 'Prerequisites cannot create a circular dependency.';
                    break;
                }
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array<int, int|string>  $prerequisiteIds
     */
    public function assertLessonPrerequisitesAreValid(CurriculumLesson $lesson, array $prerequisiteIds): void
    {
        $prerequisiteIds = $this->normalizeIds($prerequisiteIds);
        $messages = [];

        foreach ($prerequisiteIds as $prerequisiteId) {
            if ($prerequisiteId === (int) $lesson->getKey()) {
                $messages['lesson_id'] = 'A lesson cannot depend on itself.';
                break;
            }
        }

        if ($lesson->unit?->world?->curriculum_id === null) {
            $messages['lesson'] = 'The lesson must be attached to a curriculum before prerequisites can be managed.';
        }

        if ($messages === [] && $prerequisiteIds !== []) {
            $existingIds = CurriculumLesson::query()->whereIn('id', $prerequisiteIds)->pluck('id')->all();

            if (count($existingIds) !== count($prerequisiteIds)) {
                $messages['lesson_id'] = 'All lesson prerequisites must exist before they can be assigned.';
            }
        }

        if ($messages === []) {
            foreach ($prerequisiteIds as $prerequisiteId) {
                if ($this->wouldCreateCycle('lesson_prerequisites', 'lesson_id', 'prerequisite_lesson_id', (int) $lesson->getKey(), $prerequisiteId)) {
                    $messages['lesson_id'] = 'Prerequisites cannot create a circular dependency.';
                    break;
                }
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array<int, int|string>  $prerequisiteIds
     */
    public function assertStagePrerequisitesAreValid(LessonStage $stage, array $prerequisiteIds): void
    {
        $prerequisiteIds = $this->normalizeIds($prerequisiteIds);
        $messages = [];

        foreach ($prerequisiteIds as $prerequisiteId) {
            if ($prerequisiteId === (int) $stage->getKey()) {
                $messages['lesson_stage_id'] = 'A stage cannot depend on itself.';
                break;
            }
        }

        if ($messages === [] && $prerequisiteIds !== []) {
            $existingIds = LessonStage::query()
                ->where('lesson_id', (int) $stage->lesson_id)
                ->whereIn('id', $prerequisiteIds)
                ->pluck('id')
                ->all();

            if (count($existingIds) !== count($prerequisiteIds)) {
                $messages['lesson_stage_id'] = 'All stage prerequisites must belong to the same lesson.';
            }
        }

        if ($messages === []) {
            foreach ($prerequisiteIds as $prerequisiteId) {
                if ($this->wouldCreateCycle('lesson_stage_prerequisites', 'lesson_stage_id', 'prerequisite_stage_id', (int) $stage->getKey(), $prerequisiteId)) {
                    $messages['lesson_stage_id'] = 'Prerequisites cannot create a circular dependency.';
                    break;
                }
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function wouldCreateCycle(string $tableName, string $sourceForeignKey, string $targetForeignKey, int $sourceId, int $targetId): bool
    {
        if ($sourceId === $targetId) {
            return true;
        }

        $visited = [];
        $stack = [$targetId];

        while ($stack !== []) {
            $currentId = array_pop($stack);

            if ($currentId === $sourceId) {
                return true;
            }

            if (isset($visited[$currentId])) {
                continue;
            }

            $visited[$currentId] = true;

            $stack = array_merge(
                $stack,
                DB::table($tableName)
                    ->where($sourceForeignKey, $currentId)
                    ->pluck($targetForeignKey)
                    ->map(static fn ($value): int => (int) $value)
                    ->all(),
            );
        }

        return false;
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_map(static fn ($id): int => (int) $id, Arr::wrap($ids))));
    }
}
