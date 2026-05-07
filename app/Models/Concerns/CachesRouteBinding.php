<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

trait CachesRouteBinding
{
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();
        $ttl = now()->addSeconds((int) config('cache.page_life', 86400));
        $cacheKey = static::routeBindingCacheKey($value, $field);

        $cache = Cache::store(config('cache.default'));

        if ($cache->has($cacheKey)) {
            $modelId = $cache->get($cacheKey);

            if ($modelId) {
                $model = $this->newQuery()->whereKey($modelId)->first();

                if ($model) {
                    return $model;
                }
            }

            $cache->forget($cacheKey);
        }

        $model = $this->newQuery()->where($field, $value)->first();

        if ($model) {
            $cache->put($cacheKey, $model->getKey(), $ttl);
        }

        return $model;
    }

    public static function forgetCachedRouteBinding($value, $field = null): void
    {
        if ($value === null || $value === '') {
            return;
        }

        Cache::store(config('cache.default'))->forget(static::routeBindingCacheKey($value, $field));
    }

    protected static function routeBindingCacheKey($value, $field = null): string
    {
        $instance = new static();

        return implode('.', [
            'route_binding',
            str_replace('\\', '.', static::class),
            $field ?: $instance->getRouteKeyName(),
            sha1((string) $value),
        ]);
    }
}
