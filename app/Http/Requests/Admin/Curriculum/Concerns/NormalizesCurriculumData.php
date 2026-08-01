<?php

namespace App\Http\Requests\Admin\Curriculum\Concerns;

use Illuminate\Support\Str;

trait NormalizesCurriculumData
{
    /**
     * @return array<int, string>
     */
    protected function linesToArray(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function jsonToArray(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function normalizeTitle(string $value): string
    {
        return Str::of($value)->squish()->toString();
    }
}
