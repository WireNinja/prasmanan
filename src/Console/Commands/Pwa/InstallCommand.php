<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\Pwa;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

final class InstallCommand extends Command
{
    protected $signature = 'prasmanan:install-pwa';

    protected $description = 'Install and scaffold PWA configurations from Prasmanan into your application.';

    public function handle(): int
    {
        $this->components->info('Starting PWA integration...');

        $this->publishStubs();
        $this->updatePackageJson();
        $this->installDependencies();
        $this->checkLogoPresence();
        $this->displayWizard();

        return self::SUCCESS;
    }

    /**
     * Install required NPM dependencies using Bun.
     */
    private function installDependencies(): void
    {
        $this->components->info('Running bun add to fetch latest dependencies...');

        $result = Process::path(base_path())
            ->timeout(300)
            ->run('bun add -d @vite-pwa/assets-generator vite-plugin-pwa @iconify/tools');

        if ($result->successful()) {
            $this->components->task('Successfully installed latest NPM dependencies via Bun');

            return;
        }

        $this->components->error('Failed to run bun add. Please run it manually.');
        $this->line($result->errorOutput());
    }

    /**
     * Check if the PWA logo exists at the expected path.
     */
    private function checkLogoPresence(): void
    {
        $logoPath = base_path('public/pwa/icons/logo.png');

        if (File::exists($logoPath)) {
            $this->components->task('Logo found at public/pwa/icons/logo.png.');

            return;
        }

        $this->components->warn('Logo NOT found! public/pwa/icons/logo.png must exist for asset generation.');
        $this->components->warn('Silahkan tambahkan file gambar logo secara manual ke public/pwa/icons/logo.png.');
    }

    /**
     * Display the post-installation wizard instructions.
     */
    private function displayWizard(): void
    {
        $this->line('');
        $this->info('  =============================================================  ');
        $this->info('                 🚀 SYSTEM PWA TELAH TERPASANG 🚀                ');
        $this->info('  =============================================================  ');
        $this->line('');

        $this->line('  Sistem PWA (Service Worker, Manifest, Push) sudah siap dipakai.');
        $this->line('  Silahkan ikuti instruksi manual berikut untuk merampungkan integrasi:');
        $this->line('');

        $this->components->twoColumnDetail('<options=bold>Step 1</>', 'Sediakan logo klien di <fg=yellow>public/pwa/icons/logo.png</>');
        $this->components->twoColumnDetail('<options=bold>Step 2</>', 'Generate manifest & icon dengan <fg=green>bun run pwa:assets</>');
        $this->components->twoColumnDetail('<options=bold>Step 3</>', 'Timpa root icon bawaan Laravel dengan <fg=green>bun run pwa:copy</>');
        $this->components->twoColumnDetail('<options=bold>Step 4</>', 'Fetch cache Iconify (jika pakai) dengan <fg=green>bun run pwa:iconify</>');
        $this->components->twoColumnDetail('<options=bold>Step 5</>', 'Gabungkan perubahan akhir & testing via <fg=blue>bun run build</>');

        $this->line('');
        $this->info('  Selamat mengembangkan aplikasi bisnis Anda! ✨');
        $this->line('');
    }

    private function publishStubs(): void
    {
        $stubsDir = __DIR__.'/../../../../stubs/pwa';

        $filesToCopy = [
            'pwa-vite.config.stub' => 'pwa-vite.config.ts',
            'pwa-assets.config.stub' => 'pwa-assets.config.ts',
            'pwa-iconify-fetch.js.stub' => 'pwa-iconify-fetch.js',
            'pwa-icons-copy.js.stub' => 'pwa-icons-copy.js',
        ];

        foreach ($filesToCopy as $stub => $target) {
            $sourcePath = $stubsDir.'/'.$stub;
            $destinationPath = base_path($target);

            if (File::exists($sourcePath)) {
                File::copy($sourcePath, $destinationPath);
                $this->components->task("Published {$target}");
            } else {
                $this->components->error("Stub file not found: {$stub}");
            }
        }
    }

    private function updatePackageJson(): void
    {
        $packageJsonPath = base_path('package.json');

        if (! File::exists($packageJsonPath)) {
            $this->components->warn('package.json not found. Skipping NPM script setup.');

            return;
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);

        // Inject Scripts Only
        $packageJson['scripts'] = $packageJson['scripts'] ?? [];
        $packageJson['scripts']['pwa:iconify'] = 'bun pwa-iconify-fetch.js';
        $packageJson['scripts']['pwa:assets'] = 'pwa-assets-generator';
        $packageJson['scripts']['pwa:copy'] = 'bun pwa-icons-copy.js';

        File::put(
            $packageJsonPath,
            json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        $this->components->task('Updated package.json with PWA scripts');
    }
}
