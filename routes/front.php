<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

use App\Helpers\PluginHelper;
use App\Helpers\ThemeHelper;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\Front\FrontCustomAssetController;
use App\Http\Controllers\Front\FrontWelcomeController;
use App\Http\Controllers\Front\LocaleSwitchController;
use App\Http\Middleware\ContentSecurityPolicy;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// CSP violation report endpoint (no auth, session/CSP middleware excluded).
Route::post('/csp-report', [CspReportController::class, 'report'])
    ->name('csp.report')
    ->withoutMiddleware([
        ContentSecurityPolicy::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        AddQueuedCookiesToResponse::class,
        PreventRequestForgery::class,
    ]);

// Theme/admin/plugin static asset delivery.
// Session/CSRF middleware are excluded so concurrent GETs don't fight over
// the session id and accidentally invalidate the admin's session.
Route::get('assets/{type}/{file}', function ($type, $file) {
    // Reject path traversal before $file is used to build any filesystem
    // path. $file feeds both the plugin directory segment and the file name
    // below, so a single "../" would otherwise escape the assets root and
    // expose arbitrary files (.env, logs, database) to anonymous requests.
    if (str_contains($file, '..') || str_contains($file, "\0")) {
        abort(404);
    }

    $basePath = match ($type) {
        // ThemeHelper::getActiveThemePath() returns null when no theme is
        // active; abort rather than let that concatenate into an absolute
        // "/assets" root. (The realpath containment check below would also
        // reject it, but this route must not lean on a later guard to be
        // safe.)
        'theme' => (ThemeHelper::getActiveThemePath() ?? abort(404)).'/assets',
        'admin' => base_path('resources/admin/assets'),
        'plugin' => base_path("plugins/{$file}/assets"),
        default => abort(404),
    };

    // Containment check: the resolved target must stay inside the resolved
    // base directory. realpath() returns false for a nonexistent path, which
    // also covers the previous File::exists() guard.
    $realBase = realpath($basePath);
    $realFile = realpath("{$basePath}/{$file}");
    if ($realBase === false || $realFile === false
        || ! str_starts_with($realFile, $realBase.DIRECTORY_SEPARATOR)) {
        abort(404);
    }

    return response()->file($realFile);
})
    ->where('file', '.*')
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        AddQueuedCookiesToResponse::class,
        PreventRequestForgery::class,
    ]);

// Language switcher endpoint. Writes the dixlase_locale cookie and
// 302-redirects the visitor. Kept as Plugin API even when no locale URL
// routing is active so the multilingual plugin can wire it up later.
Route::post('/locale/switch', LocaleSwitchController::class)
    ->name('locale.switch')
    ->middleware('web');

// Admin-bar logout endpoint mounted on the web side, used when the
// admin bar is rendered on a front-end page. The standard admin-side
// logout (`admin.logout`) lives under the admin URL prefix, where the
// guard-aware session driver swaps in the `members_sessions` table.
// The form on the front page is rendered with the web guard's CSRF
// token (`sessions` table), so submitting against the admin-side route
// always fails CSRF validation with 419. Posting to this web-side
// endpoint keeps token issuance and validation on the same session
// record. The controller handler additionally clears the parallel
// `members_sessions` row to drop the member's auth state in the admin
// guard's table as well.
Route::post('/admin-bar/logout', [AdminLoginController::class, 'destroyFromAdminBar'])
    ->name('admin-bar.logout')
    ->middleware('web');

// Front page custom JS/CSS external file delivery.
//
// These are stateless static-asset endpoints: no session, no CSRF, no
// CSP processing. They're loaded as <link>/<script> subresources by
// the admin theme-settings preview iframe AND by public-facing pages,
// both contexts of which already issue their own session cookies.
// Running StartSession here would route the request through the
// guard-aware session handler against the *guest* `sessions` table
// (because the URL doesn't start with the admin URL prefix), and a
// missing row on that side caused Laravel to rotate the session id —
// the next admin POST then saw a CSRF mismatch and returned 419 on
// the theme settings save.
Route::get('/front/custom-script.js', [FrontCustomAssetController::class, 'script'])
    ->name('front.custom-script')
    ->middleware('front.ip')
    ->withoutMiddleware([
        ContentSecurityPolicy::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
    ]);

Route::get('/front/custom-style.css', [FrontCustomAssetController::class, 'style'])
    ->name('front.custom-style')
    ->middleware('front.ip')
    ->withoutMiddleware([
        ContentSecurityPolicy::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
    ]);

// Front-end routes.
//
// v0.1.0 ships without active locale URL routing: visiting / serves the
// welcome page directly without a /{locale}/ redirect, and plugin web
// routes mount at their declared paths without a locale prefix.
//
// The locale infrastructure (LocaleHelper, SetFrontLocale middleware,
// LocalizedUrlProvider / MissingTranslationHandler contracts, the
// /locale/switch endpoint above) is in place so any multilingual plugin
// (first-party, third-party, or a custom in-house implementation) that
// wires itself to the same contracts can opt-in by wrapping these
// routes in a Route::prefix('{locale}')->where(...)->middleware(SetFrontLocale)
// group and registering its own Route::fallback() that redirects
// locale-less URLs.
Route::middleware(['web', 'front.ip'])->group(function () {
    Route::get('/', [FrontWelcomeController::class, 'index'])->name('welcome');

    // Front log test route (development only).
    Route::get('/test-front-log', function () {
        Log::channel('front_activity')->info('Front activity log test', [
            'action' => 'page_view',
            'page' => 'test_page',
            'user_id' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        Log::channel('front_error')->error('Front error log test', [
            'error' => 'test_error',
            'error_type' => 'test_error',
            'user_id' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Front log test executed.',
            'logs' => [
                'front_activity' => 'storage/logs/front_activity.log or front_activity-'.now()->format('Y-m-d').'.log',
                'front_error' => 'storage/logs/front_error.log or front_error-'.now()->format('Y-m-d').'.log',
            ],
            'admin_url' => route('admin.settings.systems.logs.files', ['type' => 'front_activity']),
        ]);
    })->name('test.front.log');

    // Plugin web routes. v0.1.0 mounts them at their declared paths;
    // the multilingual plugin can opt-in by wrapping its loader inside
    // a Route::prefix('{locale}') group within its own ServiceProvider.
    PluginHelper::loadEnabledWebRoutes();
});
