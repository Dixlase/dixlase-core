<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */


use App\Helpers\PluginHelper;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\Front\FrontWelcomeController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// CSP違反レポートエンドポイント（認証不要、セッション・CSPミドルウェア除外）
Route::post('/csp-report', [CspReportController::class, 'report'])
    ->name('csp.report')
    ->withoutMiddleware([
        \App\Http\Middleware\ContentSecurityPolicy::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ]);

// インストール済みの場合にアクセス可能なルート
Route::middleware(['web', 'front.ip'])->group(
    function () {
        Route::get('/', [FrontWelcomeController::class, 'index'])->name('welcome');
        
        // フロントログテスト用ルート（開発用）
        Route::get('/test-front-log', function () {
            \Illuminate\Support\Facades\Log::channel('front_activity')->info('フロント操作ログテスト', [
                'action' => 'ページ閲覧',
                'page' => 'テストページ',
                'user_id' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            \Illuminate\Support\Facades\Log::channel('front_error')->error('フロントエラーログテスト', [
                'error' => 'テストエラー',
                'error_type' => 'test_error',
                'user_id' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'フロントログを出力しました',
                'logs' => [
                    'front_activity' => 'storage/logs/front_activity.log または front_activity-' . now()->format('Y-m-d') . '.log',
                    'front_error' => 'storage/logs/front_error.log または front_error-' . now()->format('Y-m-d') . '.log',
                ],
                'admin_url' => route('admin.settings.systems.logs.files', ['type' => 'front_activity']),
            ]);
        })->name('test.front.log');

        //テーマのアセットファイル
        Route::get('assets/{type}/{file}', function ($type, $file) {
            $basePath = match ($type) {
                'theme' => base_path('themes/' . getActiveThemeDirectory() . '/assets'), // アクティブテーマのディレクトリ名を取得
                'admin' => base_path('resources/admin/assets'),
                'plugin' => base_path("plugins/{$file}/assets"), // `file` をプラグイン名として扱う
                default => abort(404),
            };

            $filePath = "{$basePath}/{$file}";
            if (!File::exists($filePath)) {
                abort(404);
            }

            return response()->file($filePath);
        })->where('file', '.*');
    }
);

// プラグインのWebルートを読み込む
PluginHelper::loadEnabledWebRoutes();
