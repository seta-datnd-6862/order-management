<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Backup the SQLite database to storage/backups/';

    public function handle(): int
    {
        $connection = config('database.default');
        $dbPath = config("database.connections.{$connection}.database");

        if (!file_exists($dbPath)) {
            $this->error("Database file not found: {$dbPath}");
            return self::FAILURE;
        }

        $backupDir = storage_path('backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $fileName = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sqlite';
        $backupPath = $backupDir . '/' . $fileName;

        if (!copy($dbPath, $backupPath)) {
            $this->error('Failed to copy database file.');
            return self::FAILURE;
        }

        $this->info("Backup saved: {$backupPath}");

        // Xóa backup cũ hơn 30 ngày (giữ tối đa 30 file)
        $files = collect(File::files($backupDir))
            ->sortByDesc(fn ($f) => $f->getMTime());

        $threshold = now()->subDays(30)->timestamp;
        $deleted = 0;

        foreach ($files->slice(30) as $file) {
            File::delete($file->getPathname());
            $deleted++;
        }

        foreach ($files->take(30) as $file) {
            if ($file->getMTime() < $threshold) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Deleted {$deleted} old backup(s).");
        }

        return self::SUCCESS;
    }
}
