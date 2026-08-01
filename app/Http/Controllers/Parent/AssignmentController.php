<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Assignments\AssignmentReportService;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function index(AssignmentReportService $reportService): Response
    {
        $parent = request()->user();
        abort_unless($parent, 403);

        return Inertia::render('parent/assignments/index', [
            'children' => $parent->children()
                ->with(['childProfile', 'profile', 'enrolledClasses'])
                ->get()
                ->map(static fn ($child): array => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'learner_id' => $child->childProfile?->learner_id,
                    'avatar_url' => $child->avatar_url,
                    'world' => $child->enrolledClasses->first()?->name ?? 'Computer Discovery',
                ])
                ->values(),
            'assignments' => $reportService->parentAssignments($parent),
        ]);
    }
}
