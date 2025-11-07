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
use App\Http\Controllers\Admin\Profile\AdminProfileController;
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

        // 二段階認証（メール）
        Route::get('/two-factor-email', [AdminLoginController::class, 'showEmailForm'])->name('two-factor.email.show');
        Route::post('/two-factor-email/verify', [AdminLoginController::class, 'verifyEmail'])->name('two-factor.email.verify');
        Route::post('/two-factor-email/resend', [AdminLoginController::class, 'resendEmailCode'])->name('two-factor.email.resend');
        
        // Passkey認証
        Route::get('/two-factor-passkey', [AdminLoginController::class, 'showPasskeyForm'])->name('two-factor.passkey.show');
        Route::post('/two-factor-passkey/challenge', [AdminLoginController::class, 'getPasskeyChallenge'])->name('two-factor.passkey.challenge');
        Route::post('/two-factor-passkey/verify', [AdminLoginController::class, 'verifyPasskey'])->name('two-factor.passkey.verify');
        
        // 回復コード
        Route::get('/two-factor-recovery', [AdminLoginController::class, 'showRecoveryCodeForm'])->name('two-factor.recovery-code.show');
        Route::post('/two-factor-recovery', [AdminLoginController::class, 'confirmRecoveryCode'])->name('two-factor.recovery-code.confirm');
        


        // パスワードリセット
        Route::get('/forgot-password', [AdminPasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [AdminPasswordResetLinkController::class, 'store'])->name('password.email');
        Route::get('/reset-password/{token}', [AdminNewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/reset-password', [AdminNewPasswordController::class, 'store'])->name('password.store');

        // メール認証（認証不要・署名付きURL）
        Route::get('/verify-mail/{id}/{hash}', [AdminProfileController::class, 'verifyEmail'])
            ->name('verification.verify')
            ->middleware('signed');

        // メール認証通知（ログイン済み・未認証ユーザー向け）
        Route::middleware('auth:member')->group(function () {
            Route::get('/email/verify', [AdminEmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
            Route::post('/email/verification-notification', [AdminEmailVerificationNotificationController::class, 'store'])->name('verification.send');
        });

        // 認証済みルート
        Route::middleware([
            'auth:member',
            'verified',
            'log.admin.activity'
        ])->group(function () {

            // ダッシュボード（全員アクセス可能）
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::post('/dashboard/switch-2fa-method', [AdminDashboardController::class, 'switchToUsedMethod'])->name('dashboard.switch2faMethod');
            Route::post('/dashboard/dismiss-method-change', [AdminDashboardController::class, 'dismissMethodChangeModal'])->name('dashboard.dismissMethodChange');
            
            // フロントページ管理（権限チェック付き）
            Route::middleware('check.menu.access:front')->group(function () {
                //フロントページマスター
                Route::get('/front', [AdminFrontController::class, 'index'])->name('front.index');
                //フロントページデザイン
                Route::get('/front/design', [AdminFrontController::class, 'design'])->name('front.design');
                Route::post('/front/design', [AdminFrontController::class, 'design'])
                    ->middleware('check.menu.edit:front')
                    ->name('front.design.store');
                //フロントページ設定
                Route::get('/front/settings', [AdminFrontController::class, 'settings'])->name('front.settings');
                Route::post('/front/settings', [AdminFrontController::class, 'updateSettings'])
                    ->middleware('check.menu.edit:front')
                    ->name('front.settings.store');
            });

            // メディア管理（権限チェック付き）
            Route::middleware('check.menu.access:media')->group(function () {
                Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
                //メディアAPI（モーダル用）
                Route::get('/media/api', [AdminMediaController::class, 'api'])->name('media.api');
                //メディアアップロード
                Route::get('/media/upload', [AdminMediaController::class, 'upload'])->name('media.upload');
                Route::post('/media/upload/', [AdminMediaController::class, 'store'])
                    ->middleware('check.menu.edit:media')
                    ->name('media.store');
                //メディア削除
                Route::delete('/media/delete/{media}', [AdminMediaController::class, 'delete'])
                    ->middleware('check.menu.edit:media')
                    ->name('media.delete');
                //メディアダウンロード
                Route::get('/media/download/{media}', [AdminMediaController::class, 'download'])->name('media.download');
                //メディアプレビュー
                Route::get('/media/preview/{media}', [AdminMediaController::class, 'preview'])->name('media.preview');
                //メディア情報更新
                Route::put('/media/{media}', [AdminMediaController::class, 'updateMedia'])
                    ->middleware('check.menu.edit:media')
                    ->name('media.update');
                //メディア設定
                Route::get('/media/settings', [AdminMediaController::class, 'settings'])->name('media.settings');
                Route::post('/media/settings', [AdminMediaController::class, 'update'])
                    ->middleware('check.menu.edit:media')
                    ->name('media.settings.update');
            });

            // プロフィール設定（全員アクセス可能）
            Route::get('/profile', [AdminProfileController::class, 'index'])->name('profile');
            Route::post('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
            
            // Passkey管理
            Route::post('/profile/passkey/register-options', [AdminProfileController::class, 'passkeyRegisterOptions'])->name('profile.passkey.register-options');
            Route::post('/profile/passkey/register', [AdminProfileController::class, 'passkeyRegister'])->name('profile.passkey.register');
            Route::delete('/profile/passkey/{credentialId}', [AdminProfileController::class, 'revokePasskey'])->name('profile.passkey.revoke');
            Route::delete('/profile/passkey/all', [AdminProfileController::class, 'revokeAllPasskeys'])->name('profile.passkey.revoke-all');
            
            // 信頼済みデバイス管理
            Route::delete('/profile/trusted-device/{deviceId}', [AdminProfileController::class, 'revokeTrustedDevice'])->name('profile.trusted-device.revoke');
            Route::delete('/profile/trusted-device/all', [AdminProfileController::class, 'revokeAllTrustedDevices'])->name('profile.trusted-device.revoke-all');
            
            // 回復コード管理
            Route::post('/profile/recovery-codes/generate', [AdminProfileController::class, 'generateRecoveryCodes'])->name('profile.recovery-codes.generate');
            Route::post('/profile/recovery-codes/regenerate', [AdminProfileController::class, 'regenerateRecoveryCodes'])->name('profile.recovery-codes.regenerate');
            Route::post('/profile/recovery-codes/clear-session', [AdminProfileController::class, 'clearRecoveryCodesSession'])->name('profile.recovery-codes.clear-session');

            // 全体設定
            // 基本設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.base')->group(function () {
                Route::get('/settings/base', [AdminBaseSettingsController::class, 'index'])->name('settings.base');
                Route::put('/settings/base', [AdminBaseSettingsController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base')
                    ->name('settings.base.update');
                Route::post('/settings/base/test-mail', [AdminBaseSettingsController::class, 'testMail'])->name('settings.base.test-mail');
                Route::post('/settings/base/test-connection', [AdminBaseSettingsController::class, 'testConnection'])->name('settings.base.test-connection');
                Route::post('/settings/base/clear-test-session', [AdminBaseSettingsController::class, 'clearTestSession'])->name('settings.base.clear-test-session');
                Route::get('/settings/base/check-test-session', [AdminBaseSettingsController::class, 'checkTestSession'])->name('settings.base.check-test-session');
                Route::get('/settings/base/verify-mail/{token}', [AdminBaseSettingsController::class, 'verifyMail'])->name('settings.base.verify-mail');
                Route::get('/settings/base/mail-verification-success', [AdminBaseSettingsController::class, 'mailVerificationSuccess'])->name('settings.base.mail-verification-success');
            });

            // セキュリティ設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.security')->group(function () {
                Route::get('/settings/security', [AdminSecuritySettingsController::class, 'index'])->name('settings.security');
                Route::post('/settings/security', [AdminSecuritySettingsController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security')
                    ->name('settings.security.update');
                Route::post('/settings/security/test-captcha', [AdminSecuritySettingsController::class, 'testCaptcha'])->name('settings.security.test-captcha');
                Route::post('/settings/security/validate-captcha-widget', [AdminSecuritySettingsController::class, 'validateCaptchaWidget'])->name('settings.security.validate-captcha-widget');
                Route::post('/settings/security/reset-captcha-test', [AdminSecuritySettingsController::class, 'resetCaptchaTest'])->name('settings.security.reset-captcha-test');
                Route::post('/settings/security/clear-captcha-test', [AdminSecuritySettingsController::class, 'clearCaptchaTest'])->name('settings.security.clear-captcha-test');
            });

            // メンバー管理（権限チェック付き）
            Route::middleware('check.menu.access:settings.members')->group(function () {
                // メンバーマスター
                Route::get('/settings/members', [AdminMembersSettingsController::class, 'index'])->name('settings.members.index');
                // メンバー作成
                Route::get('/settings/members/create', [AdminMembersSettingsController::class, 'create'])->name('settings.members.create');
                // メンバー保存
                Route::post('/settings/members/store', [AdminMembersSettingsController::class, 'store'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.store');
                // メンバー編集
                Route::get('/settings/members/edit/{member}', [AdminMembersSettingsController::class, 'edit'])->name('settings.members.edit');
                // メンバー更新
                Route::patch('/settings/members/update/{member}', [AdminMembersSettingsController::class, 'update'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.update');
                // Passkey削除
                Route::delete('/settings/members/passkey/{member}/{credentialId}', [AdminMembersSettingsController::class, 'revokePasskey'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.passkey.revoke');
                // 回復コード削除
                Route::delete('/settings/members/recovery-codes/{member}', [AdminMembersSettingsController::class, 'revokeRecoveryCodes'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.recovery-codes.revoke');
                // メンバー削除
                Route::delete('/settings/members/destroy/{member}', [AdminMembersSettingsController::class, 'destroy'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.destroy');
                // メンバー強制ログアウト
                Route::post('/settings/members/force-logout/{member}', [AdminMembersSettingsController::class, 'forceLogout'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.force-logout');
                // 2FAロックアウト解除
                Route::post('/settings/members/unlock-2fa/{member}', [AdminMembersSettingsController::class, 'unlock2fa'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.unlock-2fa');
                // 全メンバー強制ログアウト
                Route::post('/settings/members/force-logout-all', [AdminMembersSettingsController::class, 'forceLogoutAll'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.force-logout-all');
                // 認証メール送信
                Route::post('/settings/members/{member}/send-verification-email', [AdminMembersSettingsController::class, 'sendVerificationEmail'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.send-verification-email');
                // プロフィール
                Route::get('/settings/members/profile', [AdminMembersSettingsController::class, 'profile'])->name('settings.members.profile');
                Route::post('/settings/members/profile', [AdminMembersSettingsController::class, 'updateProfile'])->name('settings.members.profile.update');

                // 権限設定
                Route::get('/settings/members/roles/', [AdminMembersSettingsController::class, 'roles'])->name('settings.members.roles');
                Route::post('/settings/members/roles/', [AdminMembersSettingsController::class, 'updateRoles'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.roles.update');

                // メンバー設定
                Route::get('/settings/members/settings', [AdminMembersSettingsController::class, 'settings'])->name('settings.members.settings');
                Route::post('/settings/members/settings', [AdminMembersSettingsController::class, 'updateSettings'])
                    ->middleware('check.menu.edit:settings.members')
                    ->name('settings.members.settings.update');
            });

            // テーマ設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.themes')->group(function () {
                Route::get('/settings/themes', [AdminThemesSettingsController::class, 'index'])->name('settings.themes.index');
                Route::get('/settings/themes/install', [AdminThemesSettingsController::class, 'install'])->name('settings.themes.install');
                Route::post('/settings/themes/upload', [AdminThemesSettingsController::class, 'upload'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.upload');
                Route::post('/settings/themes/activate/{id}', [AdminThemesSettingsController::class, 'activate'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.activate');
                Route::post('/settings/themes/delete/{id}', [AdminThemesSettingsController::class, 'delete'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.delete');
            });

            // プラグイン設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.plugins')->group(function () {
                Route::get('/settings/plugins', [AdminPluginsSettingsController::class, 'index'])->name('settings.plugins.index');
                Route::get('/settings/plugins/install', [AdminPluginsSettingsController::class, 'install'])->name('settings.plugins.install');
                Route::post('/settings/plugins/upload', [AdminPluginsSettingsController::class, 'upload'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.upload');
                Route::post('/settings/plugins/enable/{id}', [AdminPluginsSettingsController::class, 'enable'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.enable');
                Route::post('/settings/plugins/disable/{id}', [AdminPluginsSettingsController::class, 'disable'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.disable');
                Route::post('/settings/plugins/uninstall/{id}', [AdminPluginsSettingsController::class, 'uninstall'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.uninstall');
            });

            // システム設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.systems')->group(function () {
                //キャッシュ管理
                Route::get('/settings/systems/cache', [AdminSystemsController::class, 'cache'])->name('settings.systems.cache');
                Route::post('/settings/systems/cache/clear', [AdminSystemsController::class, 'clearCache'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('settings.systems.cache.clear');

                //データベース管理
                Route::get('/settings/systems/database', [AdminSystemsController::class, 'database'])->name('settings.systems.database');
                Route::post('/settings/systems/database/clean', [AdminSystemsController::class, 'cleanupDatabase'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('settings.systems.database.clean');
                
                //ログ
                Route::get('/settings/system/logs/{type?}', [AdminSystemsController::class, 'logs'])->name('settings.systems.logs');
                Route::get('/settings/system/logs/{type}/download', [AdminSystemsController::class, 'downloadLog'])->name('settings.systems.logs.download');
                Route::post('/settings/system/logs/{type}/clear', [AdminSystemsController::class, 'clearLog'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('settings.systems.logs.clear');
                Route::post('/settings/system/logs/test', [AdminSystemsController::class, 'testLogs'])->name('settings.systems.logs.test');
                Route::post('/settings/system/logs/test-error', [AdminSystemsController::class, 'testErrorLog'])->name('settings.systems.logs.test_error');
                Route::post('/settings/system/logs/test-front', [AdminSystemsController::class, 'testFrontLogs'])->name('settings.systems.logs.test_front');
                Route::post('/settings/system/logs/test-front-error', [AdminSystemsController::class, 'testFrontErrorLog'])->name('settings.systems.logs.test_front_error');
                
                //システム情報
                Route::get('/settings/systems/info', [AdminSystemsController::class, 'info'])->name('settings.systems.info');
            });

            // ログアウト
            Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');
        });
        
        // プラグインの管理画面ルート（一時的に手動で読み込み）
        if (file_exists(base_path('plugins/DixlaseInquiry/routes/admin.php'))) {
            include base_path('plugins/DixlaseInquiry/routes/admin.php');
        }
        if (file_exists(base_path('plugins/DixlasePages/routes/admin.php'))) {
            include base_path('plugins/DixlasePages/routes/admin.php');
        }
        
        // テーマの管理画面ルート
        if (file_exists(base_path('themes/DixlaseDefaultTheme/routes/admin.php'))) {
            include base_path('themes/DixlaseDefaultTheme/routes/admin.php');
        }
    });
