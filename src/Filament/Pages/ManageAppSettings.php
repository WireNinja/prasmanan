<?php

namespace WireNinja\Prasmanan\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use WireNinja\Prasmanan\Settings\SystemAppSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use BackedEnum;
use UnitEnum;

class ManageAppSettings extends SettingsPage
{
    protected static string $settings = SystemAppSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'lucide-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Section::make('Identitas Visual')
                            ->description('Kustomisasi nama dan logo aplikasi Anda.')
                            ->icon('lucide-palette')
                            ->schema([
                                TextInput::make('brand_name')
                                    ->label('Nama Aplikasi / Brand')
                                    ->placeholder('Prasmanan')
                                    ->required()
                                    ->helperText('Nama ini akan muncul di sidebar dan judul halaman.'),
                                FileUpload::make('brand_logo')
                                    ->label('Logo Aplikasi')
                                    ->image()
                                    ->disk('public')
                                    ->directory('branding')
                                    ->helperText('Gunakan format PNG, JPG, atau WebP transparan.'),
                            ])->columnSpan(1),

                        Section::make('Tipografi & Tema')
                            ->description('Pilih font dan atur preferensi tampilan.')
                            ->icon('lucide-type')
                            ->schema([
                                Toggle::make('is_dark_mode_enabled')
                                    ->label('Aktifkan Tombol Mode Gelap')
                                    ->helperText('Izinkan pengguna beralih antara mode terang dan gelap.')
                                    ->default(true),
                                Select::make('custom_font')
                                    ->label('Koleksi Google Font')
                                    ->options([
                                        'IBM Plex Sans' => 'IBM Plex Sans (Standard)',
                                        'Inter' => 'Inter (Modern)',
                                        'Roboto' => 'Roboto (Clean)',
                                        'Open Sans' => 'Open Sans',
                                        'Montserrat' => 'Montserrat (Premium)',
                                        'Poppins' => 'Poppins (Soft)',
                                        'Lato' => 'Lato',
                                        'Raleway' => 'Raleway',
                                        'Nunito' => 'Nunito',
                                        'Outfit' => 'Outfit (Sleek)',
                                        'Ubuntu' => 'Ubuntu',
                                        'Kanit' => 'Kanit',
                                        'Work Sans' => 'Work Sans',
                                        'Playfair Display' => 'Playfair Display (Elegant)',
                                        'Oswald' => 'Oswald (Bold)',
                                    ])
                                    ->searchable()
                                    ->default('IBM Plex Sans')
                                    ->helperText('Font akan dimuat langsung dari Google Font Provider.'),
                            ])->columnSpan(1),
                    ]),

                Section::make('Pengaturan Navigasi Sidebar')
                    ->description('Sesuaikan perilaku sidebar untuk pengalaman pengguna yang lebih baik.')
                    ->icon('lucide-layout-panel-left')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('sidebar_width')
                                    ->label('Lebar Sidebar Utama')
                                    ->placeholder('350px')
                                    ->default('350px')
                                    ->helperText('Default: 350px. Gunakan satuan px atau rem.'),
                                Toggle::make('is_sidebar_collapsible_on_desktop')
                                    ->label('Sidebar Collapsible')
                                    ->helperText('Bisa dilipat ke samping pada tampilan Desktop.')
                                    ->default(true),
                                Toggle::make('are_navigation_groups_collapsible')
                                    ->label('Grup Navigasi Collapsible')
                                    ->helperText('Grup menu bisa dibuka-tutup.')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function getNavigationLabel(): string
    {
        return 'Pengaturan Aplikasi';
    }

    public function getTitle(): string
    {
        return 'Pengaturan Aplikasi';
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
        app(SystemAppSettings::class)->clearAllCustomCaches();

        Notification::make()
            ->title('Cache Berhasil Dibersihkan')
            ->success()
            ->send();
    }
}
