<?php

namespace App\Http\Controllers\Child;

use App\Http\Controllers\Controller;
use App\Services\Rewards\ProgressReportService;
use Inertia\Inertia;
use Inertia\Response;

class RewardController extends Controller
{
    public function index(ProgressReportService $reports): Response
    {
        $child = request()->user();
        abort_unless($child, 403);

        return Inertia::render('child/rewards/index', array_merge(
            ['child' => ['name' => $child->name, 'avatar_url' => $child->avatar_url]],
            $reports->childSummary($child),
        ));
    }
}
