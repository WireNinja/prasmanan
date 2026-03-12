<?php

namespace WireNinja\Prasmanan\Settings;

use WireNinja\Prasmanan\Concerns\HasCustomCache;
use Spatie\LaravelSettings\Settings;

class SystemAppSettings extends Settings
{
    use HasCustomCache;

    public string $brand_name;

    public ?string $brand_logo;

    public bool $is_dark_mode_enabled;

    public ?string $custom_font;

    public string $sidebar_width;

    public bool $is_sidebar_collapsible_on_desktop;

    public bool $are_navigation_groups_collapsible;

    public static function group(): string
    {
        return 'system_app_settings';
    }

    /**
     * Set default values in the constructor for safety.
     */
    public function __construct()
    {
        $this->brand_name = 'Prasmanan';
        $this->brand_logo = null;
        $this->is_dark_mode_enabled = true;
        $this->custom_font = 'Inter';
        $this->sidebar_width = '16rem';
        $this->is_sidebar_collapsible_on_desktop = true;
        $this->are_navigation_groups_collapsible = true;
    }
}
