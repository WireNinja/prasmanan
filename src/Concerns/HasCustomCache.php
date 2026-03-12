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

    /**
     * Get a setting value with flexible caching.
     */
    public function getCustomCache(string $key): mixed
    {
        $cacheKey = $this->getCustomCacheKey($key);

        return Cache::flexible($cacheKey, [10, 60], function () use ($key) {
            return $this->{$key};
        });
    }

    /**
     * Clear the custom cache for a specific key.
     */
    public function clearCustomCache(string $key): void
    {
        Cache::forget($this->getCustomCacheKey($key));
    }
    
    /**
     * Clear all custom caches for this settings group.
     * This is useful when the settings are updated.
     */
    public function clearAllCustomCaches(): void
    {
        // Note: This assumes we know all keys or we use a tag.
        // For simplicity, we might need a list of keys to clear.
        // Or just rely on the flexible cache TTL for now.
    }
}
