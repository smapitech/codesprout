<?php

namespace App\Services\Html;

use App\Models\HtmlValidationResult;
use App\Models\User;

class HtmlAdaptationService
{
    public function recommendationFor(User $child): array
    {
        $recent = HtmlValidationResult::query()
            ->whereHas('attempt', fn ($query) => $query->where('child_id', $child->id))
            ->latest()
            ->limit(3)
            ->get();

        if ($recent->count() < 2) {
            return ['label' => 'Keep discovering tags', 'reason' => 'Not enough recent HTML evidence yet.'];
        }

        if ($recent->sum('unsafe_item_count') > 0) {
            return ['label' => 'Try a safe tag repair activity', 'reason' => 'Recent work included a tag that is not available in this lesson.'];
        }

        if ($recent->avg('satisfied_rule_count') >= $recent->avg('required_rule_count')) {
            return ['label' => 'Introduce one new published tag', 'reason' => 'Several recent activities were completed carefully.'];
        }

        return ['label' => 'Practise opening and closing tags', 'reason' => 'A short review can help the next webpage feel easier.'];
    }
}
