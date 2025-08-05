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
use App\Http\Controllers\Admin\AdminLoginController;
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
use App\Http\Controllers\Admin\Settings\AdminThemesSettingsController;
use App\Http\Controllers\Admin\Settings\AdminSystemsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Helpers\AdminHelper;


//管理画面のURLを取得
$adminUrl = AdminHelper::getAdminUrl();

Route::prefix($adminUrl)->name('admin.')
    ->middleware(['admin.ip']) // IPアドレスフィルタのみを先に適用
    ->group(function () {
        Route::get('/', function () {
            $member = Auth::guard('member')->user();
            if ($member) {
                return redirect()->route('admin.dashboard');
            } else {
                return redirect()->route('admin.login');
            }
        });

        // ログイン
        Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'store'])->name('login.store');

        // 二段階認証
        Route::get('/two-factor-challenge', [AdminLoginController::class, 'showTwoFactorForm'])->name('two-factor.login');
        Route::post('/two-factor-challenge', [AdminLoginController::class, 'confirmTwoFactor'])->name('two-factor.confirm');
        Route::post('/two-factor-resend', [AdminLoginController::class, 'resendTwoFactorCode'])->name('two-factor.resend');


        // 認証済みルート
        Route::middleware([
            'auth:member', 
            'log.admin.activity',
            'check.menu.access:menu_key', 
            'check.menu.edit:menu_key'
        ])->group(function () {

            // ダッシュボード
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            //フロントページマスター
            Route::get('/front', [AdminFrontController::class, 'index'])->name('front.index');
            //フロントページデザイン
            Route::get('/front/design', [AdminFrontController::class, 'design'])->name('front.design');
            Route::post('/front/design', [AdminFrontController::class, 'design'])->name('front.design.store');
            //フロントページ管理
            Route::get('/front/settings', [AdminFrontController::class, 'index'])->name('front.settings');
            Route::post('/front/settings', [AdminFrontController::class, 'index'])->name('front.settings.store');

            //メディア管理
            Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
            //メディアアップロード
            Route::get('/media/upload', [AdminMediaController::class, 'upload'])->name('media.upload');
            Route::post('/media/upload/', [AdminMediaController::class, 'store'])->name('media.upload');
            //メディア削除
            Route::delete('/media/delete/{media}', [AdminMediaController::class, 'delete'])->name('media.delete');
            //メディアダウンロード
            Route::get('/media/download/{media}', [AdminMediaController::class, 'download'])->name('media.download');
            //メディアプレビュー
            Route::get('/media/preview/{media}', [AdminMediaController::class, 'preview'])->name('media.preview');
            //メディア設定
            Route::get('/media/settings', [AdminMediaController::class, 'settings'])->name('media.settings');
            Route::post('/media/settings', [AdminMediaController::class, 'update'])->name('media.settings.update');

            // 全体設定
            // 基本設定
            Route::get('/settings/base', [AdminBaseSettingsController::class, 'index'])->name('settings.base');
            Route::put('/settings/update', [AdminBaseSettingsController::class, 'update'])->name('settings.base.update');

            // セキュリティ設定
            Route::get('/settings/security', [AdminSecuritySettingsController::class, 'index'])->name('settings.security');
            Route::post('/settings/security', [AdminSecuritySettingsController::class, 'update'])->name('settings.security.update');

            // メンバー管理
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
            Route::post('/settings/members/profile', [AdminMembersSettingsController::class, 'updateProfile'])->name('settings.members.profile.update');

            // 権限設定
            Route::get('/settings/members/roles/', [AdminMembersSettingsController::class, 'roles'])->name('settings.members.roles');
            Route::post('/settings/members/roles/', [AdminMembersSettingsController::class, 'updateRoles'])->name('settings.members.roles.update');

            // メンバー設定
            Route::get('/settings/members/settings', [AdminMembersSettingsController::class, 'settings'])->name('settings.members.settings');
            Route::post('/settings/members/settings', [AdminMembersSettingsController::class, 'updateSettings'])->name('settings.members.settings.update');

            // テーマ設定
            Route::get('/settings/themes', [AdminThemesSettingsController::class, 'index'])->name('settings.themes.index');
            Route::get('/settings/themes/install', [AdminThemesSettingsController::class, 'install'])->name('settings.themes.install');
            Route::post('/settings/themes/upload', [AdminThemesSettingsController::class, 'upload'])->name('settings.themes.upload');
            Route::post('/settings/themes/activate/{id}', [AdminThemesSettingsController::class, 'activate'])->name('settings.themes.activate');
            Route::post('/settings/themes/delete/{id}', [AdminThemesSettingsController::class, 'delete'])->name('settings.themes.delete');

            // プラグイン設定
            Route::get('/settings/plugins', [AdminPluginsSettingsController::class, 'index'])->name('settings.plugins.index');
            Route::get('/settings/plugins/install', [AdminPluginsSettingsController::class, 'install'])->name('settings.plugins.install');
            Route::post('/settings/plugins/upload', [AdminPluginsSettingsController::class, 'upload'])->name('settings.plugins.upload');
            Route::post('/settings/plugins/enable/{id}', [AdminPluginsSettingsController::class, 'enable'])->name('settings.plugins.enable');
            Route::post('/settings/plugins/disable/{id}', [AdminPluginsSettingsController::class, 'disable'])->name('settings.plugins.disable');
            Route::post('/settings/plugins/uninstall/{id}', [AdminPluginsSettingsController::class, 'uninstall'])->name('settings.plugins.uninstall');

            //ログ情報
            Route::get('/settings/system/logs/{type?}', [AdminSystemsController::class, 'logs'])->name('settings.systems.logs');
            //システム情報
            Route::get('/settings/systems/info', [AdminSystemsController::class, 'info'])->name('settings.systems.info');

            // ログアウト
            Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');
        });
    });
