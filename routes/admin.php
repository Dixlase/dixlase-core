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


use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\Front\AdminFrontController;
use App\Http\Controllers\Admin\Media\AdminMediaController;
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
use App\Http\Controllers\Admin\Settings\AdminThemesSettingsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\SecuritySetting;


$adminUrl = SecuritySetting::get('admin_url', config('security.admin_url'));

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
            // ダッシュボード
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');


            //フロントページ管理
            Route::get('/front', [AdminFrontController::class, 'index'])->name('front.index');
            //フロントページ管理保存
            Route::post('/front/store', [AdminFrontController::class, 'index'])->name('front.store');
            //フロントページ管理削除
            Route::delete('/front/delete/{front}', [AdminFrontController::class, 'index'])->name('front.delete');

            //メディア管理
            Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
            //メディアアップロード
            Route::get('/media/upload', [AdminMediaController::class, 'upload'])->name('media.upload');
            Route::post('/media/upload/', [AdminMediaController::class, 'index'])->name('media.upload');
            //メディア削除
            Route::delete('/media/delete/{media}', [AdminMediaController::class, 'delete'])->name('media.delete');
            //メディアダウンロード
            Route::get('/media/download/{media}', [AdminMediaController::class, 'download'])->name('media.download');
            //メディアプレビュー
            Route::get('/media/preview/{media}', [AdminMediaController::class, 'preview'])->name('media.preview');

            // ユーザー
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
            // Base
            Route::get('/settings/base', [AdminBaseSettingsController::class, 'index'])->name('settings.base.index');
            Route::put('/settings/update', [AdminBaseSettingsController::class, 'update'])->name('settings.systems.update');

            // Security
            Route::get('/settings/security', [AdminSecuritySettingsController::class, 'index'])->name('settings.security.index');
            Route::post('settings/security', [AdminSecuritySettingsController::class, 'update'])->name('settings.security.update');

            // Members
            // メンバーマスター
            Route::get('/settings/members', [AdminMembersSettingsController::class, 'index'])->name('settings.members.index');
            // メンバー作成
            Route::get('/settings/members/create', [AdminMembersSettingsController::class, 'create'])->name('settings.members.create');
            // メンバー保存
            Route::post('/settings/members/store', [AdminMembersSettingsController::class, 'store'])->name('settings.members.store');
            // メンバー編集
            Route::get('/settings/members/edit/{member}', [AdminMembersSettingsController::class, 'edit'])->name('settings.members.edit');
            // メンバー更新
            Route::patch('/settings/members/update/{member}', [AdminMembersSettingsController::class, 'update'])->name('settings.members.update');
            // メンバー削除
            Route::delete('/settings/members/destroy/{member}', [AdminMembersSettingsController::class, 'destroy'])->name('settings.members.destroy');
            // プロフィール
            Route::get('/settings/members/profile', [AdminMembersSettingsController::class, 'profile'])->name('settings.members.profile');

            // Themes
            Route::get('/settings/themes', [AdminThemesSettingsController::class, 'index'])->name('settings.themes.index');
            Route::get('/settings/themes/install', [AdminThemesSettingsController::class, 'install'])->name('settings.themes.install');
            Route::post('/settings/themes/upload', [AdminThemesSettingsController::class, 'upload'])->name('settings.themes.upload');
            Route::post('/settings/themes/activate/{id}', [AdminThemesSettingsController::class, 'activate'])->name('settings.themes.activate');
            Route::post('/settings/themes/delete/{id}', [AdminThemesSettingsController::class, 'delete'])->name('settings.themes.delete');

            // Plugins
            Route::get('/settings/plugins', [AdminPluginsSettingsController::class, 'index'])->name('settings.plugins.index');
            Route::get('/settings/plugins/install', [AdminPluginsSettingsController::class, 'install'])->name('settings.plugins.install');
            Route::post('/settings/plugins/upload', [AdminPluginsSettingsController::class, 'upload'])->name('settings.plugins.upload');
            Route::post('/enable/{id}', [AdminPluginsSettingsController::class, 'enable'])->name('settings.plugins.enable');
            Route::post('/disable/{id}', [AdminPluginsSettingsController::class, 'disable'])->name('settings.plugins.disable');
            Route::post('/uninstall/{id}', [AdminPluginsSettingsController::class, 'uninstall'])->name('settings.plugins.uninstall');


            // Logout
            Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
        });
    });
