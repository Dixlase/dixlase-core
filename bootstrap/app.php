<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__ . '/../routes/web.php',
            __DIR__ . '/../routes/admin.php', // adminルートもwebグループで動かす
        ],
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            //'auth.member' => \App\Http\Middleware\Authenticate::class . '::member', // 管理者メンバー用
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class, // メール認証
            'admin.ip' => \App\Http\Middleware\AdminIpFilter::class, // IPアドレスフィルタ
            'front.ip' => \App\Http\Middleware\FrontIpFilter::class, // フロントIPフィルタ
        ]);


        $middleware->group('web', [
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\SetSessionTable::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            //\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);


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
