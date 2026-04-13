<?php

/**
 * This file is part of Dixlase.
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

use App\Helpers\AdminHelper;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminLoginIdentifierCheckController;
use App\Http\Controllers\Admin\AdminPasskeyLoginController;
use App\Http\Controllers\Admin\AdminTwoFaController;
use App\Http\Controllers\Admin\Auth\AdminEmailVerificationNotificationController;
use App\Http\Controllers\Admin\Auth\AdminEmailVerificationPromptController;
use App\Http\Controllers\Admin\Auth\AdminNewPasswordController;
use App\Http\Controllers\Admin\Auth\AdminPasswordResetLinkController;
use App\Http\Controllers\Admin\Front\AdminFrontController;
use App\Http\Controllers\Admin\Media\AdminMediaController;
use App\Http\Controllers\Admin\Members;
use App\Http\Controllers\Admin\Profile\AdminProfileAppearanceController;
use App\Http\Controllers\Admin\Profile\AdminProfileBasicController;
use App\Http\Controllers\Admin\Profile\AdminProfileController;
use App\Http\Controllers\Admin\Profile\AdminProfileNotificationsController;
use App\Http\Controllers\Admin\Profile\AdminProfilePasskeyPromptController;
use App\Http\Controllers\Admin\Profile\AdminProfilePasswordController;
use App\Http\Controllers\Admin\Profile\AdminProfileSidebarController;
use App\Http\Controllers\Admin\Profile\AdminProfileTwoFaController;
use App\Http\Controllers\Admin\Profile\AdminProfileTwoFaManagementController;
use App\Http\Controllers\Admin\SafeModeController;
use App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController;
use App\Http\Controllers\Admin\Settings\AdminThemesSettingsController;
use App\Http\Controllers\Admin\Settings\Base;
use App\Http\Controllers\Admin\Settings\Security;
use App\Http\Controllers\Admin\Settings\Systems;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// 管理画面のURLを取得
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
        Route::middleware([\App\Http\Middleware\CheckLockdown::class.':login'])->group(function () {
            Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
            Route::post('/login', [AdminLoginController::class, 'store'])->name('login.store');
        });

        // ログイン識別子確認（メールアドレス/アカウント名の存在確認）
        Route::post('/login/check-identifier', [AdminLoginIdentifierCheckController::class, 'check'])->name('login.check-identifier');

        // パスキーログイン
        Route::post('/login/passkey/challenge', [AdminPasskeyLoginController::class, 'getChallenge'])->name('login.passkey.challenge');
        Route::post('/login/passkey/verify', [AdminPasskeyLoginController::class, 'verify'])->name('login.passkey.verify');

        // 二段階認証（メール）
        Route::get('/two-fa-email', [AdminTwoFaController::class, 'showEmailChallenge'])->name('two-fa.email.show');
        Route::post('/two-fa-email/verify', [AdminTwoFaController::class, 'verifyEmail'])->name('two-fa.email.verify');
        Route::post('/two-fa-email/resend', [AdminTwoFaController::class, 'resendEmail'])->name('two-fa.email.resend');

        // 回復コード
        Route::get('/two-fa-recovery', [AdminTwoFaController::class, 'showRecoveryCodeChallenge'])->name('two-fa.recovery-code.show');
        Route::post('/two-fa-recovery', [AdminTwoFaController::class, 'verifyRecoveryCode'])->name('two-fa.recovery-code.confirm');

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
            'log.admin.activity',
            \App\Http\Middleware\CheckLockdown::class.':admin',
        ])->group(function () {
            // ダッシュボード（全員アクセス可能）
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::post('/dashboard/dismiss-getting-started', [AdminDashboardController::class, 'dismissGettingStarted'])->name('dashboard.dismiss-getting-started');
            Route::post('/dashboard/visit-getting-started', [AdminDashboardController::class, 'visitGettingStartedStep'])->name('dashboard.visit-getting-started');

            // セーフモード管理（全員アクセス可能）
            Route::post('/safe-mode/disable', [SafeModeController::class, 'disable'])->name('safe-mode.disable');
            Route::post('/safe-mode/disable-all', [SafeModeController::class, 'disableAll'])->name('safe-mode.disable-all');

            // フロントページ管理（権限チェック付き）
            Route::middleware('check.menu.access:front')->group(function () {
                Route::get('/front', [AdminFrontController::class, 'index'])->name('front.index');
                Route::get('/front/create', [AdminFrontController::class, 'create'])->name('front.create');
                Route::post('/front/create', [AdminFrontController::class, 'store'])
                    ->middleware('check.menu.edit:front')->name('front.store');
                Route::get('/front/edit', [AdminFrontController::class, 'edit'])->name('front.edit');
                Route::get('/front/preview-frame', [AdminFrontController::class, 'previewFrame'])->name('front.preview-frame');
                Route::post('/front/preview', [AdminFrontController::class, 'preview'])
                    ->middleware('check.menu.access:front')->name('front.preview');
                Route::put('/front/edit', [AdminFrontController::class, 'update'])
                    ->middleware('check.menu.edit:front')->name('front.edit.update');
                Route::delete('/front/reset', [AdminFrontController::class, 'destroy'])
                    ->middleware('check.menu.edit:front')->name('front.destroy');
                Route::get('/front/settings', [AdminFrontController::class, 'settings'])->name('front.settings');
                Route::post('/front/settings', [AdminFrontController::class, 'updateSettings'])
                    ->middleware('check.menu.edit:front')->name('front.settings.store');
            });

            // メディア管理（権限チェック付き）
            Route::middleware('check.menu.access:media')->group(function () {
                Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
                // メディアAPI（モーダル用）
                Route::get('/media/api', [AdminMediaController::class, 'api'])->name('media.api');
                // メディアアップロード
                Route::get('/media/upload', [AdminMediaController::class, 'upload'])->name('media.upload');
                Route::post('/media/upload/', [AdminMediaController::class, 'store'])
                    ->middleware('check.menu.edit:media')
                    ->name('media.store');
                // メディア削除
                Route::delete('/media/delete/{media}', [AdminMediaController::class, 'delete'])
                    ->middleware('check.menu.edit:media')
                    ->name('media.delete');
                // メディアダウンロード
                Route::get('/media/download/{media}', [AdminMediaController::class, 'download'])->name('media.download');
                // メディアプレビュー
                Route::get('/media/preview/{media}', [AdminMediaController::class, 'preview'])->name('media.preview');
                // メディア情報更新
                Route::put('/media/{media}', [AdminMediaController::class, 'updateMedia'])
                    ->middleware('check.menu.edit:media')
                    ->name('media.update');
                // メディア設定（かんたんモード: Partial — セキュリティ項目は自動設定）
                Route::get('/media/settings', [AdminMediaController::class, 'settings'])->name('media.settings');
                Route::post('/media/settings', [AdminMediaController::class, 'update'])
                    ->middleware('check.menu.edit:media.settings')
                    ->name('media.settings.update');
            });

            // プロフィール設定（全員アクセス可能）
            Route::get('/profile', [AdminProfileController::class, 'index'])->name('profile');

            // 基本情報
            Route::get('/profile/basic', [AdminProfileBasicController::class, 'index'])->name('profile.basic');
            Route::post('/profile/basic', [AdminProfileBasicController::class, 'update'])->name('profile.basic.update');

            // パスワード設定
            Route::get('/profile/password', [AdminProfilePasswordController::class, 'index'])->name('profile.password');
            Route::post('/profile/password', [AdminProfilePasswordController::class, 'update'])->name('profile.password.update');

            // 外観設定
            Route::get('/profile/appearance', [AdminProfileAppearanceController::class, 'index'])->name('profile.appearance');
            Route::post('/profile/appearance', [AdminProfileAppearanceController::class, 'update'])->name('profile.appearance.update');

            // 通知設定
            Route::get('/profile/notifications', [AdminProfileNotificationsController::class, 'index'])->name('profile.notifications');
            Route::post('/profile/notifications', [AdminProfileNotificationsController::class, 'update'])->name('profile.notifications.update');

            // 二段階認証設定
            Route::get('/profile/two-fa', [AdminProfileTwoFaController::class, 'index'])->name('profile.two-fa');
            Route::post('/profile/two-fa', [AdminProfileTwoFaController::class, 'update'])->name('profile.two-fa.update');

            // 二段階認証管理（パスキー・コード）
            Route::get('/profile/two-fa-management', [AdminProfileTwoFaManagementController::class, 'index'])->name('profile.two-fa-management');

            // Passkey管理
            Route::post('/profile/passkey/register-options', [AdminProfileTwoFaManagementController::class, 'passkeyRegisterOptions'])->name('profile.passkey.register-options');
            Route::post('/profile/passkey/register', [AdminProfileTwoFaManagementController::class, 'passkeyRegister'])->name('profile.passkey.register');
            Route::delete('/profile/passkey/{credentialId}', [AdminProfileTwoFaManagementController::class, 'revokePasskey'])->name('profile.passkey.revoke');
            Route::delete('/profile/passkey/all', [AdminProfileTwoFaManagementController::class, 'revokeAllPasskeys'])->name('profile.passkey.revoke-all');

            // 回復コード管理
            Route::post('/profile/recovery-codes/generate', [AdminProfileTwoFaManagementController::class, 'generateRecoveryCodes'])->name('profile.recovery-codes.generate');
            Route::post('/profile/recovery-codes/regenerate', [AdminProfileTwoFaManagementController::class, 'regenerateRecoveryCodes'])->name('profile.recovery-codes.regenerate');
            Route::post('/profile/recovery-codes/clear-session', [AdminProfileTwoFaManagementController::class, 'clearRecoveryCodesSession'])->name('profile.recovery-codes.clear-session');

            // パスキー登録促進モーダル設定
            Route::post('/profile/passkey-prompt/dismiss', [AdminProfilePasskeyPromptController::class, 'dismiss'])->name('profile.passkey-prompt.dismiss');
            Route::post('/profile/passkey-prompt/reset', [AdminProfilePasskeyPromptController::class, 'reset'])->name('profile.passkey-prompt.reset');

            // サイドバーメニュー表示設定
            Route::post('/profile/sidebar/update', [AdminProfileSidebarController::class, 'update'])->name('profile.sidebar.update');
            Route::post('/profile/sidebar/reset', [AdminProfileSidebarController::class, 'reset'])->name('profile.sidebar.reset');

            // メンバー管理（権限チェック付き）
            Route::middleware('check.menu.access:members')->prefix('members')->name('members.')->group(function () {
                // メンバー一覧
                Route::get('/', [Members\AdminMemberController::class, 'index'])->name('index');

                // メンバーCRUD
                Route::get('/create', [Members\AdminMemberFormController::class, 'create'])
                    ->middleware('check.menu.access:members.create_edit')
                    ->name('create');
                Route::post('/', [Members\AdminMemberFormController::class, 'store'])
                    ->middleware('check.menu.access:members.create_edit')
                    ->name('store');
                Route::get('/edit/{member}', [Members\AdminMemberFormController::class, 'edit'])
                    ->middleware('check.menu.access:members.create_edit')
                    ->name('edit');
                Route::post('/update/{member}', [Members\AdminMemberFormController::class, 'update'])
                    ->middleware('check.menu.access:members.create_edit')
                    ->name('update');
                Route::delete('/destroy/{member}', [Members\AdminMemberFormController::class, 'destroy'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('destroy');

                // セキュリティ操作
                Route::delete('/passkey/{member}/{credentialId}', [Members\AdminMemberSecurityController::class, 'revokePasskey'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('passkey.revoke');
                Route::delete('/passkey/{member}/all', [Members\AdminMemberSecurityController::class, 'revokeAllPasskeys'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('passkey.revoke-all');
                Route::delete('/recovery-codes/{member}', [Members\AdminMemberSecurityController::class, 'revokeRecoveryCodes'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('recovery-codes.revoke');
                Route::post('/force-logout/{member}', [Members\AdminMemberSecurityController::class, 'forceLogout'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('force-logout');
                Route::post('/unlock-lockout/{member}', [Members\AdminMemberSecurityController::class, 'unlockTwoFa'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('unlock-lockout');
                Route::post('/force-logout-all', [Members\AdminMemberSecurityController::class, 'forceLogoutAll'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('force-logout-all');
                Route::post('/{member}/send-verification-email', [Members\AdminMemberSecurityController::class, 'sendVerificationEmail'])
                    ->middleware('check.menu.edit:members.index')
                    ->name('send-verification-email');

                // 権限設定
                Route::get('/roles', [Members\AdminMemberRolesController::class, 'index'])->name('roles');
                Route::post('/roles', [Members\AdminMemberRolesController::class, 'update'])
                    ->middleware('check.menu.edit:members.roles')
                    ->name('roles.update');
            });

            // 全体設定
            // 基本設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.base')->prefix('settings/base')->name('settings.base.')->group(function () {
                // 概要
                Route::get('/', [Base\AdminBaseIndexController::class, 'index'])->name('index');

                // サイト設定
                Route::get('/site', [Base\AdminBaseSiteController::class, 'index'])->name('site');
                Route::post('/site', [Base\AdminBaseSiteController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.site')
                    ->name('site.update');

                // 管理画面設定
                Route::get('/admin', [Base\AdminBaseAdminController::class, 'index'])->name('admin');
                Route::post('/admin', [Base\AdminBaseAdminController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.admin')
                    ->name('admin.update');

                // メール設定
                Route::get('/mail', [Base\AdminBaseMailController::class, 'index'])->name('mail');
                Route::post('/mail', [Base\AdminBaseMailController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.mail')
                    ->name('mail.update');
                Route::post('/mail/test-mail', [Base\AdminBaseMailController::class, 'testMail'])->name('mail.test-mail');
                Route::post('/mail/test-connection', [Base\AdminBaseMailController::class, 'testConnection'])->name('mail.test-connection');
                Route::post('/mail/clear-test-session', [Base\AdminBaseMailController::class, 'clearTestSession'])->name('mail.clear-test-session');
                Route::get('/mail/check-test-session', [Base\AdminBaseMailController::class, 'checkTestSession'])->name('mail.check-test-session');
                Route::get('/mail/verify-mail/{token}', [Base\AdminBaseMailController::class, 'verifyMail'])->name('mail.verify-mail');
                Route::get('/mail/mail-verification-success', [Base\AdminBaseMailController::class, 'mailVerificationSuccess'])->name('mail.mail-verification-success');

                // メンテナンス設定
                Route::get('/maintenance', [Base\AdminBaseMaintenanceController::class, 'index'])->name('maintenance');
                Route::get('/maintenance/preview', [Base\AdminBaseMaintenanceController::class, 'preview'])->name('maintenance.preview');
                Route::post('/maintenance', [Base\AdminBaseMaintenanceController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.maintenance')
                    ->name('maintenance.update');

                // モード設定
                Route::get('/mode', [Base\AdminBaseModeController::class, 'index'])->name('mode');
                Route::post('/mode', [Base\AdminBaseModeController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.mode')
                    ->name('mode.update');

                // コンテンツ設定（リビジョン保持件数など、全コンテンツタイプ共通）
                Route::get('/content', [Base\AdminBaseContentController::class, 'index'])->name('content');
                Route::post('/content', [Base\AdminBaseContentController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.content')
                    ->name('content.update');

                // コンテンツエディター設定
                Route::get('/editor', [Base\AdminBaseEditorController::class, 'index'])->name('editor');
                Route::post('/editor', [Base\AdminBaseEditorController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.editor')
                    ->name('editor.update');
            });

            // セキュリティ設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.security')->prefix('settings/security')->name('settings.security.')->group(function () {
                // 概要
                Route::get('/', [Security\AdminSecurityIndexController::class, 'index'])->name('index');

                // パスワードセキュリティ
                Route::get('/password', [Security\AdminSecurityPasswordController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.password')
                    ->name('password');
                Route::post('/password', [Security\AdminSecurityPasswordController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.password')
                    ->name('password.update');

                // ログイン試行制限
                Route::get('/login', [Security\AdminSecurityLoginController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.login')
                    ->name('login');
                Route::post('/login', [Security\AdminSecurityLoginController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.login')
                    ->name('login.update');

                // セッション管理
                Route::get('/session', [Security\AdminSecuritySessionController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.session')
                    ->name('session');
                Route::post('/session', [Security\AdminSecuritySessionController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.session')
                    ->name('session.update');

                // 二段階認証設定
                Route::get('/two-fa', [Security\AdminSecurityTwoFaController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.two-fa')
                    ->name('two-fa');
                Route::post('/two-fa', [Security\AdminSecurityTwoFaController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.two-fa')
                    ->name('two-fa.update');

                // CAPTCHA
                Route::get('/captcha', [Security\AdminSecurityCaptchaController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.captcha')
                    ->name('captcha');
                Route::post('/captcha', [Security\AdminSecurityCaptchaController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.captcha')
                    ->name('captcha.update');
                Route::post('/captcha/validate-widget', [Security\AdminSecurityCaptchaController::class, 'validateWidget'])
                    ->middleware('check.menu.access:settings.security.captcha')
                    ->name('captcha.validate-widget');
                Route::post('/captcha/clear-test', [Security\AdminSecurityCaptchaController::class, 'clearTest'])
                    ->middleware('check.menu.access:settings.security.captcha')
                    ->name('captcha.clear-test');

                // IPアクセス制御
                Route::get('/ip', [Security\AdminSecurityIpController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.ip')
                    ->name('ip');
                Route::post('/ip', [Security\AdminSecurityIpController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.ip')
                    ->name('ip.update');

                // 拡張機能セキュリティ
                Route::get('/extensions', [Security\AdminSecurityExtensionsController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.extensions')
                    ->name('extensions');
                Route::post('/extensions', [Security\AdminSecurityExtensionsController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.extensions')
                    ->name('extensions.update');
                Route::post('/extensions/test-source', [Security\AdminSecurityExtensionsController::class, 'testSource'])
                    ->middleware('check.menu.edit:settings.security.extensions')
                    ->name('extensions.test-source');

                // CSP
                Route::get('/csp', [Security\AdminSecurityCspController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.csp')
                    ->name('csp');
                Route::post('/csp', [Security\AdminSecurityCspController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.csp')
                    ->name('csp.update');
                Route::post('/csp/confirm', [Security\AdminSecurityCspController::class, 'confirm'])
                    ->middleware('check.menu.edit:settings.security.csp')
                    ->name('csp.confirm');
                Route::post('/csp/rollback', [Security\AdminSecurityCspController::class, 'rollback'])
                    ->middleware('check.menu.edit:settings.security.csp')
                    ->name('csp.rollback');

                // 通知
                Route::get('/notifications', [Security\AdminSecurityNotificationsController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.notifications')
                    ->name('notifications');
                Route::post('/notifications', [Security\AdminSecurityNotificationsController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.notifications')
                    ->name('notifications.update');

                // 環境設定
                Route::get('/environment', [Security\AdminSecurityEnvironmentController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.environment')
                    ->name('environment');
                Route::post('/environment', [Security\AdminSecurityEnvironmentController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.environment')
                    ->name('environment.update');

                // ファイル整合性
                Route::get('/integrity', [Security\AdminSecurityIntegrityController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.integrity')
                    ->name('integrity');
                Route::post('/integrity/scan', [Security\AdminSecurityIntegrityController::class, 'scan'])
                    ->middleware('check.menu.edit:settings.security.integrity')
                    ->name('integrity.scan');
                Route::post('/integrity/regenerate-baseline', [Security\AdminSecurityIntegrityController::class, 'regenerateBaseline'])
                    ->middleware('check.menu.edit:settings.security.integrity')
                    ->name('integrity.regenerate-baseline');
                Route::delete('/integrity/{audit}', [Security\AdminSecurityIntegrityController::class, 'destroy'])
                    ->middleware('check.menu.edit:settings.security.integrity')
                    ->name('integrity.destroy');
                Route::post('/integrity/bulk-delete', [Security\AdminSecurityIntegrityController::class, 'bulkDelete'])
                    ->middleware('check.menu.edit:settings.security.integrity')
                    ->name('integrity.bulk-delete');
                Route::get('/integrity/{audit}', [Security\AdminSecurityIntegrityController::class, 'show'])
                    ->middleware('check.menu.access:settings.security.integrity')
                    ->name('integrity.show');
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
                Route::get('/settings/themes/available-from-source', [AdminThemesSettingsController::class, 'availableFromSource'])
                    ->name('settings.themes.available-from-source');
                Route::post('/settings/themes/download-from-source', [AdminThemesSettingsController::class, 'downloadFromSource'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.download-from-source');
                Route::post('/settings/themes/check-updates', [AdminThemesSettingsController::class, 'checkUpdates'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.check-updates');
                Route::post('/settings/themes/update/{id}', [AdminThemesSettingsController::class, 'updateTheme'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.update');
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
                Route::get('/settings/plugins/available-from-source', [AdminPluginsSettingsController::class, 'availableFromSource'])
                    ->name('settings.plugins.available-from-source');
                Route::post('/settings/plugins/download-from-source', [AdminPluginsSettingsController::class, 'downloadFromSource'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.download-from-source');
                Route::post('/settings/plugins/check-updates', [AdminPluginsSettingsController::class, 'checkUpdates'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.check-updates');
                Route::post('/settings/plugins/update/{id}', [AdminPluginsSettingsController::class, 'updatePlugin'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.update');
            });

            // システム設定（権限チェック付き）
            Route::middleware('check.menu.access:settings.systems')->prefix('settings/systems')->name('settings.systems.')->group(function () {
                // API管理（かんたんモード: Hidden）
                Route::get('/api', [Systems\AdminSystemApiController::class, 'index'])
                    ->middleware('check.menu.access:settings.systems.api')
                    ->name('api');
                Route::post('/api', [Systems\AdminSystemApiController::class, 'update'])
                    ->middleware('check.menu.edit:settings.systems.api')
                    ->name('api.update');
                Route::post('/api/generate-key', [Systems\AdminSystemApiController::class, 'generateKey'])
                    ->middleware('check.menu.edit:settings.systems.api')
                    ->name('api.generate-key');
                Route::delete('/api/revoke-key/{id}', [Systems\AdminSystemApiController::class, 'revokeKey'])
                    ->middleware('check.menu.edit:settings.systems.api')
                    ->name('api.revoke-key');
                Route::post('/api/regenerate-key/{id}', [Systems\AdminSystemApiController::class, 'regenerateKey'])
                    ->middleware('check.menu.edit:settings.systems.api')
                    ->name('api.regenerate-key');

                // キャッシュ管理（かんたんモード: Full）
                Route::get('/cache', [Systems\AdminSystemCacheController::class, 'index'])->name('cache');
                Route::post('/cache/clear', [Systems\AdminSystemCacheController::class, 'clear'])
                    ->middleware('check.menu.edit:settings.systems.cache')
                    ->name('cache.clear');

                // データベース管理（かんたんモード: Hidden）
                Route::get('/database', [Systems\AdminSystemDatabaseController::class, 'index'])
                    ->middleware('check.menu.access:settings.systems.database')
                    ->name('database');
                Route::post('/database/cleanup', [Systems\AdminSystemDatabaseController::class, 'cleanup'])
                    ->middleware('check.menu.edit:settings.systems.database')
                    ->name('database.cleanup');

                // 監査ログ（かんたんモード: Full）
                Route::get('/logs', [Systems\AdminSystemLogsController::class, 'index'])
                    ->middleware('check.menu.access:settings.systems.logs')
                    ->name('logs.index');

                // ファイルログ
                Route::get('/logs/files/{type?}', [Systems\AdminSystemLogsController::class, 'files'])
                    ->middleware('check.menu.access:settings.systems.logs')
                    ->name('logs.files');
                Route::get('/logs/files/{type}/download', [Systems\AdminSystemLogsController::class, 'download'])
                    ->middleware('check.menu.access:settings.systems.logs')
                    ->name('logs.download');
                Route::post('/logs/files/{type}/clear', [Systems\AdminSystemLogsController::class, 'clear'])
                    ->middleware('check.menu.edit:settings.systems.logs')
                    ->name('logs.clear');
                Route::post('/logs/files/test', [Systems\AdminSystemLogsController::class, 'test'])
                    ->middleware('check.menu.edit:settings.systems.logs')
                    ->name('logs.test');
                Route::post('/logs/files/test-error', [Systems\AdminSystemLogsController::class, 'testError'])
                    ->middleware('check.menu.edit:settings.systems.logs')
                    ->name('logs.test-error');
                Route::post('/logs/files/test-front', [Systems\AdminSystemLogsController::class, 'testFront'])
                    ->middleware('check.menu.edit:settings.systems.logs')
                    ->name('logs.test-front');
                Route::post('/logs/files/test-front-error', [Systems\AdminSystemLogsController::class, 'testFrontError'])
                    ->middleware('check.menu.edit:settings.systems.logs')
                    ->name('logs.test-front-error');
                Route::get('/logs/audit/{id}', [Systems\AdminSystemLogsController::class, 'show'])
                    ->middleware('check.menu.access:settings.systems.logs')
                    ->name('logs.audit.show');
                Route::get('/logs/audit-export', [Systems\AdminSystemLogsController::class, 'auditExport'])
                    ->middleware('check.menu.access:settings.systems.logs')
                    ->name('logs.audit.export');
                Route::post('/logs/audit/cleanup', [Systems\AdminSystemLogsController::class, 'auditCleanup'])
                    ->middleware('check.menu.edit:settings.systems.logs')
                    ->name('logs.audit.cleanup');

                // システム情報（かんたんモード: ReadOnly）
                Route::get('/info', [Systems\AdminSystemInfoController::class, 'index'])
                    ->middleware('check.menu.access:settings.systems.info')
                    ->name('info');
            });

            // ログアウト
            Route::match(['get', 'post'], '/logout', [AdminLoginController::class, 'destroy'])->name('logout');

            // 注: プラグインの管理画面ルートはPluginServiceProvider::loadPluginRoutes()で読み込む
            // プラグイン側でルート名を完全に制御するため、ここでは読み込まない
            // \App\Helpers\PluginHelper::loadEnabledAdminRoutes();

            // 有効化されているテーマの管理画面ルートを自動読み込み
            \App\Helpers\ThemeHelper::loadEnabledThemeAdminRoutes();
        });
    });
