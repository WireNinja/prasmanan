<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\System;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

class DeployAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prasmanan:assets {--pack : Build and pack assets locally} {--unpack : Unpack assets on hosting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle asset deployment for shared hosting by packing/unpacking public/build';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('pack')) {
            return $this->pack();
        }

        if ($this->option('unpack')) {
            return $this->unpack();
        }

        $this->error('Please specify either --pack or --unpack');
        $this->info('Usage:');
        $this->info('  Local:   php artisan prasmanan:assets --pack');
        $this->info('  Hosting: php artisan prasmanan:assets --unpack');

        return Command::FAILURE;
    }

    protected function pack(): int
    {
        $this->info('🚀 Starting local asset packing...');

        // 1. Build assets
        $this->info('📦 Building assets with bun...');
        $process = new Process(['bun', 'run', 'build']);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error('❌ Build failed!');

            return Command::FAILURE;
        }

        // 2. Pack to zip
        $this->info('🗜️  Packing public/build to prasmanan-assets.zip...');

        $zipPath = public_path('prasmanan-assets.zip');
        $buildPath = public_path('build');

        if (! File::exists($buildPath)) {
            $this->error('❌ Build directory not found at public/build');

            return Command::FAILURE;
        }

        if (! class_exists('ZipArchive')) {
            $this->error('❌ ZipArchive PHP extension is not installed.');

            return Command::FAILURE;
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('❌ Failed to create zip file.');

            return Command::FAILURE;
        }

        $files = File::allFiles($buildPath);

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = 'build/'.str_replace($buildPath.DIRECTORY_SEPARATOR, '', $filePath);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();

        $this->info("✅ Assets packed successfully to: {$zipPath}");
        $this->info('👉 Don\'t forget to git add and push this zip file!');

        return Command::SUCCESS;
    }

    protected function unpack(): int
    {
        $this->info('📥 Starting asset unpacking on hosting...');

        $zipPath = public_path('prasmanan-assets.zip');
        $targetPath = public_path();

        if (! File::exists($zipPath)) {
            $this->error("❌ Packed assets not found at: {$zipPath}");
            $this->info('Make sure you have pushed the zip file from local.');

            return Command::FAILURE;
        }

        if (! class_exists('ZipArchive')) {
            $this->error('❌ ZipArchive PHP extension is not installed on this hosting.');

            return Command::FAILURE;
        }

        // 1. Clean existing build
        $this->info('🧹 Cleaning existing public/build directory...');
        File::deleteDirectory(public_path('build'));

        // 2. Unpack
        $this->info('🔓 Unpacking prasmanan-assets.zip...');
        $zip = new ZipArchive();

        if ($zip->open($zipPath) === true) {
            $zip->extractTo($targetPath);
            $zip->close();

            $this->info('✅ Assets unpacked successfully to public/build');

            // 3. Cleanup
            File::delete($zipPath);
            $this->info('✨ Cleanup: prasmanan-assets.zip removed.');
        } else {
            $this->error('❌ Failed to open zip file.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
