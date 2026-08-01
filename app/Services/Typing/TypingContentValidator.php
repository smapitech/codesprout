<?php

namespace App\Services\Typing;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TypingContentValidator
{
    /**
     * @var array<int, string>
     */
    private array $allowedSpecialKeys = [
        'enter',
        'space',
        'spacebar',
        'backspace',
        'shift',
        'capslock',
        'tab',
        'escape',
        'arrowup',
        'arrowdown',
        'arrowleft',
        'arrowright',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function validateItems(array $items, bool $caseSensitive = false): void
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Add at least one typing prompt before publishing.']);
        }

        $seen = [];
        foreach ($items as $index => $item) {
            $prompt = trim((string) ($item['prompt_text'] ?? ''));
            $expected = (string) ($item['expected_text'] ?? '');

            if ($prompt === '' || $expected === '') {
                throw ValidationException::withMessages(["items.$index" => 'Typing prompts need child-facing text and an expected answer.']);
            }

            $this->rejectUnsafeText($prompt, "items.$index.prompt_text");
            $this->rejectUnsafeText($expected, "items.$index.expected_text");
            $this->rejectInvisibleControlCharacters($expected, "items.$index.expected_text");

            if (mb_strlen($expected) > 160) {
                throw ValidationException::withMessages(["items.$index.expected_text" => 'Typing prompts must stay short for young learners.']);
            }

            $duplicateKey = $caseSensitive ? $expected : mb_strtolower($expected);
            if (isset($seen[$duplicateKey])) {
                throw ValidationException::withMessages(["items.$index.expected_text" => 'Duplicate typing content should be removed or intentionally separated into another exercise.']);
            }
            $seen[$duplicateKey] = true;

            foreach ((array) ($item['target_keys'] ?? []) as $key) {
                $this->assertSupportedKey((string) $key, "items.$index.target_keys");
            }
        }
    }

    public function rejectUnsafeText(string $value, string $field = 'content'): void
    {
        $lower = Str::lower($value);

        foreach (['<script', 'javascript:', '<?php', 'eval(', 'new function', 'onerror=', 'onclick=', 'select * from', 'drop table'] as $needle) {
            if (str_contains($lower, $needle)) {
                throw ValidationException::withMessages([$field => 'Typing content must be plain, safe learning text.']);
            }
        }
    }

    public function assertSupportedKey(string $key, string $field = 'key'): void
    {
        $normalised = Str::lower(str_replace([' ', '_', '-'], '', trim($key)));

        if (preg_match('/^[a-z0-9]$/', $normalised) === 1) {
            return;
        }

        if (in_array($normalised, $this->allowedSpecialKeys, true)) {
            return;
        }

        if (in_array($key, ['.', ',', '?', '!', "'", '"', ':', ';', '-', '(', ')'], true)) {
            return;
        }

        throw ValidationException::withMessages([$field => 'This key is not yet supported by the typing engine.']);
    }

    private function rejectInvisibleControlCharacters(string $value, string $field): void
    {
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value) === 1) {
            throw ValidationException::withMessages([$field => 'Typing content cannot include hidden control characters.']);
        }
    }
}
