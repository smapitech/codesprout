<?php

namespace App\Http\Controllers;

use App\Models\ApplicationSetting;
use App\Models\Curriculum;
use App\Models\CurriculumWorld;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        $user = request()->user();
        $curriculum = Curriculum::query()
            ->published()
            ->with([
                'worlds' => fn ($query) => $query->published()->orderBy('display_order')->orderBy('world_number'),
            ])
            ->latest('published_at')
            ->first();

        $publishedWorlds = collect($curriculum?->worlds ?? []);
        $worldsBySlug = $publishedWorlds->keyBy('slug');
        $usesFallbackWorlds = $publishedWorlds->isEmpty();

        $learningWorlds = $usesFallbackWorlds
            ? $this->fallbackLearningWorlds()
            : $this->publishedLearningWorlds($publishedWorlds);

        return Inertia::render('welcome', [
            'page' => [
                'title' => 'CodeSprout | Game-Based Coding for Children Ages 6-7',
                'description' => 'CodeSprout helps young children learn mouse skills, typing, coding symbols, HTML, CSS and JavaScript through a safe one-year game-based adventure.',
                'canonical' => route('home'),
                'image' => asset('assets/codesprout/original/CodeSprout-Every-Skill-Adventure.png'),
            ],
            'links' => [
                'home' => route('home', absolute: false),
                'login' => $user ? route('dashboard', absolute: false) : route('login', absolute: false),
                'childLogin' => route('child.login', absolute: false),
                'startAdventure' => $user ? route('dashboard', absolute: false) : route('child.login', absolute: false),
                'dashboard' => $user ? route('dashboard', absolute: false) : null,
                'privacy' => route('privacy', absolute: false),
                'terms' => route('terms', absolute: false),
            ],
            'authState' => [
                'authenticated' => $user !== null,
                'role' => $user?->primary_role,
                'dashboard' => $user ? route('dashboard', absolute: false) : null,
            ],
            'supportEmail' => ApplicationSetting::query()
                ->where('key', 'support_email')
                ->where('is_public', true)
                ->value('value'),
            'curriculum' => [
                'title' => $curriculum?->title ?? 'CodeSprout One-Year Programme',
                'slug' => $curriculum?->slug,
                'is_fallback' => $usesFallbackWorlds,
                'world_count' => $learningWorlds->count(),
                'published_world_count' => $publishedWorlds->count(),
            ],
            'heroAsset' => $this->imageAsset(
                name: 'CodeSprout-Every-Skill-Adventure',
                alt: 'Children and a friendly robot following a learning path from mouse and keyboard skills to coding and webpage creation.',
                width: 1448,
                height: 1086,
                fit: 'cover',
                priority: true,
            ),
            'introAsset' => $this->imageAsset(
                name: 'CodeSprout-Every-Skill-Adventure',
                alt: 'Children and a friendly robot following a learning path from mouse and keyboard skills to coding and webpage creation.',
                width: 1448,
                height: 1086,
                fit: 'cover',
            ),
            'featuredWorlds' => $this->featuredWorlds($worldsBySlug),
            'learningWorlds' => $learningWorlds->all(),
        ]);
    }

    /**
     * @param  Collection<int, CurriculumWorld>  $worlds
     * @return Collection<int, array<string, mixed>>
     */
    private function publishedLearningWorlds(Collection $worlds): Collection
    {
        return $worlds->values()->map(static function (CurriculumWorld $world): array {
            return [
                'number' => $world->world_number,
                'slug' => $world->slug,
                'title' => $world->name,
                'shortDescription' => $world->short_description,
                'themeColour' => $world->theme_colour,
                'accentColour' => $world->accent_colour,
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fallbackLearningWorlds(): Collection
    {
        $palette = $this->worldPalette();

        return collect(config('codesprout.curriculum.worlds', []))
            ->values()
            ->map(function (string $title, int $index) use ($palette): array {
                $number = $index + 1;
                $colourPair = $palette[$number] ?? ['theme' => '#138a72', 'accent' => '#f7c948'];

                return [
                    'number' => $number,
                    'slug' => Str::slug($title),
                    'title' => $title,
                    'shortDescription' => null,
                    'themeColour' => $colourPair['theme'],
                    'accentColour' => $colourPair['accent'],
                ];
            });
    }

    /**
     * @param  Collection<string, CurriculumWorld>  $worldsBySlug
     * @return array<int, array<string, mixed>>
     */
    private function featuredWorlds(Collection $worldsBySlug): array
    {
        $blueprints = [
            [
                'number' => 2,
                'slug' => 'mouse-adventure',
                'title' => 'Mouse Adventure',
                'description' => 'Build confidence through pointing, clicking, dragging, scrolling and playful cursor challenges.',
                'skills' => ['Single click', 'Double click', 'Drag and drop', 'Scrolling', 'Pointer accuracy'],
                'theme' => '#18a7b8',
                'accent' => '#f57b5d',
                'image' => 'CodeSprout-Mouse-Adventure',
                'alt' => 'Child learning mouse control through clicking, dragging and scrolling challenges in a playful maze.',
            ],
            [
                'number' => 3,
                'slug' => 'keyboard-island',
                'title' => 'Keyboard Island',
                'description' => 'Discover letters, numbers and important keyboard keys through exciting exploration missions.',
                'skills' => ['Letter keys', 'Enter', 'Shift', 'Caps Lock', 'Spacebar', 'Arrow keys'],
                'theme' => '#1fa7a0',
                'accent' => '#f2c14e',
                'image' => 'CodeSprout-Keyboard-Island',
                'alt' => 'Child and robot exploring Enter, Shift, Caps Lock, Spacebar and arrow keys on Keyboard Island.',
            ],
            [
                'number' => 4,
                'slug' => 'typing-jungle',
                'title' => 'Typing Jungle',
                'description' => 'Build accuracy and confidence by typing letters, familiar words, names and short sentences.',
                'skills' => ['Letter recognition', 'Word typing', 'Capital letters', 'Sentence typing', 'Accuracy before speed'],
                'theme' => '#69a84f',
                'accent' => '#ffd166',
                'image' => 'CodeSprout-Typing-Jungle',
                'alt' => 'Child practising letter typing with colourful letter balloons in Typing Jungle.',
            ],
            [
                'number' => 9,
                'slug' => 'html-builder-bay',
                'title' => 'HTML Builder Bay',
                'description' => 'Drag, complete and type real HTML tags to build colourful webpages.',
                'skills' => ['Opening tags', 'Closing tags', 'Heading tags', 'Paragraph tags', 'Completing missing code'],
                'theme' => '#ff7f50',
                'accent' => '#2d9bf0',
                'image' => 'CodeSprout-HTML-Builder-Bay',
                'alt' => 'Children arranging HTML heading and paragraph tags to build a webpage.',
            ],
        ];

        return array_map(function (array $blueprint) use ($worldsBySlug): array {
            $world = $worldsBySlug->get($blueprint['slug']);

            return [
                'number' => $blueprint['number'],
                'slug' => $blueprint['slug'],
                'title' => $blueprint['title'],
                'description' => $blueprint['description'],
                'skills' => $blueprint['skills'],
                'themeColour' => $world?->theme_colour ?? $blueprint['theme'],
                'accentColour' => $world?->accent_colour ?? $blueprint['accent'],
                'href' => '#learning-path',
                'image' => $this->imageAsset(
                    name: $blueprint['image'],
                    alt: $blueprint['alt'],
                    width: 1448,
                    height: 1086,
                    fit: 'cover',
                ),
            ];
        }, $blueprints);
    }

    /**
     * @return array<int, array{theme: string, accent: string}>
     */
    private function worldPalette(): array
    {
        return [
            1 => ['theme' => '#2fb37b', 'accent' => '#f7b53b'],
            2 => ['theme' => '#18a7b8', 'accent' => '#f57b5d'],
            3 => ['theme' => '#1fa7a0', 'accent' => '#f2c14e'],
            4 => ['theme' => '#69a84f', 'accent' => '#ffd166'],
            5 => ['theme' => '#ef476f', 'accent' => '#ffd166'],
            6 => ['theme' => '#7c5cff', 'accent' => '#ffb703'],
            7 => ['theme' => '#ff9f1c', 'accent' => '#2ec4b6'],
            8 => ['theme' => '#2d9bf0', 'accent' => '#7c5cff'],
            9 => ['theme' => '#ff7f50', 'accent' => '#2d9bf0'],
            10 => ['theme' => '#7b61ff', 'accent' => '#ff9f1c'],
            11 => ['theme' => '#00a8e8', 'accent' => '#ffd166'],
            12 => ['theme' => '#14b8a6', 'accent' => '#f97316'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function imageAsset(string $name, string $alt, int $width, int $height, string $fit = 'contain', bool $priority = false): array
    {
        return [
            'name' => $name,
            'alt' => $alt,
            'width' => $width,
            'height' => $height,
            'fit' => $fit,
            'priority' => $priority,
            'png' => asset("assets/codesprout/original/{$name}.png"),
            'webp' => asset("assets/codesprout/webp/{$name}.webp"),
            'avif' => asset("assets/codesprout/avif/{$name}.avif"),
        ];
    }
}
