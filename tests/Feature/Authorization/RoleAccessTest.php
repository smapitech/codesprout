<?php

namespace Tests\Feature\Authorization;

use App\Enums\RoleName;
use App\Models\LearningClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_can_open_its_dashboard_area()
    {
        $this->actingAs($this->userByEmail('admin@childsbridge.test'))->get('/admin/dashboard')->assertOk();
        $this->actingAs($this->userByEmail('teacher@childsbridge.test'))->get('/teacher/dashboard')->assertOk();
        $this->actingAs($this->userByEmail('parent@childsbridge.test'))->get('/parent/dashboard')->assertOk();
        $this->actingAs($this->childUser())->get('/child/dashboard')->assertOk();
    }

    public function test_child_cannot_access_adult_dashboards()
    {
        $child = $this->childUser();

        $this->actingAs($child)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($child)->get('/teacher/dashboard')->assertForbidden();
        $this->actingAs($child)->get('/parent/dashboard')->assertForbidden();
    }

    public function test_parent_can_only_view_linked_child_profiles()
    {
        $parent = User::factory()->create([
            'email' => 'outsider-parent@childsbridge.test',
            'password' => 'Password123!',
        ]);
        $parent->assignRole(RoleName::Parent->value);

        $child = $this->childUser();

        $this->actingAs($parent)->get(route('parent.children.show', $child->id))->assertForbidden();
    }

    public function test_parent_can_view_linked_child_profiles()
    {
        $parent = $this->userByEmail('parent@childsbridge.test');
        $child = $this->childUser();

        $this->actingAs($parent)->get(route('parent.children.show', $child->id))->assertOk();
    }

    public function test_teacher_can_only_view_assigned_classes()
    {
        $teacher = User::factory()->create([
            'email' => 'outsider-teacher@childsbridge.test',
            'password' => 'Password123!',
        ]);
        $teacher->assignRole(RoleName::Teacher->value);

        $classroom = $this->learningClass();

        $this->actingAs($teacher)->get(route('teacher.classes.show', $classroom->id))->assertForbidden();
    }

    public function test_teacher_can_view_assigned_classes()
    {
        $teacher = $this->userByEmail('teacher@childsbridge.test');
        $classroom = $this->learningClass();

        $this->actingAs($teacher)->get(route('teacher.classes.show', $classroom->id))->assertOk();
    }

    private function userByEmail(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    private function childUser(): User
    {
        return User::query()
            ->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1001'))
            ->firstOrFail();
    }

    private function learningClass(): LearningClass
    {
        return LearningClass::query()->where('class_code', 'CB-KEY-01')->firstOrFail();
    }
}
