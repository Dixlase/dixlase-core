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

use App\Helpers\PluginHelper;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\Front\FrontCustomAssetController;
use App\Http\Controllers\Front\FrontWelcomeController;
use App\Http\Controllers\Front\LocaleSwitchController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// CSP violation report endpoint (no auth, session/CSP middleware excluded).
Route::post('/csp-report', [CspReportController::class, 'report'])
    ->name('csp.report')
    ->withoutMiddleware([
        \App\Http\Middleware\ContentSecurityPolicy::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ]);

// Theme/admin/plugin static asset delivery.
// Session/CSRF middleware are excluded so concurrent GETs don't fight over
// the session id and accidentally invalidate the admin's session.
Route::get('assets/{type}/{file}', function ($type, $file) {
    $basePath = match ($type) {
        'theme' => base_path('themes/'.getActiveThemeDirectory().'/assets'),
        'admin' => base_path('resources/admin/assets'),
        'plugin' => base_path("plugins/{$file}/assets"),
        default => abort(404),
    };

    $filePath = "{$basePath}/{$file}";
    if (! File::exists($filePath)) {
        abort(404);
    }

    return response()->file($filePath);
})
    ->where('file', '.*')
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ]);

// Language switcher endpoint. Writes the dixlase_locale cookie and
// 302-redirects the visitor. Kept as Plugin API even when no locale URL
// routing is active so the multilingual plugin can wire it up later.
Route::post('/locale/switch', LocaleSwitchController::class)
    ->name('locale.switch')
    ->middleware('web');

// Front page custom JS/CSS external file delivery.
Route::get('/front/custom-script.js', [FrontCustomAssetController::class, 'script'])
    ->name('front.custom-script')
    ->middleware('front.ip')
    ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

Route::get('/front/custom-style.css', [FrontCustomAssetController::class, 'style'])
    ->name('front.custom-style')
    ->middleware('front.ip')
    ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

// Front-end routes.
//
// v0.1.0 ships without active locale URL routing: visiting / serves the
// welcome page directly without a /{locale}/ redirect, and plugin web
// routes mount at their declared paths without a locale prefix.
//
// The locale infrastructure (LocaleHelper, SetFrontLocale middleware,
// LocalizedUrlProvider / MissingTranslationHandler contracts, the
// /locale/switch endpoint above) is in place so the future multilingual
// plugin (DixlaseI18n) can opt-in by wrapping these routes in a
// Route::prefix('{locale}')->where(...)->middleware(SetFrontLocale)
// group and registering its own Route::fallback() that redirects
// locale-less URLs.
Route::middleware(['web', 'front.ip'])->group(function () {
    Route::get('/', [FrontWelcomeController::class, 'index'])->name('welcome');

    // Front log test route (development only).
    Route::get('/test-front-log', function () {
        \Illuminate\Support\Facades\Log::channel('front_activity')->info('Front activity log test', [
            'action' => 'page_view',
            'page' => 'test_page',
            'user_id' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        \Illuminate\Support\Facades\Log::channel('front_error')->error('Front error log test', [
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
