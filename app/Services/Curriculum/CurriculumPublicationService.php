<?php

namespace App\Services\Curriculum;

use App\Enums\InteractionType;
use App\Enums\StageType;
use App\Models\Curriculum;
use App\Models\CurriculumLesson;
use App\Models\CurriculumUnit;
use App\Models\CurriculumWorld;
use App\Models\LessonStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumPublicationService
{
    public function validateCurriculum(Curriculum $curriculum): void
    {
        $messages = [];

        if (blank($curriculum->title)) {
            $messages['title'] = 'A curriculum title is required before publication.';
        }

        if (blank($curriculum->slug)) {
            $messages['slug'] = 'A curriculum slug is required before publication.';
        }

        if (blank($curriculum->description)) {
            $messages['description'] = 'A curriculum description is required before publication.';
        }

        if (blank($curriculum->target_min_age) || blank($curriculum->target_max_age)) {
            $messages['ages'] = 'The curriculum must define an age range.';
        }

        if ((int) $curriculum->duration_weeks < 1) {
            $messages['duration_weeks'] = 'The curriculum must define a valid duration in weeks.';
        }

        if ((int) $curriculum->lessons_per_week < 1) {
            $messages['lessons_per_week'] = 'The curriculum must define lessons per week.';
        }

        if ($curriculum->worlds()->count() === 0) {
            $messages['worlds'] = 'At least one world is required before publication.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function validateWorld(CurriculumWorld $world): void
    {
        $messages = [];

        if (blank($world->name)) {
            $messages['name'] = 'A world name is required before publication.';
        }

        if (blank($world->short_description) && blank($world->story_description)) {
            $messages['description'] = 'A world description or story description is required before publication.';
        }

        if (blank($world->learning_outcomes) || ! is_array($world->learning_outcomes) || $world->learning_outcomes === []) {
            $messages['learning_outcomes'] = 'A world must define at least one learning outcome before publication.';
        }

        if ((int) $world->display_order < 1) {
            $messages['display_order'] = 'A world requires a valid display order.';
        }

        if ($world->units()->count() === 0) {
            $messages['units'] = 'A world needs at least one weekly unit before publication.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function validateUnit(CurriculumUnit $unit): void
    {
        $messages = [];

        if (blank($unit->title)) {
            $messages['title'] = 'A weekly unit title is required before publication.';
        }

        if (blank($unit->description)) {
            $messages['description'] = 'A weekly unit description is required before publication.';
        }

        if ((int) $unit->display_order < 1) {
            $messages['display_order'] = 'A weekly unit requires a valid display order.';
        }

        if ($unit->lessons()->count() === 0) {
            $messages['lessons'] = 'A weekly unit needs at least one lesson before publication.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function validateLesson(CurriculumLesson $lesson): void
    {
        $messages = [];

        if (blank($lesson->title)) {
            $messages['title'] = 'A lesson title is required before publication.';
        }

        if (blank($lesson->learner_objective)) {
            $messages['learner_objective'] = 'A lesson learner objective is required before publication.';
        }

        if ((int) $lesson->estimated_minutes < 1) {
            $messages['estimated_minutes'] = 'A lesson must define its estimated duration.';
        }

        if ($lesson->stages()->count() === 0) {
            $messages['stages'] = 'A lesson needs at least one stage before publication.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function validateStage(LessonStage $stage): void
    {
        $messages = [];

        if (blank($stage->title)) {
            $messages['title'] = 'A stage title is required before publication.';
        }

        if (! in_array($this->stringValue($stage->stage_type), StageType::values(), true)) {
            $messages['stage_type'] = 'A valid stage type is required before publication.';
        }

        if (! in_array($this->stringValue($stage->interaction_type), InteractionType::values(), true)) {
            $messages['interaction_type'] = 'A valid interaction type is required before publication.';
        }

        if (blank($stage->instruction_text)) {
            $messages['instruction_text'] = 'Child-facing instruction text is required before publication.';
        }

        if ((int) $stage->estimated_minutes < 1) {
            $messages['estimated_minutes'] = 'A stage must define its estimated duration.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function publishCurriculum(Curriculum $curriculum): void
    {
        DB::transaction(function () use ($curriculum): void {
            $curriculum->loadMissing(['worlds.units.lessons.stages', 'worlds.prerequisites', 'worlds.units.lessons.skills', 'worlds.units.lessons.stages.skills']);
            $this->validateCurriculum($curriculum);
            $curriculum->markPublished();
            $curriculum->save();

            foreach ($curriculum->worlds as $world) {
                $this->publishWorld($world, false);
            }
        });
    }

    public function publishWorld(CurriculumWorld $world, bool $validate = true): void
    {
        DB::transaction(function () use ($world, $validate): void {
            $world->loadMissing(['units.lessons.stages', 'units.lessons.skills', 'units.lessons.stages.skills']);

            if ($validate) {
                $this->validateWorld($world);
            }

            $world->markPublished();
            $world->save();

            foreach ($world->units as $unit) {
                $this->publishUnit($unit, false);
            }
        });
    }

    public function publishUnit(CurriculumUnit $unit, bool $validate = true): void
    {
        DB::transaction(function () use ($unit, $validate): void {
            $unit->loadMissing(['lessons.stages', 'lessons.skills', 'lessons.stages.skills']);

            if ($validate) {
                $this->validateUnit($unit);
            }

            $unit->markPublished();
            $unit->save();

            foreach ($unit->lessons as $lesson) {
                $this->publishLesson($lesson, false);
            }
        });
    }

    public function publishLesson(CurriculumLesson $lesson, bool $validate = true): void
    {
        DB::transaction(function () use ($lesson, $validate): void {
            $lesson->loadMissing(['stages.skills', 'skills']);

            if ($validate) {
                $this->validateLesson($lesson);
            }

            $lesson->markPublished();
            $lesson->save();

            foreach ($lesson->stages as $stage) {
                $this->publishStage($stage, false);
            }
        });
    }

    public function publishStage(LessonStage $stage, bool $validate = true): void
    {
        DB::transaction(function () use ($stage, $validate): void {
            $stage->loadMissing(['skills']);

            if ($validate) {
                $this->validateStage($stage);
            }

            $stage->markPublished();
            $stage->save();
        });
    }

    public function archiveCurriculum(Curriculum $curriculum): void
    {
        DB::transaction(function () use ($curriculum): void {
            $curriculum->loadMissing(['worlds.units.lessons.stages']);
            $this->archiveModelTree($curriculum);
        });
    }

    public function restoreCurriculum(Curriculum $curriculum): void
    {
        DB::transaction(function () use ($curriculum): void {
            $curriculum->loadMissing(['worlds.units.lessons.stages']);
            $this->restoreModelTree($curriculum);
        });
    }

    public function archiveWorld(CurriculumWorld $world): void
    {
        DB::transaction(function () use ($world): void {
            $world->loadMissing(['units.lessons.stages']);
            $this->archiveModelTree($world);
        });
    }

    public function restoreWorld(CurriculumWorld $world): void
    {
        DB::transaction(function () use ($world): void {
            $world->loadMissing(['units.lessons.stages']);
            $this->restoreModelTree($world);
        });
    }

    public function archiveUnit(CurriculumUnit $unit): void
    {
        DB::transaction(function () use ($unit): void {
            $unit->loadMissing(['lessons.stages']);
            $this->archiveModelTree($unit);
        });
    }

    public function restoreUnit(CurriculumUnit $unit): void
    {
        DB::transaction(function () use ($unit): void {
            $unit->loadMissing(['lessons.stages']);
            $this->restoreModelTree($unit);
        });
    }

    public function archiveLesson(CurriculumLesson $lesson): void
    {
        DB::transaction(function () use ($lesson): void {
            $lesson->loadMissing(['stages']);
            $this->archiveModelTree($lesson);
        });
    }

    public function restoreLesson(CurriculumLesson $lesson): void
    {
        DB::transaction(function () use ($lesson): void {
            $lesson->loadMissing(['stages']);
            $this->restoreModelTree($lesson);
        });
    }

    public function archiveStage(LessonStage $stage): void
    {
        DB::transaction(function () use ($stage): void {
            $this->archiveModelTree($stage);
        });
    }

    public function restoreStage(LessonStage $stage): void
    {
        DB::transaction(function () use ($stage): void {
            $this->restoreModelTree($stage);
        });
    }

    private function archiveModelTree(Model $model): void
    {
        if (method_exists($model, 'markArchived')) {
            $model->markArchived();
            $model->save();
        }

        foreach ($this->childTrees($model) as $child) {
            $this->archiveModelTree($child);
        }
    }

    private function restoreModelTree(Model $model): void
    {
        if (method_exists($model, 'markDraft')) {
            $model->markDraft();
            $model->save();
        }

        foreach ($this->childTrees($model) as $child) {
            $this->restoreModelTree($child);
        }
    }

    /**
     * @return array<int, Model>
     */
    private function childTrees(Model $model): array
    {
        return match (true) {
            $model instanceof Curriculum => $model->worlds->all(),
            $model instanceof CurriculumWorld => $model->units->all(),
            $model instanceof CurriculumUnit => $model->lessons->all(),
            $model instanceof CurriculumLesson => $model->stages->all(),
            default => [],
        };
    }

    private function stringValue(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
