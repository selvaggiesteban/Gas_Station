<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a SQL backup of the database using mysqldump';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = "backup-" . now()->format('Y-m-d_H-i-s') . ".sql";
        $path = storage_path("app/backups/" . $filename);
        
        $dbHost = config('database.connections.mysql.host');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($path)
        );

        $output = [];
        $returnVar = null;
        
        $this->info("Starting backup: $filename");
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $this->info("Backup successfully saved to: $path");
            Log::info("Database backup successful: $filename");
        } else {
            $this->error("Backup failed with exit code: $returnVar");
            Log::error("Database backup failed: $filename with exit code $returnVar");
        }
    }
}
