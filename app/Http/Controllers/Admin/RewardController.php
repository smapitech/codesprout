<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BadgeDefinitionRequest;
use App\Http\Requests\RewardRuleRequest;
use App\Models\AuditLog;
use App\Models\BadgeDefinition;
use App\Models\LearnerLevel;
use App\Models\RewardRule;
use App\Services\Rewards\ProgressReportService;
use App\Services\Rewards\RewardRulePublicationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RewardController extends Controller
{
    public function index(ProgressReportService $reports): Response
    {
        $this->authorize('viewAny', RewardRule::class);

        return Inertia::render('admin/rewards/index', [
            'summary' => $reports->administratorSummary(),
            'rules' => RewardRule::query()
                ->with('badge')
                ->latest()
                ->paginate(12)
                ->through(fn (RewardRule $rule): array => [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'slug' => $rule->slug,
                    'event_type' => $rule->event_type,
                    'reward_type' => $rule->reward_type->value,
                    'reward_amount' => $rule->reward_amount,
                    'repeat_policy' => $rule->repeat_policy->value,
                    'status' => $rule->status->value,
                    'version' => $rule->version,
                    'badge' => $rule->badge?->name,
                ]),
            'badges' => BadgeDefinition::query()
                ->orderBy('display_order')
                ->get()
                ->map(fn (BadgeDefinition $badge): array => [
                    'id' => $badge->id,
                    'name' => $badge->name,
                    'slug' => $badge->slug,
                    'category' => $badge->badge_category->value,
                    'description' => $badge->short_description,
                    'status' => $badge->status->value,
                    'awards_count' => $badge->awards()->count(),
                ]),
            'levels' => LearnerLevel::query()
                ->orderBy('level_number')
                ->get()
                ->map(fn (LearnerLevel $level): array => [
                    'id' => $level->id,
                    'name' => $level->name,
                    'level_number' => $level->level_number,
                    'xp_threshold' => $level->xp_threshold,
                    'status' => $level->status->value,
                ]),
        ]);
    }

    public function storeRule(RewardRuleRequest $request, RewardRulePublicationService $service): RedirectResponse
    {
        $service->createDraft($request->validated(), $request->user());

        return back()->with('status', 'Reward rule draft created.');
    }

    public function updateRule(RewardRuleRequest $request, RewardRule $rewardRule, RewardRulePublicationService $service): RedirectResponse
    {
        $this->authorize('update', $rewardRule);
        $service->updateOrVersion($rewardRule, $request->validated(), $request->user());

        return back()->with('status', 'Reward rule saved.');
    }

    public function publishRule(RewardRule $rewardRule, RewardRulePublicationService $service): RedirectResponse
    {
        $this->authorize('publish', $rewardRule);
        $service->publish($rewardRule, request()->user());

        return back()->with('status', 'Reward rule published.');
    }

    public function archiveRule(RewardRule $rewardRule, RewardRulePublicationService $service): RedirectResponse
    {
        $this->authorize('update', $rewardRule);
        $service->archive($rewardRule, request()->user());

        return back()->with('status', 'Reward rule archived.');
    }

    public function storeBadge(BadgeDefinitionRequest $request): RedirectResponse
    {
        $this->authorize('create', BadgeDefinition::class);
        BadgeDefinition::query()->create(array_merge($request->validated(), [
            'status' => ContentStatus::Draft,
            'created_by' => $request->user()->getKey(),
            'updated_by' => $request->user()->getKey(),
        ]));

        return back()->with('status', 'Badge draft created.');
    }

    public function publishBadge(BadgeDefinition $badgeDefinition): RedirectResponse
    {
        $this->authorize('publish', $badgeDefinition);
        $badgeDefinition->forceFill([
            'status' => ContentStatus::Published,
            'published_at' => now(),
            'updated_by' => request()->user()->getKey(),
        ])->save();

        AuditLog::query()->create([
            'actor_user_id' => request()->user()->getKey(),
            'action' => 'badge.published',
            'subject_type' => BadgeDefinition::class,
            'subject_id' => $badgeDefinition->getKey(),
            'metadata' => ['badge' => $badgeDefinition->name],
            'created_at' => now(),
        ]);

        return back()->with('status', 'Badge published.');
    }
}
