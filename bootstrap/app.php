<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        //$middleware->append(\App\Http\Middleware\RedirectToHttps::class,);
        // Laravelのデフォルトエイリアスをカスタマイズしたものに置き換え
        // エイリアスの配列を設定
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class, // メール認証
            'admin.ip' => \App\Http\Middleware\AdminIpFilter::class, // IPアドレスフィルタ
            'front.ip' => \App\Http\Middleware\FrontIpFilter::class, // フロントIPフィルタ
        ]);
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('plugin', [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
