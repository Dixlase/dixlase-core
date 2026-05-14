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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Helpers\ConfigHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetMemberLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Process only if .env file exists, installation is complete, and in admin panel.
            // config('app.installed') is consulted first because env() returns
            // null after Laravel's config cache is built.
            if (file_exists(base_path('.env')) && (config('app.installed', false) ?: env('INSTALLED', false)) && \App\Helpers\AdminHelper::isAdminRequest($request)) {
                // Check authentication state
                $isAuthenticated = Auth::guard('member')->check();
                $member = Auth::guard('member')->user();

                if ($isAuthenticated && $member) {
                    // Prioritize member's individual language settings, use system default if null or empty
                    $memberLocale = $member->locale;

                    // Get value if Enum, use as-is if string
                    if ($memberLocale instanceof \App\Enums\Locale) {
                        $locale = $memberLocale->value;
                    } elseif (is_string($memberLocale) && ! empty($memberLocale)) {
                        $locale = $memberLocale;
                    } else {
                        // Use system default if member settings are not available
                        $locale = ConfigHelper::getAppLocale();
                    }

                    // Check if language is available
                    if (Locale::isValid($locale)) {
                        App::setLocale($locale);
                    } else {
                        // Fallback to system default if language is invalid
                        $fallbackLocale = ConfigHelper::getAppLocale();
                        App::setLocale($fallbackLocale);
                    }
                }
            } elseif ($request->is('install*')) {
                // Get language from session if on installation screen
                try {
                    $installLocale = session('install_locale', 'ja');
                    if (Locale::isValid($installLocale)) {
                        App::setLocale($installLocale);
                    }
                } catch (\Exception $sessionError) {
                    // Use default language if session error occurs
                    App::setLocale('ja');
                }
            }
        } catch (\Exception $e) {
            // Log and skip if database error or other errors occur
            \Log::warning('SetMemberLocale middleware error: '.$e->getMessage());

            // Fallback: set default language
            App::setLocale(config('app.locale', 'ja'));
        }

        return $next($request);
    }
}
