<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncStorageToS3 extends Command
{
    protected $signature = 'storage:sync-to-s3';
    protected $description = 'Upload all local public storage files to S3';

    public function handle(): int
    {
        $local = Storage::disk('public');
        $s3 = Storage::disk('s3');

        $files = $local->allFiles();

        if (empty($files)) {
            $this->warn('No files found in local public storage.');
            return 0;
        }

        $this->info("Found " . count($files) . " files to upload.");
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        $uploaded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($files as $file) {
            try {
                if ($s3->exists($file)) {
                    $skipped++;
                } else {
                    $s3->put($file, $local->get($file), 'public');
                    $uploaded++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed: {$file} - {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! Uploaded: {$uploaded}, Skipped (already exists): {$skipped}, Failed: {$failed}");

        return 0;
    }
}
