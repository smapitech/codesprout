<?php

namespace Tests\Feature\Curriculum;

use App\Enums\ContentStatus;
use App\Models\Curriculum;
use App\Services\Curriculum\CurriculumDuplicationService;
use App\Services\Curriculum\CurriculumImportService;
use App\Services\Curriculum\CurriculumOrderingService;
use App\Services\Curriculum\CurriculumPrerequisiteService;
use App\Services\Curriculum\CurriculumPublicationService;
use Database\Seeders\Data\CodeSproutCurriculumSeedData;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CurriculumServicesTest extends CurriculumTestCase
{
    public function test_publication_validation_rejects_incomplete_records(): void
    {
        $curriculum = Curriculum::factory()->create([
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $this->expectException(ValidationException::class);

        app(CurriculumPublicationService::class)->validateCurriculum($curriculum);
    }

    public function test_publication_validation_rejects_stages_without_instructions(): void
    {
        $curriculum = $this->createDraftCurriculumTree();
        $stage = $curriculum->worlds->first()->units->first()->lessons->first()->stages->first();
        $stage->forceFill([
            'instruction_text' => '',
        ])->save();

        $this->expectException(ValidationException::class);

        app(CurriculumPublicationService::class)->validateStage($stage->fresh());
    }

    public function test_valid_content_can_be_published(): void
    {
        $curriculum = $this->createDraftCurriculumTree();

        app(CurriculumPublicationService::class)->publishCurriculum($curriculum);

        $curriculum->refresh()->load('worlds.units.lessons.stages');

        $this->assertSame(ContentStatus::Published, $curriculum->status);
        $this->assertNotNull($curriculum->published_at);

        $world = $curriculum->worlds->first();
        $unit = $world->units->first();
        $lesson = $unit->lessons->first();
        $stage = $lesson->stages->first();

        $this->assertSame(ContentStatus::Published, $world->status);
        $this->assertSame(ContentStatus::Published, $unit->status);
        $this->assertSame(ContentStatus::Published, $lesson->status);
        $this->assertSame(ContentStatus::Published, $stage->status);
    }

    public function test_curriculum_duplication_produces_independent_records(): void
    {
        $originalWorld = $this->seededWorld('keyboard-island');
        $duplicationService = app(CurriculumDuplicationService::class);

        $duplicate = $duplicationService->duplicateWorld($originalWorld);

        $this->assertNotSame($originalWorld->getKey(), $duplicate->getKey());
        $this->assertNotSame($originalWorld->slug, $duplicate->slug);
        $this->assertSame($originalWorld->units()->count(), $duplicate->units()->count());

        $duplicate->forceFill(['name' => 'Keyboard Island Clone'])->save();

        $this->assertSame('Keyboard Island', $originalWorld->refresh()->name);
        $this->assertSame('Keyboard Island Clone', $duplicate->refresh()->name);
    }

    public function test_prerequisite_cycles_are_rejected(): void
    {
        $curriculum = Curriculum::factory()->create([
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $worldOne = $curriculum->worlds()->create([
            'name' => 'World One',
            'slug' => 'world-one',
            'world_number' => 1,
            'short_description' => 'First test world.',
            'story_description' => 'The first test world.',
            'learning_outcomes' => ['One'],
            'theme_colour' => '#2fb37b',
            'accent_colour' => '#f7b53b',
            'estimated_weeks' => 1,
            'display_order' => 1,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $worldTwo = $curriculum->worlds()->create([
            'name' => 'World Two',
            'slug' => 'world-two',
            'world_number' => 2,
            'short_description' => 'Second test world.',
            'story_description' => 'The second test world.',
            'learning_outcomes' => ['Two'],
            'theme_colour' => '#18a7b8',
            'accent_colour' => '#f57b5d',
            'estimated_weeks' => 1,
            'display_order' => 2,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $prerequisiteService = app(CurriculumPrerequisiteService::class);
        $prerequisiteService->syncWorldPrerequisites($worldOne, [$worldTwo->id]);

        $this->expectException(ValidationException::class);

        $prerequisiteService->syncWorldPrerequisites($worldTwo, [$worldOne->id]);
    }

    public function test_curriculum_imports_are_validated(): void
    {
        $payload = CodeSproutCurriculumSeedData::build();
        $payload['curriculum']['worlds'][0]['units'][0]['lessons'][0]['stages'][0]['stage_type'] = 'not-a-stage-type';

        $this->expectException(ValidationException::class);

        app(CurriculumImportService::class)->import($payload, false);
    }

    public function test_failed_imports_roll_back_completely(): void
    {
        $payload = CodeSproutCurriculumSeedData::build();
        $payload['curriculum']['title'] = 'Broken Import Programme';
        $payload['curriculum']['slug'] = 'broken-import-programme';
        $payload['curriculum']['worlds'][1]['world_number'] = $payload['curriculum']['worlds'][0]['world_number'];

        try {
            app(CurriculumImportService::class)->import($payload, false);
            $this->fail('The import should have failed because two worlds share the same world number.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('curricula', ['slug' => 'broken-import-programme']);
            $this->assertDatabaseCount('curriculum_worlds', 12);
        }
    }

    public function test_world_unit_lesson_and_stage_ordering_work(): void
    {
        $orderingService = app(CurriculumOrderingService::class);

        $firstWorld = $this->seededWorld('computer-discovery');
        $secondWorld = $this->seededWorld('mouse-adventure');

        $orderingService->moveWorld($secondWorld, 'up');

        $this->assertSame(1, $secondWorld->refresh()->display_order);
        $this->assertSame(2, $firstWorld->refresh()->display_order);

        $world = $this->seededWorld('keyboard-island');
        $units = $world->units()->orderBy('display_order')->get();
        $this->assertGreaterThan(1, $units->count());

        $orderingService->moveUnit($units[1], 'up');

        $this->assertSame(1, $units[1]->refresh()->display_order);
        $this->assertSame(2, $units[0]->refresh()->display_order);

        $lessonUnits = $world->units()->orderBy('display_order')->get();
        $lesson = $lessonUnits[0]->lessons()->orderBy('display_order')->get();
        $this->assertGreaterThan(1, $lesson->count());

        $orderingService->moveLesson($lesson[1], 'up');

        $this->assertSame(1, $lesson[1]->refresh()->display_order);
        $this->assertSame(2, $lesson[0]->refresh()->display_order);

        $stages = $lesson[0]->stages()->orderBy('display_order')->get();
        $this->assertGreaterThan(1, $stages->count());

        $orderingService->moveStage($stages[1], 'up');

        $this->assertSame(1, $stages[1]->refresh()->display_order);
        $this->assertSame(2, $stages[0]->refresh()->display_order);
    }
}
