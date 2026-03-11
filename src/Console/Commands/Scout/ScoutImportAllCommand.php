<?php

namespace WireNinja\Prasmanan\Console\Commands\Scout;

use Illuminate\Console\Command;
use Laravel\Scout\Searchable;
use Symfony\Component\Finder\Finder;

class ScoutImportAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prasmanan:scout-index-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index all records to MeiliSearch for all searchable models';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Indexing all searchable models...');

        $this->indexModels($this->getSearchableModels());

        $this->info('All models indexed!');
    }

    /**
     * Index all searchable models.
     *
     * @param  array<int, string>  $models
     */
    private function indexModels(array $models): void
    {
        foreach ($models as $model) {
            $this->info("Indexing [{$model}]...");
            $this->call('scout:import', ['model' => $model]);
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
