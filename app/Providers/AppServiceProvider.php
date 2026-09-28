<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bám theo scheme của APP_URL thay vì theo environment. Chạy local bằng
        // `php artisan serve` thì server chỉ nói HTTP, force https ở đây sẽ đẩy
        // browser sang https://127.0.0.1:8000 và server báo "Unsupported SSL
        // request". Qua ngrok hoặc production thì APP_URL là https nên link sinh
        // ra vẫn đúng scheme.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
