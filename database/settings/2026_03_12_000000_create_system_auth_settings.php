<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('system_auth_settings.login_split_images', [
            ['image_path' => 'https://picsum.photos/1080/1920?random=1'],
            ['image_path' => 'https://picsum.photos/1080/1920?random=2'],
        ]);
        $this->migrator->add('system_auth_settings.login_split_slider_enabled', true);
        $this->migrator->add('system_auth_settings.login_split_slider_interval', 5000);
        $this->migrator->add('system_auth_settings.allow_form_base_credential', true);
        $this->migrator->add('system_auth_settings.allow_google_auth', true);
        $this->migrator->add('system_auth_settings.allow_webauth', true);
    }
};
