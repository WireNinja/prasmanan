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
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use UnitEnum;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use WireNinja\Prasmanan\Exceptions\PwaIconGenerationException;

class ManageAppSettings extends SettingsPage
{
    use HasPageShield;

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
            ->action(fn() => $this->clearCache());
    }

    public function clearCache(): void
    {
        app(SystemAppSettings::class)->clearAllCustomCaches();

        Notification::make()
            ->title('Cache Berhasil Dibersihkan')
            ->success()
            ->send();
    }

    protected function afterSave(): void
    {
        rescue(function () {
            if (config('prasmanan.is_shared_hosting')) {
                $this->syncPwaIcons();
            } else {
                $this->executeBunPwaPipeline();
            }
        }, function ($e) {
            Notification::make()
                ->title('Ikon PWA Gagal Diperbarui')
                ->body('Terjadi kesalahan saat memproses ikon. Silakan hubungi tim IT atau cek log.')
                ->danger()
                ->send();

            throw new PwaIconGenerationException(
                "Gagal sinkronisasi PWA: " . $e->getMessage(),
                0,
                $e
            );
        });
    }

    private function executeBunPwaPipeline(): void
    {
        // 1. Manjakan logo aslinya ke dalam folder PWA Icons agar Bun bisa memprosesnya
        $this->overwriteLogoForBun();

        // 2. Jalankan Bun asinkron
        $rootPath = base_path();

        // Cari path bun (fallback ke /usr/local/bin/bun jika tidak ada di path sistem)
        $bunBinary = env('PRASMANAN_BUN_PATH', 'bun');

        // Jalankan generator
        $assetsProcess = Process::path($rootPath)->run($bunBinary . ' run pwa:assets');

        if ($assetsProcess->failed()) {
            throw new \Exception("Bun Assets Gagal: " . $assetsProcess->errorOutput());
        }

        // Jalankan copy
        $copyProcess = Process::path($rootPath)->run($bunBinary . ' run pwa:copy');

        if ($copyProcess->failed()) {
            throw new \Exception("Bun Copy Gagal: " . $copyProcess->errorOutput());
        }

        Notification::make()
            ->title('PWA Assets Diperbarui (Bun Runtime)')
            ->body('Seluruh ikon PWA telah di-generate ulang secara optimal!')
            ->success()
            ->send();
    }

    private function overwriteLogoForBun(): void
    {
        $logoPath = $this->settings->brand_logo;
        if (!$logoPath) {
            return;
        }

        $fullPath = storage_path('app/public/' . $logoPath);

        if (File::exists($fullPath)) {
            File::copy($fullPath, public_path('pwa/icons/logo.png'));
        }
    }

    private function syncPwaIcons(): void
    {
        /** @var SystemAppSettings $settings */
        $settings = $this->settings;
        $logoPath = $settings->brand_logo;

        if (!$logoPath) {
            return;
        }

        $fullPath = storage_path('app/public/' . $logoPath);

        if (!File::exists($fullPath)) {
            return;
        }

        $pwaIconsPath = public_path('pwa/icons');

        if (!File::isDirectory($pwaIconsPath)) {
            File::makeDirectory($pwaIconsPath, 0755, true);
        }

        // 1. Overwrite logo.png
        File::copy($fullPath, $pwaIconsPath . '/logo.png');

        // 2. Define icons and their target sizes
        $icons = [
            'pwa-64x64.png' => 64,
            'pwa-192x192.png' => 192,
            'pwa-512x512.png' => 512,
            'maskable-icon-512x512.png' => 512,
            'apple-touch-icon-180x180.png' => 180,
            'favicon.ico' => 32, // Sync as 32x32 to public/favicon.ico
        ];

        foreach ($icons as $filename => $size) {
            $this->resizeImage($fullPath, $pwaIconsPath . '/' . $filename, $size, $size);
            
            // Copy certain files to root public
            if (in_array($filename, ['favicon.ico', 'apple-touch-icon-180x180.png', 'pwa-192x192.png', 'pwa-512x512.png'])) {
                File::copy($pwaIconsPath . '/' . $filename, public_path($filename));
            }
        }

        Notification::make()
            ->title('PWA Ikon Berhasil Disinkronisasi (Native PHP)')
            ->body('Seluruh ikon PWA telah diperbarui menggunakan logo baru via GD library.')
            ->success()
            ->send();
    }

    private function resizeImage(string $sourcePath, string $targetPath, int $width, int $height): void
    {
        $info = getimagesize($sourcePath);
        $mime = $info['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                throw new \Exception("Format gambar tidak didukung: {$mime}");
        }

        $target = imagecreatetruecolor($width, $height);

        // Preserve Transparency for PNG and WEBP
        if ($mime == 'image/png' || $mime == 'image/webp') {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
            imagefilledrectangle($target, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);

        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($target, $targetPath, 90);
                break;
            case 'image/png':
                imagepng($target, $targetPath, 9);
                break;
            case 'image/webp':
                imagewebp($target, $targetPath, 90);
                break;
        }

        imagedestroy($source);
        imagedestroy($target);
    }
}
