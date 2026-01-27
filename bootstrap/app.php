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
        $middleware->use([
            \App\Http\Middleware\CheckInstallationReady::class, // インストール準備状況チェック + インストール状態チェック
            \App\Http\Middleware\ApplySessionConfig::class, // セッション設定の動的適用
            \App\Http\Middleware\ContentSecurityPolicy::class, // CSPヘッダー付与
        ]);
        
        // CSPレポートエンドポイントをCSRF検証から除外
        $middleware->validateCsrfTokens(except: [
            'csp-report',
        ]);
        
        // 認証が必要なミドルウェアは後で実行
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\CspSafeMode::class, // CSPセーフモード検出（認証後に実行）
            \App\Http\Middleware\SetLocale::class, // フロントページ言語設定（管理メンバー優先）
            \App\Http\Middleware\SetMemberLocale::class, // 管理メンバー個別言語設定（管理画面用）
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


        // プラグイン用ミドルウェアグループ（基本）
        $middleware->group('plugin', [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // プラグインフロントエンド用（IP制限強制）
        $middleware->group('plugin.web', [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\FrontIpFilter::class, // IP制限を強制
        ]);

        // プラグイン管理画面用（認証 + IP制限強制）
        $middleware->group('plugin.admin', [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Authenticate::class . ':member', // 認証を強制
            \App\Http\Middleware\AdminIpFilter::class, // IP制限を強制
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
