<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarUrl
{
    public static function resolve(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, '/')) {
            return url($path);
        }

        if (Str::startsWith($path, ['assets/', 'images/', 'media/'])) {
            return asset($path);
        }

        return Storage::disk(config('filesystems.default'))->url($path);
    }
}
