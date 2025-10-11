<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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
use Themes\DixlaseDefaultTheme\App\Http\Controllers\Admin\Settings\AdminThemeSettingsController;
use App\Helpers\AdminHelper;

// 管理画面のURLを取得
$adminUrl = AdminHelper::getAdminUrl();

// DixlaseDefaultTheme 設定ルート
Route::prefix($adminUrl)->name('admin.')
    ->middleware(['admin.ip']) // IPアドレスフィルタのみを先に適用
    ->group(function () {
        // 認証済みルート
        Route::middleware([
            'auth:member',
            'log.admin.activity',
            'check.menu.access:themes',
            'check.menu.edit:themes'
        ])->group(function () {
            Route::get('/settings/themes/settings', [AdminThemeSettingsController::class, 'settings'])
                ->name('settings.themes.settings');
            
            Route::put('/settings/themes/settings', [AdminThemeSettingsController::class, 'update'])
                ->name('settings.themes.settings.update');
        });
    });
