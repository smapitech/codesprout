<?php

namespace Tests\Feature\Curriculum;

use App\Enums\ContentStatus;
use Illuminate\Support\Facades\DB;

class CurriculumVisibilityTest extends CurriculumTestCase
{
    public function test_published_worlds_appear_in_child_journey(): void
    {
        $response = $this->actingAs($this->childUser())->get(route('child.journey', absolute: false));

        $response->assertOk();
        $response->assertSee('Keyboard Island');
        $response->assertSee('Computer Discovery');
    }

    public function test_draft_worlds_do_not_appear_in_child_journey(): void
    {
        $world = $this->seededWorld('symbol-mountain');
        $world->forceFill([
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ])->save();

        $response = $this->actingAs($this->childUser())->get(route('child.journey', absolute: false));

        $response->assertOk();
        $response->assertDontSee('Symbol Mountain');
    }

    public function test_archived_worlds_are_hidden_from_children(): void
    {
        $world = $this->seededWorld('logic-land');
        $world->forceFill([
            'status' => ContentStatus::Archived,
        ])->save();

        $response = $this->actingAs($this->childUser())->get(route('child.journey', absolute: false));

        $response->assertOk();
        $response->assertDontSee('Logic Land');
    }

    public function test_children_cannot_retrieve_draft_world_urls(): void
    {
        $world = $this->seededWorld('typing-jungle');
        $world->forceFill([
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ])->save();

        $this->actingAs($this->childUser())->get(route('child.journey', $world, absolute: false))->assertNotFound();
    }

    public function test_teacher_access_is_limited_to_assigned_curriculum_context(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->get(route('teacher.curriculum.show', $this->seededWorld('keyboard-island'), absolute: false))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.curriculum.show', $this->seededWorld('typing-jungle'), absolute: false))->assertForbidden();
    }

    public function test_child_journey_queries_remain_reasonable(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->childUser())->get(route('child.journey', absolute: false))->assertOk();

        $this->assertLessThan(40, count(DB::getQueryLog()));
    }
}
