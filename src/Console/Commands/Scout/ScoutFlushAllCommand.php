<?php

namespace WireNinja\Prasmanan\Console\Commands\Scout;

use Illuminate\Console\Command;
use Laravel\Scout\Searchable;
use Symfony\Component\Finder\Finder;

class ScoutFlushAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prasmanan:scout-flush-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush all records from MeiliSearch for all searchable models';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Flushing all searchable models...');

        $this->flushModels($this->getSearchableModels());

        $this->info('All models flushed!');
    }

    /**
     * Flush all searchable models.
     *
     * @param  array<int, string>  $models
     */
    private function flushModels(array $models): void
    {
        foreach ($models as $model) {
            $this->info("Flushing [{$model}]...");
            $this->call('scout:flush', ['model' => $model]);
        }
    }

    private function getSearchableModels(): array
    {
        $models = [];
        $path = app_path('Models');

        if (! is_dir($path)) {
            return [];
        }

        foreach ((new Finder)->in($path)->files() as $file) {
            $model = 'App\\Models\\'.$file->getBasename('.php');
            if (class_exists($model) && in_array(Searchable::class, class_uses_recursive($model))) {
                $models[] = $model;
            }
        }

        return $models;
    }
}
