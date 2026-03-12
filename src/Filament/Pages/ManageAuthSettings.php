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

class ManageAuthSettings extends SettingsPage
{
    protected static string $settings = SystemAuthSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'lucide-lock';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Slider Configuration')
                    ->description('Manage slider behavior and images for the authentication split layout.')
                    ->icon('lucide-layout')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('login_split_slider_enabled')
                                    ->label('Enable Slider')
                                    ->helperText('Activate image slider for split login page')
                                    ->reactive(),
                                TextInput::make('login_split_slider_interval')
                                    ->label('Slider Interval (ms)')
                                    ->numeric()
                                    ->step(500)
                                    ->suffix('ms')
                                    ->visible(fn ($get) => $get('login_split_slider_enabled')),
                            ]),

                        Repeater::make('login_split_images')
                            ->label('Split Layout Images')
                            ->schema([
                                FileUpload::make('image_path')
                                    ->label('Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('split-auth')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->minItems(1)
                            ->columns(1)
                            ->grid(3)
                            ->itemLabel(fn (array $state): ?string => $state['image_path'] ?? null),
                    ]),
            ]);
    }

    public static function getNavigationLabel(): string
    {
        return 'Auth Settings';
    }

    public function getTitle(): string
    {
        return 'Auth Settings';
    }
}
