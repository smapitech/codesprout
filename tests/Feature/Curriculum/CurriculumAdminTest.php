<?php

namespace Tests\Feature\Curriculum;

use App\Enums\ContentStatus;
use App\Models\Curriculum;
use App\Models\CurriculumWorld;

class CurriculumAdminTest extends CurriculumTestCase
{
    public function test_only_administrators_can_create_curriculum_content(): void
    {
        $payload = $this->curriculumPayload('Creative Pathway');

        $this->actingAs($this->teacher())->post(route('admin.curriculum.store', absolute: false), $payload)->assertForbidden();
        $this->actingAs($this->parentUser())->post(route('admin.curriculum.store', absolute: false), $payload)->assertForbidden();
        $this->actingAs($this->childUser())->post(route('admin.curriculum.store', absolute: false), $payload)->assertForbidden();
    }

    public function test_administrator_can_create_curriculum_content(): void
    {
        $payload = $this->curriculumPayload('Creative Pathway');

        $response = $this->actingAs($this->admin())->post(route('admin.curriculum.store', absolute: false), $payload);

        $response->assertRedirect();

        $curriculum = Curriculum::query()->where('title', 'Creative Pathway')->firstOrFail();

        $this->assertSame('creative-pathway', $curriculum->slug);
        $this->assertSame(ContentStatus::Draft, $curriculum->status);
        $this->assertSame($this->admin()->id, $curriculum->created_by);
    }

    public function test_duplicate_slugs_are_generated_uniquely(): void
    {
        $payload = $this->curriculumPayload('CodeSprout One-Year Programme');

        $this->actingAs($this->admin())->post(route('admin.curriculum.store', absolute: false), $payload)->assertRedirect();

        $this->assertDatabaseHas('curricula', [
            'slug' => 'codesprout-one-year-programme-2',
        ]);
    }

    public function test_teachers_have_read_only_curriculum_access(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->get(route('admin.curriculum.index', absolute: false))->assertForbidden();
        $this->actingAs($teacher)->get(route('teacher.curriculum.index', absolute: false))->assertOk();
    }

    public function test_children_cannot_access_administration_routes(): void
    {
        $curriculum = $this->seededCurriculum();
        $child = $this->childUser();

        $this->actingAs($child)->get(route('admin.curriculum.index', absolute: false))->assertForbidden();
        $this->actingAs($child)->get(route('admin.curriculum.show', $curriculum, absolute: false))->assertForbidden();
    }

    public function test_cross_parent_reorder_attempts_are_rejected(): void
    {
        $curriculum = $this->seededCurriculum();
        $otherCurriculum = Curriculum::factory()->create([
            'title' => 'Sibling Curriculum',
            'slug' => 'sibling-curriculum',
            'description' => 'A second curriculum used only for route protection tests.',
        ]);

        $otherWorld = CurriculumWorld::factory()->for($otherCurriculum)->create([
            'name' => 'Detached World',
            'slug' => 'detached-world',
            'world_number' => 1,
            'display_order' => 1,
        ]);

        $this->actingAs($this->admin())->post("/admin/curriculum/{$curriculum->slug}/worlds/{$otherWorld->slug}/move/up")->assertNotFound();
    }

    private function curriculumPayload(string $title): array
    {
        return [
            'title' => $title,
            'description' => 'A short curriculum used for admin access tests.',
            'target_min_age' => 6,
            'target_max_age' => 7,
            'duration_weeks' => 8,
            'lessons_per_week' => 3,
            'version' => '1.0.0',
            'status' => ContentStatus::Draft->value,
        ];
    }
}
