<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ParentChildRelationship;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChildProfileController extends Controller
{
    public function show(User $child): Response
    {
        $child->loadMissing(['childProfile', 'profile', 'enrolledClasses.cohort']);

        abort_unless($child->childProfile, 404);

        Gate::authorize('view', $child->childProfile);

        $parent = request()->user();
        abort_unless($parent, 403);

        $relationship = ParentChildRelationship::query()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->firstOrFail();

        Gate::authorize('view', $relationship);

        $primaryClass = $child->enrolledClasses
            ->sortByDesc(static fn ($class) => (int) ($class->pivot->is_primary_class ?? false))
            ->first();

        return Inertia::render('parent/child-profile', [
            'child' => [
                'id' => $child->id,
                'name' => $child->name,
                'learner_id' => $child->childProfile->learner_id,
                'avatar_url' => $child->avatar_url,
                'current_world' => $primaryClass?->name ?? 'Computer Discovery',
                'class_count' => $child->enrolledClasses->count(),
            ],
            'relationship' => [
                'relationship_type' => $relationship->relationship_type,
                'is_primary_contact' => $relationship->is_primary_contact,
                'can_manage_pin' => $relationship->can_manage_pin,
                'can_view_progress' => $relationship->can_view_progress,
            ],
            'enrolledClasses' => $child->enrolledClasses->map(static fn ($class): array => [
                'id' => $class->id,
                'name' => $class->name,
                'code' => $class->class_code,
                'cohort' => $class->cohort?->name,
                'is_primary_class' => (bool) ($class->pivot->is_primary_class ?? false),
            ]),
        ]);
    }
}
