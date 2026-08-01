<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\LearningClass;
use App\Models\ParentChildRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_open_school_management(): void
    {
        $this->actingAs($this->teacher())->get(route('admin.school.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.school.index'))->assertOk();
    }

    public function test_administrator_creates_role_accounts_and_records_an_audit_event(): void
    {
        $this->actingAs($this->admin())->post(route('admin.school.users.store'), [
            'role' => RoleName::Teacher->value,
            'name' => 'Amina Teacher',
            'first_name' => 'Amina',
            'last_name' => 'Teacher',
            'email' => 'amina.teacher@example.test',
            'password' => 'TemporaryPassword123!',
            'staff_code' => 'CB-TEACH-2001',
            'job_title' => 'Coding Teacher',
            'subject_focus' => 'HTML',
        ])->assertRedirect();

        $teacher = User::query()->where('email', 'amina.teacher@example.test')->firstOrFail();
        $this->assertTrue($teacher->hasRole(RoleName::Teacher->value));
        $this->assertSame('CB-TEACH-2001', $teacher->teacherProfile?->staff_code);
        $this->assertDatabaseHas('audit_logs', ['action' => 'school.user.created', 'subject_id' => $teacher->id]);
    }

    public function test_administrator_connects_teacher_child_and_parent_without_duplicate_links(): void
    {
        $admin = $this->admin();
        $teacher = $this->teacher();
        $parent = User::query()->where('email', 'parent@childsbridge.test')->firstOrFail();
        $child = User::query()->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1001'))->firstOrFail();
        $class = LearningClass::query()->firstOrFail();

        ParentChildRelationship::query()->where('parent_user_id', $parent->id)->where('child_user_id', $child->id)->delete();

        $this->actingAs($admin)->post(route('admin.school.connections.store'), [
            'connection_type' => 'teacher_class', 'teacher_id' => $teacher->id, 'class_id' => $class->id, 'role_label' => 'Lead teacher', 'is_primary' => true,
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.school.connections.store'), [
            'connection_type' => 'child_class', 'child_id' => $child->id, 'class_id' => $class->id, 'is_primary' => true,
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.school.connections.store'), [
            'connection_type' => 'parent_child', 'parent_id' => $parent->id, 'child_id' => $child->id, 'relationship_type' => 'guardian', 'is_primary' => true,
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.school.connections.store'), [
            'connection_type' => 'parent_child', 'parent_id' => $parent->id, 'child_id' => $child->id, 'relationship_type' => 'guardian', 'is_primary' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('class_teacher_assignments', ['class_id' => $class->id, 'teacher_user_id' => $teacher->id]);
        $this->assertDatabaseHas('class_enrolments', ['class_id' => $class->id, 'child_user_id' => $child->id]);
        $this->assertSame(1, ParentChildRelationship::query()->where('parent_user_id', $parent->id)->where('child_user_id', $child->id)->count());
    }

    private function admin(): User
    {
        return User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();
    }

    private function teacher(): User
    {
        return User::query()->where('email', 'teacher@childsbridge.test')->firstOrFail();
    }
}
