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

use App\Http\Controllers\Install\InstallController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


Route::prefix('install')->name('install.')->middleware('install.steps')->group(
    function () {
        // 言語切り替え（AJAX専用）
        Route::post('/language/{locale}', [InstallController::class, 'setLanguage'])->name('language');
        
        Route::get('/', [InstallController::class, 'index'])->name('index');

        //基本設定
        Route::get('/settings', [InstallController::class, 'create'])->name('settings');
        Route::post('/settings', [InstallController::class, 'storeSettings'])->name('settings.store');

        //環境設定
        Route::get('/environment', [InstallController::class, 'environment'])->name('environment');
        Route::post('/environment', [InstallController::class, 'storeEnvironment'])->name('environment.store');

        //セキュリティ設定
        Route::get('/security', [InstallController::class, 'security'])->name('security');
        Route::post('/security', [InstallController::class, 'storeSecurity'])->name('security.store');

        //データベース設定
        Route::get('/database', [InstallController::class, 'database'])->name('database');
        Route::post('/database', [InstallController::class, 'storeDatabase'])->name('database.store');

        //確認画面
        Route::get('/confirm', [InstallController::class, 'confirm'])->name('confirm');
        Route::post('/confirm', [InstallController::class, 'confirmStore'])->name('confirm.store');

        //インストール完了
        Route::post('/finalize', [InstallController::class, 'finalizeInstall'])->name('finalize');
        //インストール完了画面
        Route::get('/complete', [InstallController::class, 'complete'])->name('complete');

        //データベース接続テスト
        Route::post('/test-db', [InstallController::class, 'testDatabaseConnection'])->name('install.test-db');
    }
);
