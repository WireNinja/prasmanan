<?php

namespace WireNinja\Prasmanan\Settings;

use WireNinja\Prasmanan\Concerns\HasCustomCache;
use Spatie\LaravelSettings\Settings;

class SystemAppSettings extends Settings
{
    use HasCustomCache;

    public string $brand_name = 'Prasmanan';

    public ?string $brand_logo = null;

    public bool $is_dark_mode_enabled = true;

    public string $custom_font = 'IBM Plex Sans';

    public string $sidebar_width = '350px';

    public bool $is_sidebar_collapsible_on_desktop = true;

    public bool $are_navigation_groups_collapsible = true;

    public static function group(): string
    {
        return 'system_app_settings';
    }

    public function getCachedBrandName(): string
    {
        return (string) $this->getCustomCache('brand_name');
    }

    public function getCachedBrandLogo(): ?string
    {
        return $this->getCustomCache('brand_logo');
    }

    public function isCachedDarkModeEnabled(): bool
    {
        return (bool) $this->getCustomCache('is_dark_mode_enabled');
    }

    public function getCachedCustomFont(): string
    {
        return (string) $this->getCustomCache('custom_font');
    }

    public function getCachedSidebarWidth(): string
    {
        return (string) $this->getCustomCache('sidebar_width');
    }

    public function isCachedSidebarCollapsibleOnDesktop(): bool
    {
        return (bool) $this->getCustomCache('is_sidebar_collapsible_on_desktop');
    }

    public function areCachedNavigationGroupsCollapsible(): bool
    {
        return (bool) $this->getCustomCache('are_navigation_groups_collapsible');
    }
}
