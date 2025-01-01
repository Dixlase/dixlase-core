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
use App\Http\Controllers\Admin\Settings\AdminBaseSettingsController;
use App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController;
use App\Http\Controllers\Admin\Settings\AdminSecuritySettingsController;
use App\Http\Controllers\Admin\Settings\AdminMembersSettingsController;
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
            $member = Auth::guard('member')->user();
            if ($member) {
                return redirect()->route('admin.dashboard');
            } else {
                return redirect()->route('admin.login');
            }
        });

        Route::get('/login', [AdminAuthenticatedSessionController::class, 'create'])
            ->name('login');

        Route::post('/login', [AdminAuthenticatedSessionController::class, 'store']);

        Route::middleware('auth:member')->group(function () {
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
            // Members
            Route::get('/settings/members', [AdminMembersSettingsController::class, 'index'])->name('settings.members.index');
            Route::get('/settings/members/create', [AdminMembersSettingsController::class, 'create'])->name('settings.members.create');
            Route::post('/settings/members/store', [AdminMembersSettingsController::class, 'store'])->name('settings.members.store');
            Route::get('/settings/members/edit/{member}', [AdminMembersSettingsController::class, 'edit'])->name('settings.members.edit');
            Route::patch('/settings/members/update/{member}', [AdminMembersSettingsController::class, 'update'])->name('settings.members.update');
            Route::delete('/settings/members/destroy/{member}', [AdminMembersSettingsController::class, 'destroy'])->name('settings.members.destroy');
            Route::get('/settings/members/profile', [AdminMembersSettingsController::class, 'profile'])->name('settings.members.profile');

            // Plugins
            Route::get('/settings/plugins', [AdminPluginsSettingsController::class, 'index'])->name('settings.plugins.index');
            Route::get('/settings/plugins/install', [AdminPluginsSettingsController::class, 'install'])->name('settings.plugins.install');
            Route::post('/settings/plugins/upload', [AdminPluginsSettingsController::class, 'upload'])->name('settings.plugins.upload');
            Route::post('/enable/{id}', [AdminPluginsSettingsController::class, 'enable'])->name('settings.plugins.enable');
            Route::post('/disable/{id}', [AdminPluginsSettingsController::class, 'disable'])->name('settings.plugins.disable');
            Route::post('/uninstall/{id}', [AdminPluginsSettingsController::class, 'uninstall'])->name('settings.plugins.uninstall');

            // Security
            Route::get('/settings/security', [AdminSecuritySettingsController::class, 'index'])->name('settings.security.index');
            Route::post('settings/security', [AdminSecuritySettingsController::class, 'update'])->name('settings.security.update');

            // Systems
            Route::get('/settings/base', [AdminBaseSettingsController::class, 'index'])->name('settings.base.index');
            Route::put('/settings/update', [AdminBaseSettingsController::class, 'update'])->name('settings.systems.update');

            // Logout
            Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
        });
    });
