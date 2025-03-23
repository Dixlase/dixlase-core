<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__ . '/../routes/front.php',
            __DIR__ . '/../routes/install.php',
            __DIR__ . '/../routes/admin.php'
        ],
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class, //認証
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class, // メール認証
            'admin.ip' => \App\Http\Middleware\AdminIpFilter::class, // IPアドレスフィルタ
            'front.ip' => \App\Http\Middleware\FrontIpFilter::class, // フロントIPフィルタ
            'log.admin.activity' => \App\Http\Middleware\LogAdminActivity::class, // 管理画面操作ログ
        ]);


        $middleware->group('plugin', [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // CheckInstallationミドルウェアをグローバルに追加
        $middleware->prepend(\App\Http\Middleware\CheckInstallation::class);
    })


    ->withExceptions(function (Exceptions $exceptions) {
        // エラー処理を追加する場合
        /*
        $exceptions->renderable(fn (\Exception $e) => response()->json(['error' => $e->getMessage()], 500));

        $exceptions->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            return response()->json(['error' => 'Resource not found'], 404);
        });

        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return redirect('/login');
        });
        */
    })->create();
