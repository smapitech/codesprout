<?php

namespace App\Services\Curriculum;

use Illuminate\Support\Str;

class CurriculumSlugService
{
    /**
     * @param  callable(string): bool  $exists
     */
    public function generate(string $title, callable $exists): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 2;

        while ($exists($slug)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
