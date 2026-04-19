<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/front.php',
            __DIR__.'/../routes/install.php',
            __DIR__.'/../routes/admin.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Traefik等のリバースプロキシ背後で正しくHTTPS/IPを認識する
        $middleware->trustProxies(at: '*');

        // Register global middlewares
        $middleware->use([
            \Illuminate\Http\Middleware\TrustProxies::class, // リバースプロキシ背後でHTTPS/IPを認識
            \App\Http\Middleware\CheckInstallationReady::class, // インストール準備状況チェック + インストール状態チェック
            \App\Http\Middleware\ForceHttps::class, // FORCE_SSL有効時にHTTPS強制リダイレクト
            \App\Http\Middleware\ApplySessionConfig::class, // セッション設定の動的適用
            \App\Http\Middleware\ContentSecurityPolicy::class, // CSPヘッダー付与
        ]);

        // CSPレポートエンドポイントをCSRF検証から除外
        $middleware->validateCsrfTokens(except: [
            'csp-report',
        ]);

        // セッション開始後に実行するミドルウェア
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\DebugCsrf::class, // TEMP DEBUG: CSRF 419 調査用（調査後削除）
            \App\Http\Middleware\CheckMaintenanceMode::class, // メンテナンスモードチェック（認証状態を参照するためセッション後に実行）
            \App\Http\Middleware\SafeMode::class, // セーフモード検出（認証後に実行、CSP/プラグイン/テーマ対応）
            \App\Http\Middleware\BlockPluginRoutes::class, // プラグインセーフモード時のルートブロック
            \App\Http\Middleware\SetLocale::class, // フロントページ言語設定（管理メンバー優先）
            \App\Http\Middleware\SetMemberLocale::class, // 管理メンバー個別言語設定（管理画面用）
        ]);

        // Register route middleware aliases
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class, // 認証
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class, // メール認証
            'admin.ip' => \App\Http\Middleware\AdminIpFilter::class, // IPアドレスフィルタ
            'front.ip' => \App\Http\Middleware\FrontIpFilter::class, // フロントIPフィルタ
            'log.admin.activity' => \App\Http\Middleware\LogAdminActivity::class, // 管理画面操作ログ
            'check.menu.access' => \App\Http\Middleware\CheckMenuAccess::class, // 管理画面メニューアクセス権限
            'check.menu.edit' => \App\Http\Middleware\CheckMenuEdit::class, // 管理画面メニュー編集権限
            'install.steps' => \App\Http\Middleware\CheckInstallationSteps::class, // インストールステップチェック
            'auth.api' => \App\Http\Middleware\AuthenticateApiKey::class, // APIキー認証
            'throttle.api' => \App\Http\Middleware\ThrottleApiRequest::class, // APIレートリミット
            'log.api' => \App\Http\Middleware\LogApiRequest::class, // APIリクエストログ
            'role' => \App\Http\Middleware\CheckRole::class, // ロールチェック
            'permission' => \App\Http\Middleware\CheckPermission::class, // 権限チェック
        ]);

        // プラグインAPI用（APIキー認証 + レートリミット + ログ）
        $middleware->group('plugin.api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\CheckLockdown::class,
            \App\Http\Middleware\AuthenticateApiKey::class,
            \App\Http\Middleware\ThrottleApiRequest::class,
            \App\Http\Middleware\LogApiRequest::class,
        ]);

        // プラグインAPI公開用（認証不要、レートリミット + ログのみ）
        $middleware->group('plugin.api.public', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\CheckLockdown::class,
            \App\Http\Middleware\ThrottleApiRequest::class,
            \App\Http\Middleware\LogApiRequest::class,
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
            \App\Http\Middleware\Authenticate::class.':member', // 認証を強制
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
