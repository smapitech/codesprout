<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumLesson;
use App\Models\CurriculumUnit;
use App\Models\CurriculumWorld;
use App\Models\LessonStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CurriculumOrderingService
{
    public function moveWorld(CurriculumWorld $world, string $direction): void
    {
        $this->moveWithin(
            $world,
            CurriculumWorld::query()->where('curriculum_id', $world->curriculum_id),
            $direction,
            static fn (CurriculumWorld $item): int => $item->display_order,
        );
    }

    public function moveUnit(CurriculumUnit $unit, string $direction): void
    {
        $this->moveWithin(
            $unit,
            CurriculumUnit::query()->where('world_id', $unit->world_id),
            $direction,
            static fn (CurriculumUnit $item): int => $item->display_order,
        );
    }

    public function moveLesson(CurriculumLesson $lesson, string $direction): void
    {
        $this->moveWithin(
            $lesson,
            CurriculumLesson::query()->where('unit_id', $lesson->unit_id),
            $direction,
            static fn (CurriculumLesson $item): int => $item->display_order,
        );
    }

    public function moveStage(LessonStage $stage, string $direction): void
    {
        $this->moveWithin(
            $stage,
            LessonStage::query()->where('lesson_id', $stage->lesson_id),
            $direction,
            static fn (LessonStage $item): int => $item->display_order,
        );
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $siblingsQuery
     * @param  callable(TModel): int  $displayOrderGetter
     */
    private function moveWithin(Model $record, $siblingsQuery, string $direction, callable $displayOrderGetter): void
    {
        DB::transaction(function () use ($record, $siblingsQuery, $direction): void {
            $siblings = $siblingsQuery
                ->lockForUpdate()
                ->orderBy('display_order')
                ->orderBy('id')
                ->get()
                ->values();

            $index = $siblings->search(static fn (Model $item): bool => (int) $item->getKey() === (int) $record->getKey());

            if ($index === false) {
                return;
            }

            $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

            if ($targetIndex < 0 || $targetIndex >= $siblings->count()) {
                return;
            }

            $siblings[$index] = $siblings[$targetIndex];
            $siblings[$targetIndex] = $record->refresh();

            $this->resequence($siblings);
        });
    }

    /**
     * @param  Collection<int, Model>  $siblings
     */
    private function resequence(Collection $siblings): void
    {
        $temporaryBase = 1000000;

        $siblings->values()->each(function (Model $item, int $index) use ($temporaryBase): void {
            $item->forceFill(['display_order' => $temporaryBase + $index])->save();
        });

        $siblings->values()->each(function (Model $item, int $index): void {
            $item->forceFill(['display_order' => $index + 1])->save();
        });
    }
}
