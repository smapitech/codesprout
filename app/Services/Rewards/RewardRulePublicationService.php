<?php

namespace App\Services\Rewards;

use App\Enums\ContentStatus;
use App\Enums\RewardRepeatPolicy;
use App\Enums\RewardType;
use App\Models\AuditLog;
use App\Models\RewardRule;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardRulePublicationService
{
    private const ALLOWED_CONDITION_KEYS = [
        'minimum_accuracy',
        'maximum_hints',
        'source_slug',
        'skill_slug',
        'first_completion',
        'streak_days',
        'teacher_recognition_slug',
        'requires_completion_status',
    ];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createDraft(array $attributes, User $actor): RewardRule
    {
        $this->validate($attributes);

        return RewardRule::query()->create(array_merge($attributes, [
            'status' => ContentStatus::Draft,
            'version' => 1,
            'created_by' => $actor->getKey(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateOrVersion(RewardRule $rule, array $attributes, User $actor): RewardRule
    {
        $this->validate($attributes);

        if ($rule->status === ContentStatus::Published) {
            return DB::transaction(function () use ($rule, $attributes, $actor): RewardRule {
                $replacement = RewardRule::query()->create(array_merge(
                    Arr::except($rule->toArray(), ['id', 'uuid', 'created_at', 'updated_at', 'published_at', 'published_by', 'archived_at']),
                    $attributes,
                    [
                        'status' => ContentStatus::Draft,
                        'version' => $rule->version + 1,
                        'created_by' => $actor->getKey(),
                    ],
                ));

                $this->audit('reward.rule.version_created', $replacement, $actor, ['previous_rule_id' => $rule->getKey()]);

                return $replacement;
            });
        }

        $rule->fill($attributes);
        $rule->save();

        return $rule->fresh();
    }

    public function publish(RewardRule $rule, User $actor): RewardRule
    {
        $this->validate($rule->toArray());

        $rule->forceFill([
            'status' => ContentStatus::Published,
            'published_at' => now(),
            'published_by' => $actor->getKey(),
        ])->save();

        $this->audit('reward.rule.published', $rule, $actor);

        return $rule->fresh();
    }

    public function archive(RewardRule $rule, User $actor): RewardRule
    {
        $rule->forceFill([
            'status' => ContentStatus::Archived,
            'archived_at' => now(),
        ])->save();

        $this->audit('reward.rule.archived', $rule, $actor);

        return $rule->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function validate(array $attributes): void
    {
        $rewardType = $attributes['reward_type'] instanceof RewardType
            ? $attributes['reward_type']
            : RewardType::tryFrom((string) ($attributes['reward_type'] ?? ''));

        if (! $rewardType) {
            throw ValidationException::withMessages(['reward_type' => 'Choose a supported reward type.']);
        }

        $repeatPolicy = $attributes['repeat_policy'] instanceof RewardRepeatPolicy
            ? $attributes['repeat_policy']
            : RewardRepeatPolicy::tryFrom((string) ($attributes['repeat_policy'] ?? RewardRepeatPolicy::OncePerSource->value));

        if (! $repeatPolicy) {
            throw ValidationException::withMessages(['repeat_policy' => 'Choose a supported repeat policy.']);
        }

        if ((int) ($attributes['reward_amount'] ?? 0) < 0 || (int) ($attributes['reward_amount'] ?? 0) > 5000) {
            throw ValidationException::withMessages(['reward_amount' => 'Rewards must be a safe non-negative amount.']);
        }

        $conditions = $attributes['eligibility_conditions'] ?? [];
        if (! is_array($conditions)) {
            throw ValidationException::withMessages(['eligibility_conditions' => 'Reward conditions must be declarative data.']);
        }

        $unsafeNeedles = ['<script', 'javascript:', 'eval(', 'new function', 'select ', 'insert ', 'update ', 'delete ', 'drop ', 'alter ', 'onerror=', 'onclick='];
        foreach ($conditions as $key => $value) {
            if (! in_array($key, self::ALLOWED_CONDITION_KEYS, true)) {
                throw ValidationException::withMessages(['eligibility_conditions' => "Unsupported condition field: {$key}."]);
            }

            $encoded = strtolower((string) json_encode($value));
            foreach ($unsafeNeedles as $needle) {
                if (str_contains($encoded, $needle)) {
                    throw ValidationException::withMessages(['eligibility_conditions' => 'Executable or unsafe reward conditions are not allowed.']);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(string $action, RewardRule $rule, User $actor, array $metadata = []): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $actor->getKey(),
            'action' => $action,
            'subject_type' => RewardRule::class,
            'subject_id' => $rule->getKey(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
