<?php

namespace WireNinja\Prasmanan\Settings;

use WireNinja\Prasmanan\Concerns\HasCustomCache;
use Spatie\LaravelSettings\Settings;

class SystemAuthSettings extends Settings
{
    use HasCustomCache;

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
}
