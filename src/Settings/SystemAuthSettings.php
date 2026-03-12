<?php

namespace WireNinja\Prasmanan\Settings;

use Illuminate\Support\Facades\Cache;
use Spatie\LaravelSettings\Settings;

class SystemAuthSettings extends Settings
{
    public array $login_split_images;

    public bool $login_split_slider_enabled;

    public int $login_split_slider_interval;

    public bool $allow_form_base_credential = true;

    public bool $allow_google_auth = true;

    public bool $allow_webauth = true;

    public static function group(): string
    {
        return 'system_auth_settings';
    }

    public function getCustomCacheKey(string $key): string
    {
        return 'system_auth_settings::' . $key;
    }

    public function getCustomCache(string $key): string
    {
        $key = $this->getCustomCacheKey($key);

        return Cache::flexible($key, [10, 60], function () use ($key) {
            // return self::get($key);
        });
    }

    public function clearCustomCache(string $key): void
    {
        Cache::forget(self::getCustomCacheKey($key));
    }
}
