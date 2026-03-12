<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Concerns;

use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\Size;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Illuminate\Foundation\Application;

/**
 * @property Application $app
 */
trait ConfiguresFilament
{
    protected function configureFilament(): void
    {
        $this->configureFilamentTimezone();
        $this->configureFilamentTables();
        $this->configureFilamentActions();
        $this->configureFilamentForms();
    }

    protected function configureFilamentTimezone(): void
    {
        FilamentTimezone::set(config('app.timezone'));
    }

    protected function configureFilamentTables(): void
    {
        Table::configureUsing(fn (Table $table): Table => $table
            ->paginationMode(PaginationMode::Cursor)
            ->defaultPaginationPageOption(25)
            ->defaultDateDisplayFormat('j F Y')
            ->defaultDateTimeDisplayFormat('j F Y, H:i')
            ->defaultTimeDisplayFormat('H:i')
            ->defaultNumberLocale('id')
            ->defaultSort('id', 'desc')
            ->defaultCurrency('IDR')
            ->deferLoading());
    }

    protected function configureFilamentActions(): void
    {
        ActionGroup::configureUsing(function (ActionGroup $group): void {
            $group
                ->size(Size::Small)
                ->hiddenLabel();
        });
    }

    protected function configureFilamentForms(): void
    {
        Select::configureUsing(function (Select $select): void {
            $select
                ->native(false)
                ->searchable();
        });

        DateTimePicker::configureUsing(function (DateTimePicker $datePicker): void {
            $datePicker
                ->native(false)
                ->displayFormat('j F Y, H:i:s')
                ->timezone(config('app.timezone'));
        });

        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $datePicker
                ->native(false)
                ->displayFormat('j F Y')
                ->timezone(config('app.timezone'));
        });

        FileUpload::configureUsing(function (FileUpload $fileUpload): void {
            $fileUpload
                ->editableSvgs()
                ->imageEditor()
                ->preserveFilenames(false)
                ->maxParallelUploads(10)
                ->maxSize(config('prasmanan.security.max_file_upload_size'));
        });
    }
}
