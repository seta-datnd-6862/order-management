<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Backup the database to storage/backups/';

    public function handle(): int
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $backupDir = storage_path('backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $stamp = now()->format('Y-m-d_H-i-s');

        $backupPath = match ($driver) {
            'sqlite' => $this->dumpSqlite($connection, $backupDir, $stamp),
            'mysql', 'mariadb' => $this->dumpMysql($connection, $backupDir, $stamp),
            default => null,
        };

        if ($backupPath === null) {
            if (!in_array($driver, ['sqlite', 'mysql', 'mariadb'], true)) {
                $this->error("Unsupported database driver: {$driver}");
            }

            return self::FAILURE;
        }

        $this->info("Backup saved: {$backupPath}");
        $this->prune($backupDir);

        return self::SUCCESS;
    }

    private function dumpSqlite(string $connection, string $backupDir, string $stamp): ?string
    {
        $dbPath = config("database.connections.{$connection}.database");

        if (!file_exists($dbPath)) {
            $this->error("Database file not found: {$dbPath}");

            return null;
        }

        $backupPath = "{$backupDir}/backup_{$stamp}.sqlite";

        if (!copy($dbPath, $backupPath)) {
            $this->error('Failed to copy database file.');

            return null;
        }

        return $backupPath;
    }

    private function dumpMysql(string $connection, string $backupDir, string $stamp): ?string
    {
        $cfg = config("database.connections.{$connection}");
        $backupPath = "{$backupDir}/backup_{$stamp}.sql.gz";

        // Mật khẩu đi qua MYSQL_PWD chứ không qua argv, để không lộ trong `ps`.
        $process = Process::fromShellCommandline(
            'mysqldump --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER"'
            .' --single-transaction --quick --no-tablespaces --routines --events'
            .' "$DB_NAME" | gzip -9 > "$DEST"'
        );
        $process->setTimeout(1800);
        $process->setEnv([
            'DB_HOST' => $cfg['host'],
            'DB_PORT' => (string) $cfg['port'],
            'DB_USER' => $cfg['username'],
            'MYSQL_PWD' => $cfg['password'],
            'DB_NAME' => $cfg['database'],
            'DEST' => $backupPath,
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('mysqldump failed: '.trim($process->getErrorOutput()));
            File::delete($backupPath);

            return null;
        }

        return $backupPath;
    }

    /** Giữ tối đa 30 file và bỏ mọi bản cũ hơn 30 ngày. */
    private function prune(string $backupDir): void
    {
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
    }
}
