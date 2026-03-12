<?php

namespace WireNinja\Prasmanan\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use WireNinja\Prasmanan\Settings\SystemAuthSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use BackedEnum;
use UnitEnum;

class ManageAuthSettings extends SettingsPage
{
    protected static string $settings = SystemAuthSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-lock';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konfigurasi Slider Login')
                    ->description('Kelola perilaku dan gambar slider untuk tata letak split login.')
                    ->icon('lucide-image-play')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('login_split_slider_enabled')
                                    ->label('Aktifkan Slider')
                                    ->helperText('Aktifkan slider gambar untuk halaman login split.')
                                    ->reactive(),
                                TextInput::make('login_split_slider_interval')
                                    ->label('Interval Slider')
                                    ->numeric()
                                    ->step(500)
                                    ->suffix('ms')
                                    ->placeholder('5000')
                                    ->helperText('Kecepatan transisi slider (dalam milidetik).')
                                    ->visible(fn($get) => $get('login_split_slider_enabled'))
                                    ->columnSpan(2),
                            ]),

                        $this->imageRepeater()
                            ->hidden(fn($get) => !$get('login_split_slider_enabled')),
                    ])
                    ->collapsible(),

                Section::make('Metode Otentikasi')
                    ->description('Pilih metode otentikasi yang diizinkan bagi pengguna untuk masuk ke sistem.')
                    ->icon('lucide-shield-check')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Section::make('Kredensial Form')
                                    ->description('Login standar menggunakan email/username dan password.')
                                    ->icon('lucide-user-cog')
                                    ->schema([
                                        Toggle::make('allow_form_base_credential')
                                            ->label('Izinkan Form')
                                            ->helperText('Aktifkan jika ingin login tradisional.')
                                            ->required(),
                                    ])->columnSpan(1),

                                Section::make('Google OAuth')
                                    ->description('Login cepat menggunakan akun Google.')
                                    ->icon('lucide-mail')
                                    ->schema([
                                        Toggle::make('allow_google_auth')
                                            ->label('Izinkan Google')
                                            ->helperText('Gunakan integrasi Google Socialite.')
                                            ->required(),
                                    ])->columnSpan(1),

                                Section::make('WebAuthn / Passkey')
                                    ->description('Login modern menggunakan biometrik atau kunci keamanan.')
                                    ->icon('lucide-fingerprint')
                                    ->schema([
                                        Toggle::make('allow_webauth')
                                            ->label('Izinkan Passkey')
                                            ->helperText('Keamanan tingkat tinggi tanpa password.')
                                            ->required(),
                                    ])->columnSpan(1),
                            ]),
                    ]),
            ]);
    }

    protected function imageRepeater(): Repeater
    {
        return Repeater::make('login_split_images')
            ->label('Koleksi Gambar Split Layout')
            ->schema([
                FileUpload::make('image_path')
                    ->label('Unggah Gambar')
                    ->image()
                    ->disk('public')
                    ->directory('split-auth')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->minItems(1)
            ->columns(1)
            ->grid(3)
            ->itemLabel(fn(array $state): ?string => $state['image_path'] ?? 'Gambar Baru');
    }

    public static function getNavigationLabel(): string
    {
        return 'Pengaturan Otentikasi';
    }

    public function getTitle(): string
    {
        return 'Pengaturan Otentikasi';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getClearCacheAction(),
        ];
    }

    protected function getClearCacheAction(): Action
    {
        return Action::make('clearCache')
            ->label('Bersihkan Cache')
            ->color('warning')
            ->icon('lucide-trash-2')
            ->requiresConfirmation()
            ->action(fn () => $this->clearCache());
    }

    public function clearCache(): void
    {
        app(SystemAuthSettings::class)->clearAllCustomCaches();

        Notification::make()
            ->title('Cache Berhasil Dibersihkan')
            ->success()
            ->send();
    }
}
