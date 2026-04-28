<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use App\Http\Controllers\Install\InstallCompleteController;
use App\Http\Controllers\Install\InstallConfirmController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\Install\InstallDatabaseController;
use App\Http\Controllers\Install\InstallEnvironmentController;
use App\Http\Controllers\Install\InstallMailController;
use App\Http\Controllers\Install\InstallModeController;
use App\Http\Controllers\Install\InstallSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('install')->name('install.')->middleware('install.steps')->group(
    function () {
        // 言語切り替え（AJAX専用）
        Route::post('/language/{locale}', [InstallSettingsController::class, 'setLanguage'])->name('language');

        Route::get('/', [InstallController::class, 'index'])->name('index');

        // モード選択
        Route::get('/mode', [InstallModeController::class, 'create'])->name('mode');
        Route::post('/mode', [InstallModeController::class, 'store'])->name('mode.store');

        // 基本設定
        Route::get('/settings', [InstallSettingsController::class, 'create'])->name('settings');
        Route::post('/settings', [InstallSettingsController::class, 'store'])->name('settings.store');

        // 環境設定
        Route::get('/environment', [InstallEnvironmentController::class, 'create'])->name('environment');
        Route::post('/environment', [InstallEnvironmentController::class, 'store'])->name('environment.store');

        // データベース設定
        Route::get('/database', [InstallDatabaseController::class, 'create'])->name('database');
        Route::post('/database', [InstallDatabaseController::class, 'store'])->name('database.store');

        // メールサーバー設定
        Route::get('/mail', [InstallMailController::class, 'create'])->name('mail');
        Route::post('/mail', [InstallMailController::class, 'store'])->name('mail.store');

        // メールテスト関連のルート
        Route::post('/test-mail-connection', [InstallMailController::class, 'testConnection'])->name('mail.test-connection');
        Route::post('/test-mail-send', [InstallMailController::class, 'testSend'])->name('mail.test-send');
        Route::get('/verify-mail/{token}', [InstallMailController::class, 'verify'])->name('mail.verify-mail');
        Route::post('/reset-mail-tests', [InstallMailController::class, 'resetTests'])->name('mail.reset-tests');

        // 確認画面
        Route::get('/confirm', [InstallConfirmController::class, 'show'])->name('confirm');
        Route::post('/confirm', [InstallConfirmController::class, 'store'])->name('confirm.store');
        // インストール完了画面
        Route::get('/complete', [InstallCompleteController::class, 'show'])->name('complete');

        // インストール最終化
        Route::post('/finalize', [InstallCompleteController::class, 'finalize'])->name('finalize');

        // データベース接続テスト
        Route::post('/test-db', [InstallDatabaseController::class, 'testConnection'])->name('install.test-db');
    }
);
