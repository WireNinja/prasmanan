<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('system_app_settings.brand_name', 'Prasmanan');
        $this->migrator->add('system_app_settings.brand_logo', null);
        $this->migrator->add('system_app_settings.is_dark_mode_enabled', true);
        $this->migrator->add('system_app_settings.custom_font', 'IBM Plex Sans');
        $this->migrator->add('system_app_settings.sidebar_width', '350px');
        $this->migrator->add('system_app_settings.is_sidebar_collapsible_on_desktop', true);
        $this->migrator->add('system_app_settings.are_navigation_groups_collapsible', true);
    }
};
