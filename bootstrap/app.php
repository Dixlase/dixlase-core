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
        // Register global middlewares
        $middleware->append([
            \App\Http\Middleware\CheckInstallationReady::class, // インストール準備状況チェック + インストール状態チェック
            \App\Http\Middleware\ApplySessionConfig::class, // セッション設定の動的適用
            \App\Http\Middleware\SetMemberLocale::class, // 管理メンバー個別言語設定
        ]);

        // Register route middleware aliases
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class, //認証
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class, // メール認証
            'admin.ip' => \App\Http\Middleware\AdminIpFilter::class, // IPアドレスフィルタ
            'front.ip' => \App\Http\Middleware\FrontIpFilter::class, // フロントIPフィルタ
            'log.admin.activity' => \App\Http\Middleware\LogAdminActivity::class, // 管理画面操作ログ
            'check.menu.access' => \App\Http\Middleware\CheckMenuAccess::class, // 管理画面メニューアクセス権限
            'check.menu.edit' => \App\Http\Middleware\CheckMenuEdit::class, // 管理画面メニュー編集権限
            'install.steps' => \App\Http\Middleware\CheckInstallationSteps::class, // インストールステップチェック
        ]);


        $middleware->group('plugin', [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
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
