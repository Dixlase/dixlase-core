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
use App\Http\Controllers\Admin\Settings\Base;
use App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController;
use App\Http\Controllers\Admin\Settings\AdminSecuritySettingsController;
use App\Http\Controllers\Admin\Settings\Security;
use App\Http\Controllers\Admin\Settings\Systems;
use App\Http\Controllers\Admin\Members;
use App\Http\Controllers\Admin\Settings\AdminThemesSettingsController;
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
                //フロントページ編集
                Route::get('/front/edit', [AdminFrontController::class, 'edit'])->name('front.edit');
                Route::put('/front/edit', [AdminFrontController::class, 'updateEdit'])
                    ->middleware('check.menu.edit:front')
                    ->name('front.edit.update');
                Route::get('/front/edit/content/{storageType}/{editorType}', [AdminFrontController::class, 'getContent'])->name('front.edit.content');
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
            Route::middleware('check.menu.access:settings.base')->prefix('settings/base')->name('settings.base.')->group(function () {
                // 概要
                Route::get('/', [Base\AdminBaseIndexController::class, 'index'])->name('index');
                
                // サイト設定
                Route::get('/site', [Base\AdminBaseSiteController::class, 'index'])->name('site');
                Route::post('/site', [Base\AdminBaseSiteController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base')
                    ->name('site.update');
                
                // 管理画面設定
                Route::get('/admin', [Base\AdminBaseAdminController::class, 'index'])->name('admin');
                Route::post('/admin', [Base\AdminBaseAdminController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base')
                    ->name('admin.update');
                
                // メール設定
                Route::get('/mail', [Base\AdminBaseMailController::class, 'index'])->name('mail');
                Route::post('/mail', [Base\AdminBaseMailController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base')
                    ->name('mail.update');
                Route::post('/mail/test-mail', [Base\AdminBaseMailController::class, 'testMail'])->name('mail.test-mail');
                Route::post('/mail/test-connection', [Base\AdminBaseMailController::class, 'testConnection'])->name('mail.test-connection');
                Route::post('/mail/clear-test-session', [Base\AdminBaseMailController::class, 'clearTestSession'])->name('mail.clear-test-session');
                Route::get('/mail/check-test-session', [Base\AdminBaseMailController::class, 'checkTestSession'])->name('mail.check-test-session');
                Route::get('/mail/verify-mail/{token}', [Base\AdminBaseMailController::class, 'verifyMail'])->name('mail.verify-mail');
                Route::get('/mail/mail-verification-success', [Base\AdminBaseMailController::class, 'mailVerificationSuccess'])->name('mail.mail-verification-success');
                
                // メンテナンス設定
                Route::get('/maintenance', [Base\AdminBaseMaintenanceController::class, 'index'])->name('maintenance');
                Route::post('/maintenance', [Base\AdminBaseMaintenanceController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base')
                    ->name('maintenance.update');
            });

            // セキュリティ設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.security')->prefix('settings/security')->name('settings.security.')->group(function () {
                // 概要
                Route::get('/', [Security\AdminSecurityIndexController::class, 'index'])->name('index');
                
                // 認証・セッション
                Route::get('/auth', [Security\AdminSecurityAuthController::class, 'index'])->name('auth');
                Route::post('/auth', [Security\AdminSecurityAuthController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security')
                    ->name('auth.update');
                
                // CAPTCHA
                Route::get('/captcha', [Security\AdminSecurityCaptchaController::class, 'index'])->name('captcha');
                Route::post('/captcha', [Security\AdminSecurityCaptchaController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security')
                    ->name('captcha.update');
                Route::post('/captcha/validate-widget', [Security\AdminSecurityCaptchaController::class, 'validateWidget'])->name('captcha.validate-widget');
                Route::post('/captcha/clear-test', [Security\AdminSecurityCaptchaController::class, 'clearTest'])->name('captcha.clear-test');
                
                // IPアクセス制御
                Route::get('/ip', [Security\AdminSecurityIpController::class, 'index'])->name('ip');
                Route::post('/ip', [Security\AdminSecurityIpController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security')
                    ->name('ip.update');
                
                // 拡張機能セキュリティ
                Route::get('/extensions', [Security\AdminSecurityExtensionsController::class, 'index'])->name('extensions');
                Route::post('/extensions', [Security\AdminSecurityExtensionsController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security')
                    ->name('extensions.update');
                
                // CSP
                Route::get('/csp', [Security\AdminSecurityCspController::class, 'index'])->name('csp');
                Route::post('/csp', [Security\AdminSecurityCspController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security')
                    ->name('csp.update');
                
                // 通知
                Route::get('/notifications', [Security\AdminSecurityNotificationsController::class, 'index'])->name('notifications');
                Route::post('/notifications', [Security\AdminSecurityNotificationsController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security')
                    ->name('notifications.update');
                
                // ファイル整合性
                Route::get('/integrity', [Security\AdminSecurityIntegrityController::class, 'index'])->name('integrity');
                Route::post('/integrity/scan', [Security\AdminSecurityIntegrityController::class, 'scan'])
                    ->middleware('check.menu.edit:settings.security')
                    ->name('integrity.scan');
                Route::post('/integrity/regenerate-baseline', [Security\AdminSecurityIntegrityController::class, 'regenerateBaseline'])
                    ->middleware('check.menu.edit:settings.security')
                    ->name('integrity.regenerate-baseline');
                Route::get('/integrity/{audit}', [Security\AdminSecurityIntegrityController::class, 'show'])->name('integrity.show');

            });
            // メンバー管理（権限チェック付き）
            Route::middleware('check.menu.access:members')->prefix('members')->name('members.')->group(function () {
                // メンバー一覧・CRUD
                Route::get('/', [Members\AdminMemberController::class, 'index'])->name('index');
                Route::get('/create', [Members\AdminMemberController::class, 'create'])->name('create');
                Route::post('/', [Members\AdminMemberController::class, 'store'])->name('store');
                Route::get('/edit/{member}', [Members\AdminMemberController::class, 'edit'])->name('edit');
                Route::patch('/update/{member}', [Members\AdminMemberController::class, 'update'])->name('update');
                Route::delete('/destroy/{member}', [Members\AdminMemberController::class, 'destroy'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('destroy');

                // セキュリティ操作
                Route::delete('/passkey/{member}/{credentialId}', [Members\AdminMemberController::class, 'revokePasskey'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('passkey.revoke');
                Route::delete('/recovery-codes/{member}', [Members\AdminMemberController::class, 'revokeRecoveryCodes'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('recovery-codes.revoke');
                Route::post('/force-logout/{member}', [Members\AdminMemberController::class, 'forceLogout'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('force-logout');
                Route::post('/unlock-2fa/{member}', [Members\AdminMemberController::class, 'unlock2fa'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('unlock-2fa');
                Route::post('/force-logout-all', [Members\AdminMemberSettingsController::class, 'forceLogoutAll'])
                    ->middleware('check.menu.edit:members.settings')
                    ->name('force-logout-all');
                Route::post('/{member}/send-verification-email', [Members\AdminMemberController::class, 'sendVerificationEmail'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('send-verification-email');

                // メンバー設定
                Route::get('/settings', [Members\AdminMemberSettingsController::class, 'index'])->name('settings');
                Route::get('/settings/password', [Members\AdminMemberPasswordController::class, 'index'])->name('settings.password');
                Route::get('/settings/session', [Members\AdminMemberSessionController::class, 'index'])->name('settings.session');
                Route::get('/settings/auth', [Members\AdminMemberAuthController::class, 'index'])->name('settings.auth');
                Route::get('/settings/roles', [Members\AdminMemberRolesController::class, 'index'])->name('settings.roles');

                Route::post('/settings/password', [Members\AdminMemberPasswordController::class, 'update'])
                    ->middleware('check.menu.edit:members.settings')
                    ->name('settings.password.update');
                Route::post('/settings/session', [Members\AdminMemberSessionController::class, 'update'])
                    ->middleware('check.menu.edit:members.settings')
                    ->name('settings.session.update');
                Route::post('/settings/auth', [Members\AdminMemberAuthController::class, 'update'])
                    ->middleware('check.menu.edit:members.settings')
                    ->name('settings.auth.update');
                Route::post('/settings/roles', [Members\AdminMemberRolesController::class, 'update'])
                    ->middleware('check.menu.edit:members.roles')
                    ->name('settings.roles.update');
            });

            // テーマ設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.themes')->group(function () {
                Route::get('/settings/themes', [AdminThemesSettingsController::class, 'index'])->name('settings.themes.index');
                Route::get('/settings/themes/add', [AdminThemesSettingsController::class, 'add'])->name('settings.themes.add');
                Route::post('/settings/themes/upload', [AdminThemesSettingsController::class, 'upload'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.upload');
                Route::post('/settings/themes/install', [AdminThemesSettingsController::class, 'install'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.install');
                Route::post('/settings/themes/uninstall/{id}', [AdminThemesSettingsController::class, 'uninstall'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.uninstall');
                Route::post('/settings/themes/switch/{id}', [AdminThemesSettingsController::class, 'switch'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.switch');
                Route::post('/settings/themes/delete', [AdminThemesSettingsController::class, 'delete'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.delete');
                Route::post('/settings/themes/audit', [AdminThemesSettingsController::class, 'audit'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.audit');
            });

            // プラグイン設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.plugins')->group(function () {
                Route::get('/settings/plugins', [AdminPluginsSettingsController::class, 'index'])->name('settings.plugins.index');
                Route::get('/settings/plugins/add', [AdminPluginsSettingsController::class, 'add'])->name('settings.plugins.add');
                Route::post('/settings/plugins/upload', [AdminPluginsSettingsController::class, 'upload'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.upload');
                Route::post('/settings/plugins/install', [AdminPluginsSettingsController::class, 'install'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.install');
                Route::post('/settings/plugins/uninstall/{id}', [AdminPluginsSettingsController::class, 'uninstall'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.uninstall');
                Route::post('/settings/plugins/enable/{id}', [AdminPluginsSettingsController::class, 'enable'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.enable');
                Route::post('/settings/plugins/disable/{id}', [AdminPluginsSettingsController::class, 'disable'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.disable');
                Route::post('/settings/plugins/delete', [AdminPluginsSettingsController::class, 'delete'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.delete');
                Route::post('/settings/plugins/audit', [AdminPluginsSettingsController::class, 'audit'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.audit');
            });

            // システム設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.systems')->prefix('settings/systems')->name('settings.systems.')->group(function () {
                // API管理
                Route::get('/api', [Systems\AdminSystemApiController::class, 'index'])->name('api');
                Route::post('/api', [Systems\AdminSystemApiController::class, 'update'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('api.update');
                Route::post('/api/generate-key', [Systems\AdminSystemApiController::class, 'generateKey'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('api.generate-key');
                Route::delete('/api/revoke-key/{id}', [Systems\AdminSystemApiController::class, 'revokeKey'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('api.revoke-key');
                Route::post('/api/regenerate-key/{id}', [Systems\AdminSystemApiController::class, 'regenerateKey'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('api.regenerate-key');

                // キャッシュ管理
                Route::get('/cache', [Systems\AdminSystemCacheController::class, 'index'])->name('cache');
                Route::post('/cache/clear', [Systems\AdminSystemCacheController::class, 'clear'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('cache.clear');

                // データベース管理
                Route::get('/database', [Systems\AdminSystemDatabaseController::class, 'index'])->name('database');
                Route::post('/database/cleanup', [Systems\AdminSystemDatabaseController::class, 'cleanup'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('database.cleanup');
                
                // 監査ログ（/logs/ がデフォルト）
                Route::get('/logs', [Systems\AdminSystemLogsController::class, 'auditIndex'])->name('logs');
                
                // ファイルログ
                Route::get('/logs/files/{type?}', [Systems\AdminSystemLogsController::class, 'index'])->name('logs.files');
                Route::get('/logs/files/{type}/download', [Systems\AdminSystemLogsController::class, 'download'])->name('logs.download');
                Route::post('/logs/files/{type}/clear', [Systems\AdminSystemLogsController::class, 'clear'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('logs.clear');
                Route::post('/logs/files/test', [Systems\AdminSystemLogsController::class, 'test'])->name('logs.test');
                Route::post('/logs/files/test-error', [Systems\AdminSystemLogsController::class, 'testError'])->name('logs.test-error');
                Route::post('/logs/files/test-front', [Systems\AdminSystemLogsController::class, 'testFront'])->name('logs.test-front');
                Route::post('/logs/files/test-front-error', [Systems\AdminSystemLogsController::class, 'testFrontError'])->name('logs.test-front-error');
                Route::get('/logs/audit/{id}', [Systems\AdminSystemLogsController::class, 'auditShow'])->name('logs.audit.show');
                Route::get('/logs/audit-export', [Systems\AdminSystemLogsController::class, 'auditExport'])->name('logs.audit.export');
                Route::post('/logs/audit/cleanup', [Systems\AdminSystemLogsController::class, 'auditCleanup'])
                    ->middleware('check.menu.edit:settings.systems')
                    ->name('logs.audit.cleanup');
                
                // システム情報
                Route::get('/info', [Systems\AdminSystemInfoController::class, 'index'])->name('info');
            });

            // ログアウト
            Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');
            
            // 有効化されているプラグインの管理画面ルートを自動読み込み
            \App\Helpers\PluginHelper::loadEnabledAdminRoutes();
            
            // 有効化されているテーマの管理画面ルートを自動読み込み
            \App\Helpers\ThemeHelper::loadEnabledThemeAdminRoutes();
        });
    });
