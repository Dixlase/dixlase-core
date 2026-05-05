<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
use App\Http\Controllers\Admin\Front\AdminFrontRevisionController;
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

// Get admin panel URL
$adminUrl = AdminHelper::getAdminUrl();

Route::prefix($adminUrl)->name('admin.')
    ->middleware(['admin.ip']) // Apply only the IP address filter first
    ->group(function () {
        Route::get('/', function () {
            $member = Auth::guard('member')->user();
            if ($member) {
                return redirect()->route('admin.dashboard');
            } else {
                return redirect()->route('admin.login');
            }
        });

        // Login
        Route::middleware([\App\Http\Middleware\CheckLockdown::class.':login'])->group(function () {
            Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
            Route::post('/login', [AdminLoginController::class, 'store'])->name('login.store');
        });

        // Login identifier verification (check existence of email address/account name)
        Route::post('/login/check-identifier', [AdminLoginIdentifierCheckController::class, 'check'])->name('login.check-identifier');

        // Passkey login
        Route::post('/login/passkey/challenge', [AdminPasskeyLoginController::class, 'getChallenge'])->name('login.passkey.challenge');
        Route::post('/login/passkey/verify', [AdminPasskeyLoginController::class, 'verify'])->name('login.passkey.verify');

        // Two-factor authentication (email)
        Route::get('/two-fa-email', [AdminTwoFaController::class, 'showEmailChallenge'])->name('two-fa.email.show');
        Route::post('/two-fa-email/verify', [AdminTwoFaController::class, 'verifyEmail'])->name('two-fa.email.verify');
        Route::post('/two-fa-email/resend', [AdminTwoFaController::class, 'resendEmail'])->name('two-fa.email.resend');

        // Recovery code
        Route::get('/two-fa-recovery', [AdminTwoFaController::class, 'showRecoveryCodeChallenge'])->name('two-fa.recovery-code.show');
        Route::post('/two-fa-recovery', [AdminTwoFaController::class, 'verifyRecoveryCode'])->name('two-fa.recovery-code.confirm');

        // Password reset
        Route::get('/forgot-password', [AdminPasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [AdminPasswordResetLinkController::class, 'store'])->name('password.email');
        Route::get('/reset-password/{token}', [AdminNewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/reset-password', [AdminNewPasswordController::class, 'store'])->name('password.store');

        // Email verification (no authentication required, signed URL)
        Route::get('/verify-mail/{id}/{hash}', [AdminProfileController::class, 'verifyEmail'])
            ->name('verification.verify')
            ->middleware('signed');

        // Email verification notification (for logged-in, unverified users)
        Route::middleware('auth:member')->group(function () {
            Route::get('/email/verify', [AdminEmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
            Route::post('/email/verification-notification', [AdminEmailVerificationNotificationController::class, 'store'])->name('verification.send');
        });

        // Authenticated routes
        Route::middleware([
            'auth:member',
            'verified',
            'log.admin.activity',
            \App\Http\Middleware\CheckLockdown::class.':admin',
        ])->group(function () {
            // Dashboard (accessible to all)
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::post('/dashboard/dismiss-getting-started', [AdminDashboardController::class, 'dismissGettingStarted'])->name('dashboard.dismiss-getting-started');
            Route::post('/dashboard/visit-getting-started', [AdminDashboardController::class, 'visitGettingStartedStep'])->name('dashboard.visit-getting-started');

            // Safe mode management (accessible to all)
            Route::post('/safe-mode/disable', [SafeModeController::class, 'disable'])->name('safe-mode.disable');
            Route::post('/safe-mode/disable-all', [SafeModeController::class, 'disableAll'])->name('safe-mode.disable-all');

            // Front page management (with permission check)
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

                // Revisions (view, diff display, restore)
                Route::get('/front/revisions', [AdminFrontRevisionController::class, 'index'])->name('front.revisions.index');
                Route::get('/front/revisions/{id}', [AdminFrontRevisionController::class, 'show'])->whereNumber('id')->name('front.revisions.show');
                Route::post('/front/revisions/{id}/restore', [AdminFrontRevisionController::class, 'restore'])
                    ->whereNumber('id')
                    ->middleware('check.menu.edit:front')
                    ->name('front.revisions.restore');
                Route::post('/front/revisions/{id}/note', [AdminFrontRevisionController::class, 'updateNote'])
                    ->whereNumber('id')
                    ->middleware('check.menu.edit:front')
                    ->name('front.revisions.note');
                Route::post('/front/revisions/{id}/protect', [AdminFrontRevisionController::class, 'toggleProtection'])
                    ->whereNumber('id')
                    ->middleware('check.menu.edit:front')
                    ->name('front.revisions.protect');
            });

            // Media management (with permission check)
            Route::middleware('check.menu.access:media')->group(function () {
                Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
                // Media API (for modal)
                Route::get('/media/api', [AdminMediaController::class, 'api'])->name('media.api');
                // Media upload
                Route::get('/media/upload', [AdminMediaController::class, 'upload'])->name('media.upload');
                Route::post('/media/upload/', [AdminMediaController::class, 'store'])
                    ->middleware('check.menu.edit:media')
                    ->name('media.store');
                // Media deletion
                Route::delete('/media/delete/{media}', [AdminMediaController::class, 'delete'])
                    ->middleware('check.menu.edit:media')
                    ->name('media.delete');
                // Media download
                Route::get('/media/download/{media}', [AdminMediaController::class, 'download'])->name('media.download');
                // Media preview
                Route::get('/media/preview/{media}', [AdminMediaController::class, 'preview'])->name('media.preview');
                // Update media information
                Route::put('/media/{media}', [AdminMediaController::class, 'updateMedia'])
                    ->middleware('check.menu.edit:media')
                    ->name('media.update');
                // Media settings (simple mode: Partial — security items are auto-configured)
                Route::get('/media/settings', [AdminMediaController::class, 'settings'])->name('media.settings');
                Route::post('/media/settings', [AdminMediaController::class, 'update'])
                    ->middleware('check.menu.edit:media.settings')
                    ->name('media.settings.update');
            });

            // Profile settings (accessible to all)
            Route::get('/profile', [AdminProfileController::class, 'index'])->name('profile');

            // Basic information
            Route::get('/profile/basic', [AdminProfileBasicController::class, 'index'])->name('profile.basic');
            Route::post('/profile/basic', [AdminProfileBasicController::class, 'update'])->name('profile.basic.update');

            // Password settings
            Route::get('/profile/password', [AdminProfilePasswordController::class, 'index'])->name('profile.password');
            Route::post('/profile/password', [AdminProfilePasswordController::class, 'update'])->name('profile.password.update');

            // Appearance settings
            Route::get('/profile/appearance', [AdminProfileAppearanceController::class, 'index'])->name('profile.appearance');
            Route::post('/profile/appearance', [AdminProfileAppearanceController::class, 'update'])->name('profile.appearance.update');

            // Notification settings
            Route::get('/profile/notifications', [AdminProfileNotificationsController::class, 'index'])->name('profile.notifications');
            Route::post('/profile/notifications', [AdminProfileNotificationsController::class, 'update'])->name('profile.notifications.update');

            // Two-factor authentication settings
            Route::get('/profile/two-fa', [AdminProfileTwoFaController::class, 'index'])->name('profile.two-fa');
            Route::post('/profile/two-fa', [AdminProfileTwoFaController::class, 'update'])->name('profile.two-fa.update');

            // Two-factor authentication management (passkey/code)
            Route::get('/profile/two-fa-management', [AdminProfileTwoFaManagementController::class, 'index'])->name('profile.two-fa-management');

            // Passkey management
            Route::post('/profile/passkey/register-options', [AdminProfileTwoFaManagementController::class, 'passkeyRegisterOptions'])->name('profile.passkey.register-options');
            Route::post('/profile/passkey/register', [AdminProfileTwoFaManagementController::class, 'passkeyRegister'])->name('profile.passkey.register');
            Route::delete('/profile/passkey/{credentialId}', [AdminProfileTwoFaManagementController::class, 'revokePasskey'])->name('profile.passkey.revoke');
            Route::delete('/profile/passkey/all', [AdminProfileTwoFaManagementController::class, 'revokeAllPasskeys'])->name('profile.passkey.revoke-all');

            // Recovery code management
            Route::post('/profile/recovery-codes/generate', [AdminProfileTwoFaManagementController::class, 'generateRecoveryCodes'])->name('profile.recovery-codes.generate');
            Route::post('/profile/recovery-codes/regenerate', [AdminProfileTwoFaManagementController::class, 'regenerateRecoveryCodes'])->name('profile.recovery-codes.regenerate');
            Route::post('/profile/recovery-codes/clear-session', [AdminProfileTwoFaManagementController::class, 'clearRecoveryCodesSession'])->name('profile.recovery-codes.clear-session');

            // Passkey registration prompt modal settings
            Route::post('/profile/passkey-prompt/dismiss', [AdminProfilePasskeyPromptController::class, 'dismiss'])->name('profile.passkey-prompt.dismiss');
            Route::post('/profile/passkey-prompt/reset', [AdminProfilePasskeyPromptController::class, 'reset'])->name('profile.passkey-prompt.reset');

            // Sidebar menu display settings
            Route::post('/profile/sidebar/update', [AdminProfileSidebarController::class, 'update'])->name('profile.sidebar.update');
            Route::post('/profile/sidebar/reset', [AdminProfileSidebarController::class, 'reset'])->name('profile.sidebar.reset');

            // Member management (with permission check)
            Route::middleware('check.menu.access:members')->prefix('members')->name('members.')->group(function () {
                // Member list
                Route::get('/', [Members\AdminMemberController::class, 'index'])->name('index');

                // Member CRUD
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

                // Security operations
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

                // Permission settings
                Route::get('/roles', [Members\AdminMemberRolesController::class, 'index'])->name('roles');
                Route::post('/roles', [Members\AdminMemberRolesController::class, 'update'])
                    ->middleware('check.menu.edit:members.roles')
                    ->name('roles.update');
            });

            // Privacy (subject access export/erasure) — super-admin stub
            Route::prefix('privacy/users')->name('privacy.users.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\Privacy\UserDataController::class, 'index'])
                    ->name('index');
                Route::get('/{id}/export', [\App\Http\Controllers\Admin\Privacy\UserDataController::class, 'export'])
                    ->whereNumber('id')
                    ->name('export');
                Route::post('/{id}/delete', [\App\Http\Controllers\Admin\Privacy\UserDataController::class, 'delete'])
                    ->whereNumber('id')
                    ->name('delete');
            });

            // Global settings
            // Basic settings (with permission check)
            Route::middleware('check.menu.access:settings.base')->prefix('settings/base')->name('settings.base.')->group(function () {
                // Overview
                Route::get('/', [Base\AdminBaseIndexController::class, 'index'])->name('index');

                // Site settings
                Route::get('/site', [Base\AdminBaseSiteController::class, 'index'])->name('site');
                Route::post('/site', [Base\AdminBaseSiteController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.site')
                    ->name('site.update');

                // Admin panel settings
                Route::get('/admin', [Base\AdminBaseAdminController::class, 'index'])->name('admin');
                Route::post('/admin', [Base\AdminBaseAdminController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.admin')
                    ->name('admin.update');

                // Email settings
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

                // Maintenance settings
                Route::get('/maintenance', [Base\AdminBaseMaintenanceController::class, 'index'])->name('maintenance');
                Route::get('/maintenance/preview', [Base\AdminBaseMaintenanceController::class, 'preview'])->name('maintenance.preview');
                Route::post('/maintenance', [Base\AdminBaseMaintenanceController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.maintenance')
                    ->name('maintenance.update');

                // Mode settings
                Route::get('/mode', [Base\AdminBaseModeController::class, 'index'])->name('mode');
                Route::post('/mode', [Base\AdminBaseModeController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.mode')
                    ->name('mode.update');

                // Content settings (revision retention count, etc., common to all content types)
                Route::get('/content', [Base\AdminBaseContentController::class, 'index'])->name('content');
                Route::post('/content', [Base\AdminBaseContentController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.content')
                    ->name('content.update');

                // Content editor settings
                Route::get('/editor', [Base\AdminBaseEditorController::class, 'index'])->name('editor');
                Route::post('/editor', [Base\AdminBaseEditorController::class, 'update'])
                    ->middleware('check.menu.edit:settings.base.editor')
                    ->name('editor.update');
            });

            // Security settings (with permission check)
            Route::middleware('check.menu.access:settings.security')->prefix('settings/security')->name('settings.security.')->group(function () {
                // Overview
                Route::get('/', [Security\AdminSecurityIndexController::class, 'index'])->name('index');

                // Password security
                Route::get('/password', [Security\AdminSecurityPasswordController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.password')
                    ->name('password');
                Route::post('/password', [Security\AdminSecurityPasswordController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.password')
                    ->name('password.update');

                // Login attempt restriction
                Route::get('/login', [Security\AdminSecurityLoginController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.login')
                    ->name('login');
                Route::post('/login', [Security\AdminSecurityLoginController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.login')
                    ->name('login.update');

                // Session management
                Route::get('/session', [Security\AdminSecuritySessionController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.session')
                    ->name('session');
                Route::post('/session', [Security\AdminSecuritySessionController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.session')
                    ->name('session.update');

                // Two-factor authentication settings
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

                // IP access control
                Route::get('/ip', [Security\AdminSecurityIpController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.ip')
                    ->name('ip');
                Route::post('/ip', [Security\AdminSecurityIpController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.ip')
                    ->name('ip.update');

                // Extension security
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

                // Notifications
                Route::get('/notifications', [Security\AdminSecurityNotificationsController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.notifications')
                    ->name('notifications');
                Route::post('/notifications', [Security\AdminSecurityNotificationsController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.notifications')
                    ->name('notifications.update');

                // Environment settings
                Route::get('/environment', [Security\AdminSecurityEnvironmentController::class, 'index'])
                    ->middleware('check.menu.access:settings.security.environment')
                    ->name('environment');
                Route::post('/environment', [Security\AdminSecurityEnvironmentController::class, 'update'])
                    ->middleware('check.menu.edit:settings.security.environment')
                    ->name('environment.update');

                // File integrity
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

            // Theme settings (with permission check)
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
                Route::post('/settings/themes/audit-all', [AdminThemesSettingsController::class, 'auditAll'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.audit-all');
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
                Route::post('/settings/themes/update-all', [AdminThemesSettingsController::class, 'bulkUpdate'])
                    ->middleware('check.menu.edit:settings.themes')
                    ->name('settings.themes.update-all');
            });

            // Plugin settings (with permission check)
            Route::middleware('check.menu.access:settings.plugins')->group(function () {
                Route::get('/settings/plugins', [AdminPluginsSettingsController::class, 'index'])->name('settings.plugins.index');
                Route::get('/settings/plugins/add', [AdminPluginsSettingsController::class, 'add'])->name('settings.plugins.add');
                Route::get('/settings/plugins/show/{slug}', [AdminPluginsSettingsController::class, 'show'])->name('settings.plugins.show');
                Route::get('/settings/plugins/show-online/{slug}', [AdminPluginsSettingsController::class, 'showOnline'])->name('settings.plugins.show-online');
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
                Route::post('/settings/plugins/audit-all', [AdminPluginsSettingsController::class, 'auditAll'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.audit-all');
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
                Route::post('/settings/plugins/update-all', [AdminPluginsSettingsController::class, 'bulkUpdate'])
                    ->middleware('check.menu.edit:settings.plugins')
                    ->name('settings.plugins.update-all');
            });

            // System settings (with permission check)
            Route::middleware('check.menu.access:settings.systems')->prefix('settings/systems')->name('settings.systems.')->group(function () {
                // API management (Easy mode: Hidden)
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

                // Integrated update management (Core / plugin / theme)
                Route::prefix('updates')->name('updates.')->group(function () {
                    Route::get('/', [Systems\AdminSystemUpdatesController::class, 'index'])
                        ->middleware('check.menu.access:settings.systems.updates')
                        ->name('index');
                    Route::post('/check', [Systems\AdminSystemUpdatesController::class, 'check'])
                        ->middleware('check.menu.edit:settings.systems.updates')
                        ->name('check');
                    Route::post('/apply', [Systems\AdminSystemUpdatesController::class, 'apply'])
                        ->middleware('check.menu.edit:settings.systems.updates')
                        ->name('apply');
                });

                // Cache management (Easy mode: Full)
                Route::get('/cache', [Systems\AdminSystemCacheController::class, 'index'])->name('cache');
                Route::post('/cache/clear', [Systems\AdminSystemCacheController::class, 'clear'])
                    ->middleware('check.menu.edit:settings.systems.cache')
                    ->name('cache.clear');
                Route::post('/cache/rebuild', [Systems\AdminSystemCacheController::class, 'rebuild'])
                    ->middleware('check.menu.edit:settings.systems.cache')
                    ->name('cache.rebuild');

                // Database management (Easy mode: Hidden)
                Route::get('/database', [Systems\AdminSystemDatabaseController::class, 'index'])
                    ->middleware('check.menu.access:settings.systems.database')
                    ->name('database');
                Route::post('/database/cleanup', [Systems\AdminSystemDatabaseController::class, 'cleanup'])
                    ->middleware('check.menu.edit:settings.systems.database')
                    ->name('database.cleanup');

                // Backup management
                Route::prefix('backup')->name('backup.')->group(function () {
                    Route::get('/', [Systems\AdminSystemBackupController::class, 'index'])
                        ->middleware('check.menu.access:settings.systems.backup.index')
                        ->name('index');
                    Route::post('/', [Systems\AdminSystemBackupController::class, 'create'])
                        ->middleware('check.menu.edit:settings.systems.backup.index')
                        ->name('create');
                    Route::get('/{backup}/download', [Systems\AdminSystemBackupController::class, 'download'])
                        ->middleware('check.menu.access:settings.systems.backup.index')
                        ->name('download');
                    Route::post('/{backup}/restore', [Systems\AdminSystemBackupController::class, 'restore'])
                        ->middleware('check.menu.edit:settings.systems.backup.index')
                        ->name('restore');
                    Route::delete('/{backup}', [Systems\AdminSystemBackupController::class, 'destroy'])
                        ->middleware('check.menu.edit:settings.systems.backup.index')
                        ->name('destroy');
                    Route::get('/restores', [Systems\AdminSystemBackupController::class, 'restores'])
                        ->middleware('check.menu.access:settings.systems.backup.restores')
                        ->name('restores');
                    Route::post('/restores/{restore}/rollback', [Systems\AdminSystemBackupController::class, 'rollback'])
                        ->middleware('check.menu.edit:settings.systems.backup.restores')
                        ->name('restores.rollback');
                    Route::get('/settings', [Systems\AdminSystemBackupController::class, 'settings'])
                        ->middleware('check.menu.access:settings.systems.backup.settings')
                        ->name('settings');
                    Route::post('/settings', [Systems\AdminSystemBackupController::class, 'updateSettings'])
                        ->middleware('check.menu.edit:settings.systems.backup.settings')
                        ->name('settings.update');
                });

                // Audit log (Easy mode: Full)
                Route::get('/logs', [Systems\AdminSystemLogsController::class, 'index'])
                    ->middleware('check.menu.access:settings.systems.logs')
                    ->name('logs.index');

                // File log
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

                // System information (Easy mode: ReadOnly)
                Route::get('/info', [Systems\AdminSystemInfoController::class, 'index'])
                    ->middleware('check.menu.access:settings.systems.info')
                    ->name('info');
            });

            // Logout
            Route::match(['get', 'post'], '/logout', [AdminLoginController::class, 'destroy'])->name('logout');

            // Note: plugin admin panel routes are loaded in PluginServiceProvider::loadPluginRoutes()
            // Not loaded here to allow plugins full control over route names
            // \App\Helpers\PluginHelper::loadEnabledAdminRoutes();

            // Auto-load admin panel routes for activated themes
            \App\Helpers\ThemeHelper::loadEnabledThemeAdminRoutes();
        });
    });
