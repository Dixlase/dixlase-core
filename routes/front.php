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

// CSP違反レポートエンドポイント（認証不要、CSPミドルウェア除外）
Route::post('/csp-report', [CspReportController::class, 'report'])
    ->name('csp.report')
    ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

// インストール済みの場合にアクセス可能なルート
Route::middleware(['web', 'front.ip'])->group(
    function () {
        Route::get('/', [FrontWelcomeController::class, 'index'])->name('welcome');

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
