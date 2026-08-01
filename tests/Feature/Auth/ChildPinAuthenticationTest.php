<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\ChildProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChildPinAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_child_login_screen_can_be_rendered()
    {
        $response = $this->get('/child-login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('auth/child-login')
            ->has('demoLearners', 2));
    }

    public function test_child_can_authenticate_with_pin()
    {
        $child = User::query()->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1001'))->firstOrFail();

        $response = $this->post('/child-login', [
            'learner_id' => 'CB-LEARN-1001',
            'pin' => '1234',
        ]);

        $this->assertAuthenticatedAs($child);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_child_pin_login_rejects_invalid_attempts()
    {
        $response = $this->post('/child-login', [
            'learner_id' => 'CB-LEARN-1001',
            'pin' => '0000',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('pin');
    }

    public function test_child_pin_login_is_rate_limited()
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/child-login', [
                'learner_id' => 'CB-LEARN-1001',
                'pin' => '0000',
            ]);
        }

        $response = $this->post('/child-login', [
            'learner_id' => 'CB-LEARN-1001',
            'pin' => '0000',
        ]);

        $response->assertSessionHasErrors('learner_id');
    }

    public function test_deactivated_child_accounts_cannot_log_in()
    {
        $child = User::factory()->create([
            'email' => null,
            'name' => 'Deactivated Child',
            'password' => 'Password123!',
            'deactivated_at' => now(),
        ]);

        $child->assignRole(RoleName::Child->value);

        ChildProfile::query()->create([
            'user_id' => $child->id,
            'learner_id' => 'CB-LEARN-9999',
            'pin_mode' => 'numeric',
            'pin_hash' => Hash::make('4321'),
            'pin_hint' => null,
            'last_pin_verified_at' => null,
            'pin_reset_required_at' => null,
            'notes' => null,
        ]);

        $response = $this->post('/child-login', [
            'learner_id' => 'CB-LEARN-9999',
            'pin' => '4321',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('learner_id');
    }
}
