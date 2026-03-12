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
                Section::make('Branding')
                    ->description('Sesuaikan identitas visual aplikasi Anda.')
                    ->icon('lucide-palette')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('brand_name')
                                    ->label('Nama Brand')
                                    ->required(),
                                FileUpload::make('brand_logo')
                                    ->label('Logo Brand')
                                    ->image()
                                    ->disk('public')
                                    ->directory('branding'),
                            ]),
                    ]),

                Section::make('Tata Letak & Tema')
                    ->description('Atur tampilan dan perilaku navigasi panel.')
                    ->icon('lucide-layout')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_dark_mode_enabled')
                                    ->label('Aktifkan Mode Gelap')
                                    ->helperText('Izinkan pengguna beralih ke mode gelap.')
                                    ->default(true),
                                Select::make('custom_font')
                                    ->label('Font Kustom')
                                    ->options([
                                        'IBM Plex Sans' => 'IBM Plex Sans',
                                        'Inter' => 'Inter',
                                        'Roboto' => 'Roboto',
                                        'Outfit' => 'Outfit',
                                    ])
                                    ->default('IBM Plex Sans'),
                                TextInput::make('sidebar_width')
                                    ->label('Lebar Sidebar')
                                    ->placeholder('350px')
                                    ->default('350px'),
                                Toggle::make('is_sidebar_collapsible_on_desktop')
                                    ->label('Sidebar Dapat Dilipat di Desktop')
                                    ->default(true),
                                Toggle::make('are_navigation_groups_collapsible')
                                    ->label('Grup Navigasi Dapat Dilipat')
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
}
