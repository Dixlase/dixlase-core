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

use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\Install\InstallSettingsController;
use App\Http\Controllers\Install\InstallEnvironmentController;
use App\Http\Controllers\Install\InstallDatabaseController;
use App\Http\Controllers\Install\InstallMailController;
use App\Http\Controllers\Install\InstallSecurityController;
use App\Http\Controllers\Install\InstallConfirmController;
use App\Http\Controllers\Install\InstallCompleteController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


Route::prefix('install')->name('install.')->middleware('install.steps')->group(
    function () {
        // 言語切り替え（AJAX専用）
        Route::post('/language/{locale}', [InstallSettingsController::class, 'setLanguage'])->name('language');
        
        Route::get('/', [InstallController::class, 'index'])->name('index');

        //基本設定
        Route::get('/settings', [InstallSettingsController::class, 'create'])->name('settings');
        Route::post('/settings', [InstallSettingsController::class, 'store'])->name('settings.store');

        //環境設定
        Route::get('/environment', [InstallEnvironmentController::class, 'create'])->name('environment');
        Route::post('/environment', [InstallEnvironmentController::class, 'store'])->name('environment.store');

        //データベース設定
        Route::get('/database', [InstallDatabaseController::class, 'create'])->name('database');
        Route::post('/database', [InstallDatabaseController::class, 'store'])->name('database.store');

        //メールサーバー設定
        Route::get('/mail', [InstallMailController::class, 'create'])->name('mail');
        Route::post('/mail', [InstallMailController::class, 'store'])->name('mail.store');
        
        // メールテスト関連のルート
        Route::post('/test-mail-connection', [InstallMailController::class, 'testConnection'])->name('mail.test-connection');
        Route::post('/test-mail-send', [InstallMailController::class, 'testSend'])->name('mail.test-send');
        Route::get('/verify-mail/{token}', [InstallMailController::class, 'verify'])->name('mail.verify-mail');
        Route::post('/reset-mail-tests', [InstallMailController::class, 'resetTests'])->name('mail.reset-tests');

        //セキュリティ設定
        Route::get('/security', [InstallSecurityController::class, 'create'])->name('security');
        Route::post('/security', [InstallSecurityController::class, 'store'])->name('security.store');

        //確認画面
        Route::get('/confirm', [InstallConfirmController::class, 'show'])->name('confirm');
        Route::post('/confirm', [InstallConfirmController::class, 'store'])->name('confirm.store');
        //インストール完了画面
        Route::get('/complete', [InstallCompleteController::class, 'show'])->name('complete');
        
        //インストール最終化
        Route::post('/finalize', [InstallCompleteController::class, 'finalize'])->name('finalize');

        //データベース接続テスト
        Route::post('/test-db', [InstallDatabaseController::class, 'testConnection'])->name('install.test-db');
    }
);
