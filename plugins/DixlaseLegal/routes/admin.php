<?php

/**
 * This file is part of Dixlase Legal.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

use Illuminate\Support\Facades\Route;
use Plugins\DixlaseLegal\App\Http\Controllers\Admin\DixlaseLegalAdminContentsController;
use Plugins\DixlaseLegal\App\Http\Controllers\Admin\DixlaseLegalAdminLegalPagesController;
use Plugins\DixlaseLegal\App\Http\Controllers\Admin\DixlaseLegalAdminSettingsController;

/*
|--------------------------------------------------------------------------
| DixlaseLegal Admin Routes
|--------------------------------------------------------------------------
|
| 管理画面用のルート定義
|
| 注意: このファイルは自動的に以下のミドルウェアが適用されます
| - web: セッション、CSRF保護
| - auth:member: 管理者認証
| - admin.ip: 管理画面IPアドレス制限
|
*/

Route::prefix('legal-pages')
    ->name('dixlase-legal::admin.legal-pages.')
    ->group(function () {
        // URL設定
        Route::get('/', [DixlaseLegalAdminLegalPagesController::class, 'index'])->name('index');
        Route::patch('/', [DixlaseLegalAdminLegalPagesController::class, 'update'])->name('update');

        // コンテンツ管理
        Route::get('/contents', [DixlaseLegalAdminContentsController::class, 'index'])->name('contents.index');
        Route::get('/contents/{slug}/{lang}/edit', [DixlaseLegalAdminContentsController::class, 'edit'])->name('contents.edit');
        Route::patch('/contents/{slug}/{lang}', [DixlaseLegalAdminContentsController::class, 'update'])->name('contents.update');

        // 設定
        Route::get('/settings', [DixlaseLegalAdminSettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings', [DixlaseLegalAdminSettingsController::class, 'update'])->name('settings.update');
    });
