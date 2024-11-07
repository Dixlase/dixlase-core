<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use App\Http\Controllers\Register\RegisterRegisteredUserController;
use App\Http\Controllers\Install\InstallController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
// インストール用ルート（ミドルウェア適用なし）
Route::get('/install', [InstallController::class, 'showForm'])->name('install');
Route::post('/install', [InstallController::class, 'processForm'])->name('install.process');
*/

// インストール済みの場合にアクセス可能なルート
Route::middleware('web')->group(function () {

    Route::get('/install', [InstallController::class, 'showWelcome'])->name('install.welcome');
    Route::get('/install/settings', [InstallController::class, 'showSiteSettings'])->name('install.settings');
    Route::post('/install/settings', [InstallController::class, 'postSiteSettings']);
    Route::get('/install/confirm', [InstallController::class, 'showConfirm'])->name('install.confirm');
    Route::post('/install/confirm', [InstallController::class, 'postConfirm']);
    Route::get('/install/complete', [InstallController::class, 'showComplete'])->name('install.complete');

    Route::get('/', function () {
        return view('welcome');
    });

    //アカウント登録
    Route::middleware('guest')->group(function () {
        Route::get('register', [RegisterRegisteredUserController::class, 'create'])
            ->name('register');
        Route::post('register', [RegisterRegisteredUserController::class, 'store']);
    });
});



//マイページ用のルーティング
require __DIR__ . '/mypage.php';

//管理者用のルーティング
require __DIR__ . '/admin.php';
