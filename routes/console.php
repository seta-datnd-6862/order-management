<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup database hàng ngày lúc 2:00 AM
// Để chạy scheduler, thêm vào crontab:
// * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('db:backup')->dailyAt('02:00');
