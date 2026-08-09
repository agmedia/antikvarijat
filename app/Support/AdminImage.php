<?php

namespace App\Support;

class AdminImage
{
    /**
     * Resolve content images shown in the admin against APP_IMAGE_DOMAIN.
     * Absolute external URLs are preserved, while local /media paths are
     * always rebased to the configured image domain.
     */
    public static function url(?string $path, ?string $fallback = 'media/avatars/avatar0.jpg'): string
    {
        $source = trim((string) ($path ?: $fallback));

        if ($source === '' || str_starts_with($source, 'data:') || str_starts_with($source, 'blob:')) {
            return $source;
        }

        if (preg_match('/^https?:\/\//i', $source)) {
            $urlPath = parse_url($source, PHP_URL_PATH);

            if (! is_string($urlPath) || ! str_starts_with($urlPath, '/media/')) {
                return $source;
            }

            $source = ltrim($urlPath, '/');
        }

        $domain = rtrim((string) config('settings.images_domain'), '/');

        if ($domain === '') {
            return asset(ltrim($source, '/'));
        }

        return $domain . '/' . ltrim($source, '/');
    }
}
