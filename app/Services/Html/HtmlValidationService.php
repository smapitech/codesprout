<?php

namespace App\Services\Html;

use App\Enums\HtmlValidationStatus;
use App\Models\HtmlExerciseVersion;
use App\Models\HtmlValidationResult;
use App\Models\ProjectRevision;
use Illuminate\Support\Str;

class HtmlValidationService
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function validateSource(string $source, HtmlExerciseVersion $version, ?ProjectRevision $revision = null): array
    {
        $version->loadMissing(['tagPolicy', 'requirements']);
        $sanitised = $this->sanitizer->sanitise($source, $version->tagPolicy);
        $requirements = $version->requirements;
        $results = [];
        $satisfied = 0;

        foreach ($requirements as $requirement) {
            $outcome = $this->evaluateRequirement($requirement->toArray(), $sanitised['structure'], $sanitised['sanitised_html']);
            $results[] = [
                'requirement_id' => $requirement->id,
                'rule_identifier' => $requirement->requirement_type.':'.($requirement->tag_name ?? $requirement->attribute_name ?? $requirement->id),
                'outcome' => $outcome ? 'passed' : 'needs_changes',
                'guidance_code' => $outcome ? null : $this->guidanceCode($requirement->toArray()),
                'evidence' => ['tag' => $requirement->tag_name, 'attribute' => $requirement->attribute_name],
            ];
            $satisfied += $outcome ? 1 : 0;
        }

        $unsafe = count($sanitised['issues']);
        $status = $unsafe > 0
            ? HtmlValidationStatus::Unsafe
            : ($satisfied >= $requirements->where('required', true)->count() ? HtmlValidationStatus::Valid : HtmlValidationStatus::NeedsChanges);

        return [
            'status' => $status,
            'sanitised_html' => $sanitised['sanitised_html'],
            'structure' => $sanitised['structure'],
            'issues' => $sanitised['issues'],
            'required_rule_count' => $requirements->where('required', true)->count(),
            'satisfied_rule_count' => $satisfied,
            'unsafe_item_count' => $unsafe,
            'syntax_issue_count' => 0,
            'structure_issue_count' => $requirements->count() - $satisfied,
            'accessibility_issue_count' => collect($sanitised['issues'])->where('code', 'missing_alt')->count(),
            'result_checksum' => hash('sha256', json_encode([$sanitised['checksum'], $results], JSON_THROW_ON_ERROR)),
            'requirement_results' => $results,
            'summary' => [
                'tags_used' => $sanitised['structure']['tag_counts'] ?? [],
                'guidance' => app(HtmlGuidanceService::class)->messagesFor($results, $sanitised['issues']),
            ],
        ];
    }

    public function persistAttemptResult($attempt, array $validation): HtmlValidationResult
    {
        $result = HtmlValidationResult::query()->create([
            'html_attempt_id' => $attempt->id,
            'validity_status' => $validation['status'],
            'required_rule_count' => $validation['required_rule_count'],
            'satisfied_rule_count' => $validation['satisfied_rule_count'],
            'unsafe_item_count' => $validation['unsafe_item_count'],
            'syntax_issue_count' => $validation['syntax_issue_count'],
            'structure_issue_count' => $validation['structure_issue_count'],
            'accessibility_issue_count' => $validation['accessibility_issue_count'],
            'assistance_summary' => ['count' => $attempt->assistance_count],
            'result_summary' => $validation['summary'],
            'result_checksum' => $validation['result_checksum'],
            'completed_at' => now(),
        ]);

        foreach ($validation['requirement_results'] as $index => $row) {
            $result->requirementResults()->create([
                'html_exercise_requirement_id' => $row['requirement_id'],
                'rule_identifier' => $row['rule_identifier'],
                'outcome' => $row['outcome'],
                'evidence_summary' => $row['evidence'],
                'safe_guidance_code' => $row['guidance_code'],
                'display_order' => $index + 1,
            ]);
        }

        return $result;
    }

    private function evaluateRequirement(array $requirement, array $structure, string $html): bool
    {
        $counts = $structure['tag_counts'] ?? [];
        $tag = $requirement['tag_name'] ? Str::lower($requirement['tag_name']) : null;

        return match ($requirement['requirement_type']) {
            'tag_exists' => ($counts[$tag] ?? 0) >= (int) ($requirement['minimum_count'] ?? 1),
            'attribute_exists' => $tag && $requirement['attribute_name'] ? preg_match('/<'.$tag.'\b[^>]*\s'.preg_quote($requirement['attribute_name'], '/').'\s*=/i', $html) === 1 : false,
            'text_contains' => $requirement['expected_value'] ? str_contains(Str::lower(strip_tags($html)), Str::lower((string) $requirement['expected_value'])) : true,
            'safe_link' => preg_match('/<a\b[^>]*href=/i', $html) === 1,
            'alt_text' => preg_match('/<img\b[^>]*alt=("|\')[^"\']+\1/i', $html) === 1,
            default => true,
        };
    }

    private function guidanceCode(array $requirement): string
    {
        return match ($requirement['requirement_type']) {
            'tag_exists' => 'missing_'.$requirement['tag_name'],
            'attribute_exists' => 'missing_attribute_'.$requirement['attribute_name'],
            'safe_link' => 'safe_link_needed',
            'alt_text' => 'image_alt_needed',
            default => 'small_correction_needed',
        };
    }
}
