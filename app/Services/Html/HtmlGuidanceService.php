<?php

namespace App\Services\Html;

class HtmlGuidanceService
{
    public function messagesFor(array $requirementResults, array $issues): array
    {
        $messages = [];

        foreach ($issues as $issue) {
            $messages[] = $issue['message'] ?? 'Your webpage contains an item we cannot preview safely.';
        }

        foreach ($requirementResults as $result) {
            if (($result['outcome'] ?? '') === 'passed') {
                continue;
            }

            $messages[] = match ($result['guidance_code']) {
                'missing_h1' => 'Your webpage needs a main heading.',
                'missing_p' => 'Add paragraph text between <p> and </p>.',
                'missing_ul' => 'Add a list to organise your ideas.',
                'missing_li' => 'Place each <li> inside a list.',
                'missing_attribute_href' => 'Quotation marks should go around the link address.',
                'image_alt_needed' => 'Your image needs alt text so everyone can understand it.',
                'safe_link_needed' => 'Use a safe approved link for this lesson.',
                default => 'Try one small correction.',
            };
        }

        return array_values(array_unique(array_slice($messages ?: ['Your page is ready to preview.'], 0, 3)));
    }
}
