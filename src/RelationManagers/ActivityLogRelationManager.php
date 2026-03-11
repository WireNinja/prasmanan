<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\RelationManagers;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;
use Spatie\Activitylog\Models\Activity;

class ActivityLogRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Riwayat Aktivitas';

    protected static ?string $modelLabel = 'Aktivitas';

    protected static ?string $pluralModelLabel = 'Aktivitas';

    protected static ?string $recordTitleAttribute = 'description';

    protected static string|BackedEnum|null $icon = 'lucide-clock';

    #[Override]
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Informasi Aktivitas')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('log_name')
                                    ->label('Nama Log'),

                                TextEntry::make('event')
                                    ->label('Jenis Aktivitas')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'created' => 'success',
                                        'updated' => 'warning',
                                        'deleted' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'created' => 'Dibuat',
                                        'updated' => 'Diperbarui',
                                        'deleted' => 'Dihapus',
                                        default => ucfirst($state),
                                    }),

                                TextEntry::make('causer.name')
                                    ->label('Dilakukan Oleh')
                                    ->default('-')
                                    ->icon('lucide-user'),

                                TextEntry::make('created_at')
                                    ->label('Waktu')
                                    ->dateTime('d F Y, H:i:s')
                                    ->icon('lucide-clock'),
                            ]),

                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ]),

                Section::make('Perubahan Data')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                KeyValueEntry::make('properties.attributes')
                                    ->label('Data Baru')
                                    ->visible(fn (Activity $record): bool => ! empty($record->properties['attributes'] ?? []))
                                    ->keyLabel('Kolom')
                                    ->valueLabel('Nilai'),

                                KeyValueEntry::make('properties.old')
                                    ->label('Data Lama')
                                    ->visible(fn (Activity $record): bool => ! empty($record->properties['old'] ?? []))
                                    ->keyLabel('Kolom')
                                    ->valueLabel('Nilai'),
                            ]),
                    ])
                    ->visible(fn (Activity $record): bool => ! empty($record->properties)),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event')
                    ->label('Aktivitas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                        default => ucfirst($state),
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState()),

                TextColumn::make('causer.name')
                    ->label('Oleh')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->icon('lucide-user'),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->icon('lucide-clock')
                    ->since()
                    ->tooltip(fn (Activity $record): string => $record->created_at?->translatedFormat('d F Y, H:i:s') ?? '-'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum Ada Aktivitas')
            ->emptyStateDescription('Aktivitas akan muncul di sini ketika ada perubahan data.')
            ->emptyStateIcon('lucide-activity');
    }
}
