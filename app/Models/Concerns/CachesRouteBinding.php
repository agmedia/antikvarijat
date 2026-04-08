<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

trait CachesRouteBinding
{
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();
        $ttl = now()->addSeconds((int) config('cache.page_life', 86400));
        $cacheKey = implode('.', [
            'route_binding',
            str_replace('\\', '.', static::class),
            $field,
            sha1((string) $value),
        ]);

        $cache = Cache::store('file');

        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $model = $this->newQuery()->where($field, $value)->first();

        $cache->put($cacheKey, $model, $ttl);

        return $model;
    }
}
