<?php

namespace WireNinja\Prasmanan\Traits;

use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait HasCachedTableSummaries
{
    private static string $staticCachePrefix = 'cached_table_summaries:';

    /**
     * Clear the cached summaries for a given prefix by incrementing its version.
     */
    public static function clearCachedTableSummaries(string $cachePrefix): void
    {
        $versionKey = self::getCacheVersionKey($cachePrefix);
        Cache::increment($versionKey);
    }

    protected static function getCacheVersionKey(string $cachePrefix): string
    {
        return self::$staticCachePrefix.'version:'.$cachePrefix;
    }

    protected static function getCacheVersion(string $cachePrefix): int
    {
        return Cache::rememberForever(self::getCacheVersionKey($cachePrefix), fn () => 1);
    }

    protected static function makeCachedAverage(
        string $label,
        string $cachePrefix,
        string $column,
        int $ttlMinutes = 10,
    ): Average {
        return Average::make()
            ->label($label)
            ->using(function (Builder $query) use ($cachePrefix, $ttlMinutes, $column) {
                $version = self::getCacheVersion($cachePrefix);
                $key = self::$staticCachePrefix.$cachePrefix.'_v'.$version.'_'.md5($query->toSql().serialize($query->getBindings()));

                return Cache::remember($key, now()->addMinutes($ttlMinutes), fn () => $query->avg($column));
            });
    }

    protected static function makeCachedSum(
        string $label,
        string $cachePrefix,
        string $column,
        int $ttlMinutes = 10
    ): Sum {
        return Sum::make()
            ->label($label)
            ->using(function (Builder $query) use ($cachePrefix, $ttlMinutes, $column) {
                $version = self::getCacheVersion($cachePrefix);
                $key = self::$staticCachePrefix.$cachePrefix.'_v'.$version.'_'.md5($query->toSql().serialize($query->getBindings()));

                return Cache::remember($key, now()->addMinutes($ttlMinutes), fn () => $query->sum($column));
            });
    }
}
