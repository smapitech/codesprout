<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Inertia\Inertia;
use Inertia\Response;

class SkillController extends Controller
{
    public function index(): Response
    {
        $skills = Skill::query()
            ->withCount(['lessons', 'stages'])
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(static fn (Skill $skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'slug' => $skill->slug,
                'category' => $skill->category,
                'description' => $skill->description,
                'mastery_description' => $skill->mastery_description,
                'lessons_count' => $skill->lessons_count,
                'stages_count' => $skill->stages_count,
                'status' => $skill->status instanceof \BackedEnum ? $skill->status->value : $skill->status,
            ]);

        return Inertia::render('admin/curriculum/skills', [
            'skills' => $skills,
        ]);
    }
}
