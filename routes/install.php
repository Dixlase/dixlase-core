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


Route::prefix('install')->name('install.')->group(
    function () {
        Route::get('/', [InstallController::class, 'index'])->name('index');
        Route::get('/settings', [InstallController::class, 'create'])->name('settings');
        Route::post('/settings', [InstallController::class, 'store'])->name('settings.store');
        Route::get('/confirm', [InstallController::class, 'confirm'])->name('confirm');
        Route::post('/confirm', [InstallController::class, 'confirmStore'])->name('confirm.store');
        Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
    }
);
