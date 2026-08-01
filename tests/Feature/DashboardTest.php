<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_redirects_administrators_to_the_admin_area()
    {
        $user = User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/admin/dashboard');
    }

    public function test_dashboard_redirects_teachers_to_the_teacher_area()
    {
        $user = User::query()->where('email', 'teacher@childsbridge.test')->firstOrFail();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/teacher/dashboard');
    }

    public function test_dashboard_redirects_parents_to_the_parent_area()
    {
        $user = User::query()->where('email', 'parent@childsbridge.test')->firstOrFail();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/parent/dashboard');
    }

    public function test_dashboard_redirects_children_to_the_child_area()
    {
        $user = User::query()->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1001'))->firstOrFail();

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/child/dashboard');
    }

    public function test_users_without_roles_are_forbidden_from_the_dashboard()
    {
        $user = User::factory()->create([
            'email' => 'roleless@childsbridge.test',
            'password' => 'Password123!',
        ]);

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }
}
