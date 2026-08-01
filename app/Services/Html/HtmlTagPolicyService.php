<?php

namespace App\Services\Html;

use App\Enums\ContentStatus;
use App\Models\HtmlTagPolicy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HtmlTagPolicyService
{
    private const BLOCKED_TAGS = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'meta', 'link', 'base', 'svg', 'canvas'];

    private const BLOCKED_ATTRIBUTES = ['onclick', 'onerror', 'onload', 'srcdoc', 'style', 'download', 'ping', 'autofocus', 'contenteditable', 'formaction'];

    public function __construct(private readonly HtmlAuditService $audit) {}

    public function createPublished(array $data, User $actor): HtmlTagPolicy
    {
        $this->validate($data);

        return DB::transaction(function () use ($data, $actor): HtmlTagPolicy {
            $policy = HtmlTagPolicy::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'version' => $data['version'] ?? 1,
                'allowed_tags' => array_values($data['allowed_tags']),
                'allowed_attributes' => $data['allowed_attributes'],
                'allowed_protocols' => array_values($data['allowed_protocols'] ?? ['https', 'mailto']),
                'resource_limits' => $data['resource_limits'] ?? $this->defaultLimits(),
                'status' => ContentStatus::Published,
                'published_at' => now(),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'checksum' => $this->checksum($data),
            ]);

            $this->audit->record('html.tag_policy.published', $policy, $actor);

            return $policy;
        });
    }

    public function validate(array $data): void
    {
        $tags = array_map(fn ($tag): string => Str::lower((string) $tag), $data['allowed_tags'] ?? []);
        if ($tags === []) {
            throw ValidationException::withMessages(['allowed_tags' => 'Choose at least one safe HTML tag.']);
        }

        foreach ($tags as $tag) {
            if (! preg_match('/^[a-z][a-z0-9]*$/', $tag) || in_array($tag, self::BLOCKED_TAGS, true)) {
                throw ValidationException::withMessages(['allowed_tags' => 'This HTML tag is not available in CodeSprout lessons.']);
            }
        }

        foreach (($data['allowed_attributes'] ?? []) as $tag => $attributes) {
            if ($tag !== '*' && ! in_array(Str::lower((string) $tag), $tags, true)) {
                throw ValidationException::withMessages(['allowed_attributes' => 'Attributes can only be configured for allowed tags.']);
            }

            foreach ((array) $attributes as $attribute) {
                $attribute = Str::lower((string) $attribute);
                if (! preg_match('/^[a-z][a-z0-9_-]*$/', $attribute) || str_starts_with($attribute, 'on') || in_array($attribute, self::BLOCKED_ATTRIBUTES, true)) {
                    throw ValidationException::withMessages(['allowed_attributes' => 'Unsafe HTML attributes are not allowed.']);
                }
            }
        }

        foreach (($data['allowed_protocols'] ?? []) as $protocol) {
            if (! in_array(Str::lower((string) $protocol), ['https', 'http', 'mailto'], true)) {
                throw ValidationException::withMessages(['allowed_protocols' => 'Only approved safe link protocols may be used.']);
            }
        }
    }

    public function defaultLimits(): array
    {
        return [
            'max_source_length' => 8000,
            'max_nodes' => 120,
            'max_depth' => 12,
            'max_attributes_per_element' => 6,
            'max_attribute_length' => 500,
            'max_text_length' => 1200,
            'max_images' => 4,
            'max_links' => 6,
            'max_list_items' => 20,
        ];
    }

    private function checksum(array $data): string
    {
        return hash('sha256', json_encode([
            $data['allowed_tags'] ?? [],
            $data['allowed_attributes'] ?? [],
            $data['allowed_protocols'] ?? [],
            $data['resource_limits'] ?? $this->defaultLimits(),
        ], JSON_THROW_ON_ERROR));
    }
}
