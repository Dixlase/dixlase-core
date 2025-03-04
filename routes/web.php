<?php

/**
 * This file is part of MySoftware.
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


use App\Http\Controllers\Register\RegisterRegisteredUserController;
use App\Http\Controllers\Front\FrontWelcomeController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Traits\ThemeLoaderTrait;

//セッションがスタートしていなかったらスタートする
/*
if (!session()->isStarted()) {
    session()->start();
    dump('セッションスタート');
}
*/

Route::middleware('web')->get('/debug-session', function () {
    return 'Debug';
});


Route::middleware(['front.ip'])->group(
    function () {
        //トップページ
        Route::get('/', [FrontWelcomeController::class, 'index'])->name('welcome');

        /*
        Route::get('/', function () {
            // ここは“リクエスト時”に実行されるため、セッションが開始済み

            $member = Auth::guard('member')->user();
            if ($member) {
                //dump($member);
            } else {
                dump('メンバーがいません');
            }
        });
        */


        //アカウント登録
        Route::middleware('guest')->group(function () {
            Route::get('register', [RegisterRegisteredUserController::class, 'create'])
                ->name('register');
            Route::post('register', [RegisterRegisteredUserController::class, 'store']);
        });

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


        //マイページ用のルーティング
        require __DIR__ . '/mypage.php';
    }
);
