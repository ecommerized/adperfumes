<?php

namespace App\Console\Commands;

use App\Jobs\GenerateSeoAeoJob;
use Illuminate\Console\Command;

class GenerateSeoCommand extends Command
{
    protected $signature = 'seo:generate
        {model : Model type (product, brand, category, blog_post)}
        {--id= : Specific model ID}
        {--force : Force regeneration even if manually edited}
        {--all : Generate for all records of this type}';

    protected $description = 'Generate SEO/AEO data for models via Claude AI';

    public function handle(): int
    {
        $type = $this->argument('model');
        $modelClass = config("seo.content_types.{$type}");

        if (!$modelClass || !class_exists($modelClass)) {
            $this->error("Unknown model type: {$type}");
            $this->info('Available types: ' . implode(', ', array_keys(config('seo.content_types'))));
            return Command::FAILURE;
        }

        $force = $this->option('force');

        if ($this->option('id')) {
            $model = $modelClass::find($this->option('id'));
            if (!$model) {
                $this->error("Record not found: {$type} #{$this->option('id')}");
                return Command::FAILURE;
            }

            $this->info("Dispatching SEO generation for {$type} #{$model->id}...");
            GenerateSeoAeoJob::dispatch($model, $force);
            $this->info('Job dispatched to queue.');

        } elseif ($this->option('all')) {
            $count = $modelClass::count();
            if (!$this->confirm("Generate SEO for all {$count} {$type} records?")) {
                return Command::SUCCESS;
            }

            $bar = $this->output->createProgressBar($count);
            $modelClass::chunk(50, function ($models) use ($bar, $force) {
                foreach ($models as $model) {
                    GenerateSeoAeoJob::dispatch($model, $force)
                        ->delay(now()->addSeconds(rand(1, 10)));
                    $bar->advance();
                }
            });
            $bar->finish();
            $this->newLine();
            $this->info("{$count} jobs dispatched to queue.");

        } else {
            $this->error('Please specify --id=N or --all');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
