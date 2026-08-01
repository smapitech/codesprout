<?php

namespace App\Services\Html;

use App\Models\HtmlTagPolicy;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HtmlSanitizer
{
    public function sanitise(string $source, HtmlTagPolicy $policy): array
    {
        $limits = $policy->resource_limits ?? [];
        if (mb_strlen($source) > (int) ($limits['max_source_length'] ?? 8000)) {
            throw ValidationException::withMessages(['source_html' => 'This webpage is a little too large for this lesson.']);
        }

        $document = $this->document($source);
        $issues = [];
        $stats = ['nodes' => 0, 'images' => 0, 'links' => 0, 'max_depth' => 0];

        $body = $document->getElementsByTagName('body')->item(0);
        if ($body) {
            $this->cleanChildren($body, $policy, $issues, $stats, 0);
        }

        $html = '';
        foreach ($body?->childNodes ?? [] as $child) {
            $html .= $document->saveHTML($child);
        }

        return [
            'sanitised_html' => trim($html),
            'issues' => $issues,
            'structure' => $this->structure($document),
            'stats' => $stats,
            'checksum' => hash('sha256', trim($html)),
        ];
    }

    private function document(string $source): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<!DOCTYPE html><html><body>'.$source.'</body></html>');
        libxml_clear_errors();

        return $document;
    }

    private function cleanChildren(DOMNode $node, HtmlTagPolicy $policy, array &$issues, array &$stats, int $depth): void
    {
        $allowedTags = array_map('strtolower', $policy->allowed_tags ?? []);
        $limits = $policy->resource_limits ?? [];
        $stats['max_depth'] = max($stats['max_depth'], $depth);

        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = Str::lower($child->tagName);
                $stats['nodes']++;

                if (! in_array($tag, $allowedTags, true)) {
                    $issues[] = ['code' => 'unsupported_tag', 'tag' => $tag, 'message' => 'This tag is not available in this lesson.'];
                    $child->parentNode?->removeChild($child);

                    continue;
                }

                if ($stats['nodes'] > (int) ($limits['max_nodes'] ?? 120) || $depth > (int) ($limits['max_depth'] ?? 12)) {
                    $issues[] = ['code' => 'content_limit', 'message' => 'This webpage has more parts than this lesson allows.'];
                    $child->parentNode?->removeChild($child);

                    continue;
                }

                if ($tag === 'img') {
                    $stats['images']++;
                }
                if ($tag === 'a') {
                    $stats['links']++;
                }

                $this->cleanAttributes($child, $policy, $issues);
            }

            $this->cleanChildren($child, $policy, $issues, $stats, $depth + 1);
        }
    }

    private function cleanAttributes(DOMElement $element, HtmlTagPolicy $policy, array &$issues): void
    {
        $tag = Str::lower($element->tagName);
        $allowed = array_merge((array) ($policy->allowed_attributes['*'] ?? []), (array) ($policy->allowed_attributes[$tag] ?? []));

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = Str::lower($attribute->name);
            $value = html_entity_decode((string) $attribute->value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (! in_array($name, $allowed, true) || str_starts_with($name, 'on')) {
                $issues[] = ['code' => 'unsupported_attribute', 'attribute' => $name, 'message' => 'This attribute is not available in this lesson.'];
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! $this->safeUrl($value, $policy, $name)) {
                $issues[] = ['code' => 'unsafe_url', 'attribute' => $name, 'message' => 'This link or image is not on the safe list.'];
                $element->removeAttribute($attribute->name);

                continue;
            }

            if ($name === 'target') {
                $element->removeAttribute('target');
                $element->setAttribute('rel', 'noopener noreferrer');
            }
        }

        if ($tag === 'img' && trim($element->getAttribute('alt')) === '') {
            $issues[] = ['code' => 'missing_alt', 'tag' => 'img', 'message' => 'Your image needs a description.'];
        }
    }

    private function safeUrl(string $value, HtmlTagPolicy $policy, string $attribute): bool
    {
        $decoded = Str::lower(trim(rawurldecode(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        if ($decoded === '' || str_starts_with($decoded, '/assets/codesprout/')) {
            return true;
        }

        if ($attribute === 'href' && str_starts_with($decoded, '#')) {
            return true;
        }

        // Images are deny-by-default: only platform-approved local assets may load.
        // This prevents learner previews from making arbitrary remote requests.
        if ($attribute === 'src') {
            return false;
        }

        if (preg_match('/^([a-z0-9+.-]+):/i', $decoded, $matches) === 1) {
            return in_array(Str::lower($matches[1]), array_map('strtolower', $policy->allowed_protocols ?? []), true);
        }

        return false;
    }

    private function structure(DOMDocument $document): array
    {
        $body = $document->getElementsByTagName('body')->item(0);
        $tags = [];
        if ($body) {
            foreach ($body->getElementsByTagName('*') as $element) {
                $tags[] = Str::lower($element->tagName);
            }
        }

        return [
            'tags' => array_values($tags),
            'tag_counts' => array_count_values($tags),
        ];
    }
}
