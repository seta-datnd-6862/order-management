<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // App chạy sau Nginx của host (TLS) rồi tới Nginx trong container.
        // Không tin proxy thì client IP trong log sẽ là gateway của Docker
        // và Laravel tưởng request là http. Chỉ vào được qua host Nginx
        // nên tin toàn bộ proxy là an toàn ở đây.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
