<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportDatabaseFromS3 extends Command
{
    protected $signature = 'db:import-from-s3 {--path=_db/database_data.sql : S3 path to the SQL dump}';
    protected $description = 'Download a SQL dump from S3 and import it into the database';

    public function handle(): int
    {
        $s3Path = $this->option('path');

        $this->info("Downloading {$s3Path} from S3...");

        $s3 = Storage::disk('s3');

        if (!$s3->exists($s3Path)) {
            $this->error("File not found on S3: {$s3Path}");
            return 1;
        }

        $size = $s3->size($s3Path);
        $this->info("File size: " . round($size / 1024 / 1024, 2) . " MB");

        // Download to a temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'db_import_');

        $this->info("Downloading to temp file...");
        $stream = $s3->readStream($s3Path);

        if (!$stream) {
            $this->error("Failed to open S3 stream for: {$s3Path}");
            return 1;
        }

        $localStream = fopen($tempFile, 'w');
        stream_copy_to_stream($stream, $localStream);
        fclose($localStream);
        fclose($stream);

        $this->info("Download complete. Importing into database...");

        // Get database connection details
        $config = config('database.connections.' . config('database.default'));
        $host = $config['host'];
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        // Use mysql CLI to import (handles large files efficiently)
        $command = sprintf(
            'mysql -h %s -P %s -u %s -p%s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($tempFile)
        );

        $this->info("Running mysql import...");
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        // Clean up temp file
        @unlink($tempFile);

        if ($exitCode !== 0) {
            $this->error("MySQL import failed with exit code: {$exitCode}");
            foreach ($output as $line) {
                $this->error($line);
            }

            // Fallback: try importing via PDO in chunks
            $this->warn("Attempting fallback import via PDO...");
            return $this->importViaPdo($s3Path);
        }

        $this->info("Database import completed successfully!");

        // Show some stats
        try {
            $products = DB::table('products')->count();
            $brands = DB::table('brands')->count();
            $categories = DB::table('categories')->count();
            $this->info("Stats - Products: {$products}, Brands: {$brands}, Categories: {$categories}");
        } catch (\Throwable $e) {
            // Stats are optional
        }

        return 0;
    }

    private function importViaPdo(string $s3Path): int
    {
        $s3 = Storage::disk('s3');
        $sql = $s3->get($s3Path);

        if (!$sql) {
            $this->error("Failed to read SQL from S3");
            return 1;
        }

        $this->info("Loaded SQL dump (" . round(strlen($sql) / 1024 / 1024, 2) . " MB). Executing...");

        // Split by statement delimiter, handling multi-line statements
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

        // Disable foreign key checks during import
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec('SET UNIQUE_CHECKS=0');
        $pdo->exec("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");

        $statements = 0;
        $errors = 0;

        // Process SQL line by line to handle large files
        $currentStatement = '';
        $lines = explode("\n", $sql);
        $totalLines = count($lines);

        // Free the large string
        unset($sql);

        $bar = $this->output->createProgressBar($totalLines);
        $bar->start();

        foreach ($lines as $line) {
            $bar->advance();

            // Skip comments and empty lines
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                continue;
            }

            $currentStatement .= $line . "\n";

            // Check if statement is complete (ends with semicolon)
            if (str_ends_with($trimmed, ';')) {
                try {
                    $pdo->exec($currentStatement);
                    $statements++;
                } catch (\Throwable $e) {
                    $errors++;
                    if ($errors <= 10) {
                        $this->newLine();
                        $this->warn("Error: " . $e->getMessage());
                    }
                }
                $currentStatement = '';
            }
        }

        // Re-enable checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $pdo->exec('SET UNIQUE_CHECKS=1');

        $bar->finish();
        $this->newLine(2);

        $this->info("Import complete! Executed: {$statements} statements, Errors: {$errors}");

        // Show some stats
        try {
            $products = DB::table('products')->count();
            $brands = DB::table('brands')->count();
            $categories = DB::table('categories')->count();
            $this->info("Stats - Products: {$products}, Brands: {$brands}, Categories: {$categories}");
        } catch (\Throwable $e) {
            // Stats are optional
        }

        return $errors > 0 ? 1 : 0;
    }
}
