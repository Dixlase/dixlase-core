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

use App\Contracts\Site\SiteContextInterface;
use App\Helpers\LocaleHelper;
use App\Helpers\PluginHelper;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\Front\FrontCustomAssetController;
use App\Http\Controllers\Front\FrontWelcomeController;
use App\Http\Middleware\SetFrontLocale;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// CSP violation report endpoint (no auth, session/CSP middleware excluded).
// Stays outside the locale group because a single canonical URL is required.
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
// Stays outside the locale group: assets are language-neutral and need a
// single canonical URL so the browser cache key is shared across locales.
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

// Front page custom JS/CSS external file delivery.
// Stays outside the locale group so the URL stays cache-key-stable.
Route::get('/front/custom-script.js', [FrontCustomAssetController::class, 'script'])
    ->name('front.custom-script')
    ->middleware('front.ip')
    ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

Route::get('/front/custom-style.css', [FrontCustomAssetController::class, 'style'])
    ->name('front.custom-style')
    ->middleware('front.ip')
    ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

// Locale-prefixed front-end routes.
// Plugin web routes are loaded inside this group so they inherit the
// /{locale}/ prefix and the SetFrontLocale resolution.
Route::prefix('{locale}')
    ->where(['locale' => 'ja|en'])
    ->middleware(['web', 'front.ip', SetFrontLocale::class])
    ->group(function () {
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

        // Plugin web routes (auto-wrapped in the locale group so plugin
        // authors can write ordinary route definitions).
        PluginHelper::loadEnabledWebRoutes();
    });

// Catch-all for locale-less front URLs: 302 redirect to the same path
// under a resolved locale. Runs after every locale-prefixed route has
// had a chance to match. Resolution mirrors SetFrontLocale (minus URL):
// Cookie > Accept-Language > Site.primary_locale > config fallback.
// 302 (not 301) keeps v2 free to change the strategy without poisoning
// caches.
Route::get('/{any?}', function ($any = '') {
    // If the path already begins with a supported locale, the request
    // legitimately reached the catch-all because no locale-group route
    // matched (i.e. genuine 404). Don't double-prefix the path.
    $first = explode('/', trim((string) $any, '/'))[0] ?? '';
    if ($first !== '' && LocaleHelper::isSupported($first)) {
        abort(404);
    }

    $locale = LocaleHelper::getCookieLocale();

    if ($locale === null) {
        $header = request()->header('Accept-Language');
        if (is_string($header) && $header !== '') {
            foreach (explode(',', $header) as $entry) {
                $code = strtolower(substr(trim(explode(';', $entry)[0]), 0, 2));
                if ($code !== '' && LocaleHelper::isSupported($code)) {
                    $locale = $code;
                    break;
                }
            }
        }
    }

    if ($locale === null) {
        try {
            $siteLocale = app(SiteContextInterface::class)->currentSite()->primary_locale ?? null;
            if (is_string($siteLocale) && LocaleHelper::isSupported($siteLocale)) {
                $locale = $siteLocale;
            }
        } catch (\Throwable) {
            // SiteContext may be unavailable during install; fall through.
        }
    }

    if ($locale === null) {
        $fallback = (string) config('app.fallback_locale', LocaleHelper::getDefaultLocale());
        $locale = LocaleHelper::isSupported($fallback) ? $fallback : LocaleHelper::getDefaultLocale();
    }

    $query = request()->getQueryString();
    $target = '/'.$locale.($any === '' ? '' : '/'.ltrim($any, '/')).($query !== null ? '?'.$query : '');

    return redirect($target, 302);
})->where('any', '.*')->name('locale.fallback');
