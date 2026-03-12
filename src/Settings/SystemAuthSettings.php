<?php

namespace WireNinja\Prasmanan\Settings;

use Spatie\LaravelSettings\Settings;

class SystemAuthSettings extends Settings
{
    public array $login_split_images;

    public bool $login_split_slider_enabled;

    public int $login_split_slider_interval;
    
    public bool $allow_form_base_credential;

    public bool $allow_google_auth;

    public bool $allow_webauth;

    public static function group(): string
    {
        return 'system_auth_settings';
    }
}
