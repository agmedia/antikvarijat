<?php

namespace App\Helpers;

class Asset
{
    /**
     * Return a public asset URL with an automatic cache-busting version.
     */
    public static function url(string $path): string
    {
        $assetPath = ltrim((string) parse_url($path, PHP_URL_PATH), '/');
        $absolutePath = public_path($assetPath);
        $version = is_file($absolutePath) ? filemtime($absolutePath) : 1;

        return asset($assetPath) . '?v=' . $version;
    }
}
