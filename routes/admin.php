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
use App\Http\Controllers\Admin\Settings\AdminSettingsPluginController;
use App\Http\Controllers\Admin\Settings\AdminSettingsSecurityController;
use App\Http\Controllers\Admin\Settings\AdminSettingsAdminsController;
use App\Http\Controllers\Admin\Users\AdminUsersController;
use App\Http\Controllers\Admin\Contents\AdminContentsPageController;
use App\Http\Controllers\Admin\Contents\AdminContentsThemesController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\SettingSecurity;

$adminUrl = SettingSecurity::get('admin_url', config('security.admin_url'));

Route::prefix($adminUrl)->name('admin.')
    ->middleware('admin.ip') // IPアドレスフィルタ
    ->group(function () {
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


            //Contents
            // Pages
            // ページマスター
            Route::get('/contents/pages', [AdminContentsPageController::class, 'index'])->name('contents.pages.index');
            // ページ作成
            Route::get('/contents/pages/create', [AdminContentsPageController::class, 'create'])->name('contents.pages.create');
            // ページ編集
            Route::get('/contents/pages/edit/{page}', [AdminContentsPageController::class, 'edit'])->name('contents.pages.edit');
            // ページ保存
            Route::post('/contents/pages/store', [AdminContentsPageController::class, 'store'])->name('contents.pages.store');
            // ページ更新
            Route::patch('/contents/pages/update/{page}', [AdminContentsPageController::class, 'update'])->name('contents.pages.update');
            // ページ削除
            Route::delete('/contents/pages/delete/{page}', [AdminContentsPageController::class, 'destroy'])->name('contents.pages.destroy');

            // テーマ一覧表示
            Route::get('/contents/themes', [AdminContentsThemesController::class, 'index'])->name('contents.themes.index');
            // テーマのインストール
            Route::get('/contents/themes/install', [AdminContentsThemesController::class, 'install'])->name('contents.themes.install');
            // テーマアップロード
            Route::post('/contents/themes/upload', [AdminContentsThemesController::class, 'upload'])->name('contents.themes.upload');
            // テーマの有効化（切り替え）
            Route::post('/contents/themes/activate/{id}', [AdminContentsThemesController::class, 'activate'])->name('contents.themes.activate');
            // テーマ削除
            Route::post('/contents/themes/delete/{id}', [AdminContentsThemesController::class, 'delete'])->name('contents.themes.delete');

            //Users
            // ユーザーマスター
            Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
            // ユーザー作成
            Route::get('/users/create', [AdminUsersController::class, 'create'])->name('users.create');
            // ユーザー編集
            Route::get('users/edit/{user}', [AdminUsersController::class, 'edit'])->name('users.edit');
            // ユーザー保存
            Route::post('/users/store', [AdminUsersController::class, 'store'])->name('users.store');
            // ユーザー更新
            Route::patch('users/update/{user}', [AdminUsersController::class, 'update'])->name('users.update');
            // ユーザー削除
            Route::delete('/users/delete/{user}', [AdminUsersController::class, 'destroy'])->name('users.destroy');

            // Settings
            // Admins
            Route::get('/settings/admins', [AdminSettingsAdminsController::class, 'index'])->name('settings.admins.index');
            Route::get('/settings/admins/create', [AdminSettingsAdminsController::class, 'create'])->name('settings.admins.create');
            Route::post('/settings/admins/store', [AdminSettingsAdminsController::class, 'store'])->name('settings.admins.store');
            Route::get('/settings/admins/edit/{admin}', [AdminSettingsAdminsController::class, 'edit'])->name('settings.admins.edit');
            Route::patch('/settings/admins/update/{admin}', [AdminSettingsAdminsController::class, 'update'])->name('settings.admins.update');
            Route::delete('/settings/admins/destroy/{admin}', [AdminSettingsAdminsController::class, 'destroy'])->name('settings.admins.destroy');
            Route::get('/settings/admins/profile', [AdminSettingsAdminsController::class, 'profile'])->name('settings.admins.profile');

            // Plugins
            Route::get('/settings/plugins', [AdminSettingsPluginController::class, 'index'])->name('settings.plugins.index');
            Route::get('/settings/plugins/install', [AdminSettingsPluginController::class, 'install'])->name('settings.plugins.install');
            Route::post('/settings/plugins/upload', [AdminSettingsPluginController::class, 'upload'])->name('settings.plugins.upload');
            Route::post('/enable/{id}', [AdminSettingsPluginController::class, 'enable'])->name('settings.plugins.enable');
            Route::post('/disable/{id}', [AdminSettingsPluginController::class, 'disable'])->name('settings.plugins.disable');
            Route::post('/uninstall/{id}', [AdminSettingsPluginController::class, 'uninstall'])->name('settings.plugins.uninstall');

            // Security
            Route::get('/settings/security', [AdminSettingsSecurityController::class, 'index'])->name('settings.security.index');
            Route::post('settings/security', [AdminSettingsSecurityController::class, 'update'])->name('settings.security.update');

            // Systems
            Route::get('/admin/settings/systems', [AdminSettingsSystemsController::class, 'index'])->name('settings.systems');
            Route::put('/admin/settings/update', [AdminSettingsSystemsController::class, 'update'])->name('settings.systems.update');

            // Logout
            Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
        });
    });
