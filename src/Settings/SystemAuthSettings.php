<?php

namespace WireNinja\Prasmanan\Settings;

use Spatie\LaravelSettings\Settings;

class SystemAuthSettings extends Settings
{
    public array $login_split_images;

    public bool $login_split_slider_enabled;

    public int $login_split_slider_interval;

    public static function group(): string
    {
        return 'system_auth_settings';
    }
}
