<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 🔥 KONFIGURASI REDIRECT GUEST
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->path() === '/' || $request->path() === 'login') {
                return null; // Jangan redirect untuk halaman landing dan login
            }
            return '/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();