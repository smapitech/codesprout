<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('auth/login')
            ->where('childLoginUrl', '/child-login')
            ->has('demoCredentials', 3));
    }

    public function test_administrator_can_authenticate_using_the_login_screen()
    {
        $user = User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_teacher_can_authenticate_using_the_login_screen()
    {
        $user = User::query()->where('email', 'teacher@childsbridge.test')->firstOrFail();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_parent_can_authenticate_using_the_login_screen()
    {
        $user = User::query()->where('email', 'parent@childsbridge.test')->firstOrFail();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_deactivated_accounts_cannot_log_in()
    {
        $user = User::factory()->create([
            'email' => 'disabled@childsbridge.test',
            'password' => 'Password123!',
            'deactivated_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_logout()
    {
        $user = User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
