<?php

namespace App\Services\Curriculum;

use App\Enums\ContentStatus;
use App\Models\CurriculumLesson;
use App\Models\CurriculumUnit;
use App\Models\CurriculumWorld;
use App\Models\LessonStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CurriculumDuplicationService
{
    public function duplicateWorld(CurriculumWorld $world): CurriculumWorld
    {
        return DB::transaction(function () use ($world): CurriculumWorld {
            $world->loadMissing(['curriculum', 'units.lessons.stages.skills', 'units.lessons.skills']);

            $clone = $world->replicate([
                'slug',
                'world_number',
                'display_order',
                'status',
                'published_at',
                'created_at',
                'updated_at',
            ]);

            $clone->name = $this->copyName($world->name);
            $clone->slug = $this->uniqueSlug($world->curriculum->worlds(), $clone->name);
            $clone->world_number = (int) $world->curriculum->worlds()->max('world_number') + 1;
            $clone->display_order = (int) $world->curriculum->worlds()->max('display_order') + 1;
            $clone->status = ContentStatus::Draft;
            $clone->published_at = null;
            $clone->save();

            foreach ($world->units as $unit) {
                $this->duplicateUnitIntoWorld($unit, $clone);
            }

            return $clone;
        });
    }

    public function duplicateUnit(CurriculumUnit $unit): CurriculumUnit
    {
        return DB::transaction(function () use ($unit): CurriculumUnit {
            $unit->loadMissing(['world', 'lessons.stages.skills', 'lessons.skills']);
            $clone = $this->duplicateUnitIntoWorld($unit, $unit->world);

            return $clone;
        });
    }

    public function duplicateLesson(CurriculumLesson $lesson): CurriculumLesson
    {
        return DB::transaction(function () use ($lesson): CurriculumLesson {
            $lesson->loadMissing(['unit.world', 'stages.skills', 'skills']);
            $clone = $this->duplicateLessonIntoUnit($lesson, $lesson->unit);

            return $clone;
        });
    }

    public function duplicateStage(LessonStage $stage): LessonStage
    {
        return DB::transaction(function () use ($stage): LessonStage {
            $stage->loadMissing(['lesson.unit.world', 'skills']);
            $clone = $this->duplicateStageIntoLesson($stage, $stage->lesson);

            return $clone;
        });
    }

    private function duplicateUnitIntoWorld(CurriculumUnit $unit, CurriculumWorld $world): CurriculumUnit
    {
        $clone = $unit->replicate([
            'slug',
            'week_number',
            'display_order',
            'status',
            'published_at',
            'created_at',
            'updated_at',
        ]);

        $clone->world_id = $world->getKey();
        $clone->title = $this->copyName($unit->title);
        $clone->slug = $this->uniqueSlug($world->units(), $clone->title);
        $clone->week_number = (int) $world->units()->max('week_number') + 1;
        $clone->display_order = (int) $world->units()->max('display_order') + 1;
        $clone->status = ContentStatus::Draft;
        $clone->published_at = null;
        $clone->save();

        foreach ($unit->lessons as $lesson) {
            $this->duplicateLessonIntoUnit($lesson, $clone);
        }

        return $clone;
    }

    private function duplicateLessonIntoUnit(CurriculumLesson $lesson, CurriculumUnit $unit): CurriculumLesson
    {
        $clone = $lesson->replicate([
            'slug',
            'lesson_number',
            'display_order',
            'status',
            'published_at',
            'created_at',
            'updated_at',
        ]);

        $clone->unit_id = $unit->getKey();
        $clone->title = $this->copyName($lesson->title);
        $clone->slug = $this->uniqueSlug($unit->lessons(), $clone->title);
        $clone->lesson_number = (int) $unit->lessons()->max('lesson_number') + 1;
        $clone->display_order = (int) $unit->lessons()->max('display_order') + 1;
        $clone->status = ContentStatus::Draft;
        $clone->published_at = null;
        $clone->save();

        $clone->skills()->sync($lesson->skills->pluck('id')->all());

        foreach ($lesson->stages as $stage) {
            $this->duplicateStageIntoLesson($stage, $clone);
        }

        return $clone;
    }

    private function duplicateStageIntoLesson(LessonStage $stage, CurriculumLesson $lesson): LessonStage
    {
        $clone = $stage->replicate([
            'slug',
            'display_order',
            'status',
            'published_at',
            'created_at',
            'updated_at',
        ]);

        $clone->lesson_id = $lesson->getKey();
        $clone->title = $this->copyName($stage->title);
        $clone->slug = $this->uniqueSlug($lesson->stages(), $clone->title);
        $clone->display_order = (int) $lesson->stages()->max('display_order') + 1;
        $clone->status = ContentStatus::Draft;
        $clone->published_at = null;
        $clone->save();

        $clone->skills()->sync($stage->skills->pluck('id')->all());

        return $clone;
    }

    private function copyName(string $name): string
    {
        return Str::of($name)->append(' Copy')->toString();
    }

    private function uniqueSlug(Builder|Relation $query, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while ((clone $query)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
