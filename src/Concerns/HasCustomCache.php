<?php

namespace WireNinja\Prasmanan\Concerns;

use Illuminate\Support\Facades\Cache;

trait HasCustomCache
{
    /**
     * Get the cache key for a specific setting key.
     */
    public function getCustomCacheKey(string $key): string
    {
        return static::group() . '::' . $key;
    }

    public function getCustomCache(string $key): mixed
    {
        try {
            $cacheKey = $this->getCustomCacheKey($key);

            return Cache::flexible($cacheKey, [10, 60], function () use ($key) {
                return $this->{$key};
            });
        } catch (\Throwable) {
            return $this->{$key} ?? null;
        }
    }

    /**
     * Clear the custom cache for a specific key.
     */
    public function clearCustomCache(string $key): void
    {
        Cache::forget($this->getCustomCacheKey($key));
    }

    /**
     * Clear all custom caches for this settings class.
     */
    public function clearAllCustomCaches(): void
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $this->clearCustomCache($property->getName());
        }
    }
}
