<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Curriculum;
use App\Models\CurriculumWorld;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_the_homepage(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $page->component('welcome')
                    ->where('page.title', 'CodeSprout | Game-Based Coding for Children Ages 6-7')
                    ->where('curriculum.is_fallback', false)
                    ->where('curriculum.published_world_count', 12)
                    ->has('featuredWorlds', 4)
                    ->has('learningWorlds', 12);
            });
    }

    public function test_homepage_links_point_to_existing_routes(): void
    {
        $this->get('/')->assertInertia(function (AssertableInertia $page): void {
            $page->component('welcome')
                ->where('links.home', '/')
                ->where('links.login', '/login')
                ->where('links.childLogin', '/child-login')
                ->where('links.startAdventure', '/child-login')
                ->where('links.dashboard', null)
                ->where('authState.authenticated', false)
                ->where('links.privacy', '/privacy')
                ->where('links.terms', '/terms');
        });
    }

    public function test_authenticated_homepage_links_return_to_the_dashboard(): void
    {
        $admin = User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();

        $this->actingAs($admin)->get('/')->assertInertia(function (AssertableInertia $page): void {
            $page->component('welcome')
                ->where('links.login', '/dashboard')
                ->where('links.startAdventure', '/dashboard')
                ->where('links.dashboard', '/dashboard')
                ->where('authState.authenticated', true);
        });
    }

    public function test_published_curriculum_appears_and_draft_content_is_hidden(): void
    {
        $response = $this->get('/');

        $response->assertInertia(function (AssertableInertia $page): void {
            $page->where('curriculum.is_fallback', false)
                ->where('curriculum.published_world_count', 12)
                ->where('featuredWorlds.0.title', 'Mouse Adventure')
                ->where('featuredWorlds.1.title', 'Keyboard Island')
                ->where('featuredWorlds.2.title', 'Typing Jungle')
                ->where('featuredWorlds.3.title', 'HTML Builder Bay');
        });

        $world = CurriculumWorld::query()->where('slug', 'symbol-mountain')->firstOrFail();
        $world->forceFill([
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ])->save();

        $draftResponse = $this->get('/');
        $draftResponse->assertDontSee('Symbol Mountain');
    }

    public function test_homepage_falls_back_when_no_curriculum_has_been_published(): void
    {
        Curriculum::query()->update([
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $response = $this->get('/');

        $response->assertOk()->assertInertia(function (AssertableInertia $page): void {
            $page->where('curriculum.is_fallback', true)
                ->where('curriculum.published_world_count', 0)
                ->where('curriculum.world_count', 12)
                ->has('learningWorlds', 12)
                ->has('featuredWorlds', 4);
        });
    }

    public function test_homepage_does_not_expose_private_child_data(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('CB-LEARN-1001');
        $response->assertDontSee('CB-LEARN-1002');
        $response->assertDontSee('Amara Stone');
        $response->assertDontSee('Noah Stone');
        $response->assertDontSee('learner_id');
    }

    public function test_landing_assets_exist_on_disk_and_are_referenced(): void
    {
        $assets = [
            'CodeSprout-Every-Skill-Adventure',
            'CodeSprout-Mouse-Adventure',
            'CodeSprout-Keyboard-Island',
            'CodeSprout-Typing-Jungle',
            'CodeSprout-HTML-Builder-Bay',
        ];

        foreach ($assets as $asset) {
            $this->assertFileExists(public_path("assets/codesprout/original/{$asset}.png"));
            $this->assertFileExists(public_path("assets/codesprout/webp/{$asset}.webp"));
            $this->assertFileExists(public_path("assets/codesprout/avif/{$asset}.avif"));
        }

        $this->get('/')->assertInertia(function (AssertableInertia $page): void {
            $page->where('heroAsset.png', asset('assets/codesprout/original/CodeSprout-Every-Skill-Adventure.png'))
                ->where('heroAsset.webp', asset('assets/codesprout/webp/CodeSprout-Every-Skill-Adventure.webp'))
                ->where('heroAsset.avif', asset('assets/codesprout/avif/CodeSprout-Every-Skill-Adventure.avif'))
                ->where('featuredWorlds.0.image.png', asset('assets/codesprout/original/CodeSprout-Mouse-Adventure.png'))
                ->where('featuredWorlds.1.image.png', asset('assets/codesprout/original/CodeSprout-Keyboard-Island.png'))
                ->where('featuredWorlds.2.image.png', asset('assets/codesprout/original/CodeSprout-Typing-Jungle.png'))
                ->where('featuredWorlds.3.image.png', asset('assets/codesprout/original/CodeSprout-HTML-Builder-Bay.png'));
        });
    }

    public function test_privacy_and_terms_pages_are_accessible(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $page->component('privacy');
            });

        $this->get('/terms')
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $page->component('terms');
            });
    }
}
