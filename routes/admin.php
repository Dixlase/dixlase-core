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

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\AdminConfirmablePasswordController;
use App\Http\Controllers\Admin\Auth\AdminEmailVerificationNotificationController;
use App\Http\Controllers\Admin\Auth\AdminEmailVerificationPromptController;
use App\Http\Controllers\Admin\Auth\AdminNewPasswordController;
use App\Http\Controllers\Admin\Auth\AdminPasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\AdminRegisteredUserController;
use App\Http\Controllers\Admin\Auth\AdminVerifyEmailController;
use App\Http\Controllers\Admin\Settings\AdminSettingsSystemsController;
use App\Http\Controllers\Admin\Admins\AdminAdminsController;
use App\Http\Controllers\Admin\Users\AdminUsersController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        $user = Auth::guard('admin')->user();
        if ($user) {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('admin.login');
        }
    });

    Route::get('/login', [AdminAuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [AdminAuthenticatedSessionController::class, 'store']);


    Route::middleware('auth:admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        //Users
        Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUsersController::class, 'create'])->name('users.create');
        Route::get('/users/edit', [AdminUsersController::class, 'edit'])->name('users.edit');
        Route::post('/users/store', [AdminUsersController::class, 'store'])->name('users.store');
        Route::get('/users/update', [AdminUsersController::class, 'update'])->name('users.update');
        Route::get('/users/delete', [AdminUsersController::class, 'delete'])->name('users.delete');

        // Settings
        // Admins
        Route::get('/settings/admins', [AdminAdminsController::class, 'index'])->name('settings.admins.index');
        Route::get('/settings/admins/create', [AdminAdminsController::class, 'create'])->name('settings.admins.create');
        Route::get('/settings/admins/edit', [AdminAdminsController::class, 'edit'])->name('settings.admins.edit');


        Route::put('/settings/admins/update', [AdminAdminsController::class, 'update'])->name('settings.admins.update');
        Route::put('/settings/admins/delete', [AdminAdminsController::class, 'delete'])->name('settings.admins.delete');
        Route::get('/settings/admins/profile', [AdminAdminsController::class, 'profile'])->name('settings.admins.profile');

        // Systems
        Route::get('/admin/settings/systems', [AdminSettingsSystemsController::class, 'index'])->name('settings.systems');
        Route::put('/admin/settings/update', [AdminSettingsSystemsController::class, 'update'])->name('settings.systems.update');

        // Logout
        Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });
});
