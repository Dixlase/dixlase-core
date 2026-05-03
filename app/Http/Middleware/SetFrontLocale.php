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

namespace App\Http\Middleware;

use App\Helpers\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the application locale for front-end requests.
 *
 * Designed to run inside the locale-prefixed route group, so the URL
 * segment is the canonical source of truth. The resolution order is:
 *
 *   1. URL prefix              — e.g. /ja/about => 'ja'
 *   2. Cookie 'dixlase_locale' — written only by the language switcher
 *   3. Accept-Language header  — first supported locale found
 *   4. Site.primary_locale     — site default
 *   5. config('app.fallback_locale')
 *
 * The resolved locale is registered as a URL::defaults() so subsequent
 * route() calls preserve it automatically. The cookie is intentionally
 * NOT written here — only the /locale/switch endpoint persists the
 * visitor's explicit choice.
 */
class SetFrontLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $urlLocale = $this->getUrlLocale($request);
        if ($urlLocale !== null) {
            return $urlLocale;
        }

        $cookieLocale = LocaleHelper::getCookieLocale();
        if ($cookieLocale !== null) {
            return $cookieLocale;
        }

        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale !== null) {
            return $browserLocale;
        }

        $siteLocale = LocaleHelper::getSiteDefaultLocale();
        if (LocaleHelper::isSupported($siteLocale)) {
            return $siteLocale;
        }

        $fallback = (string) config('app.fallback_locale', 'en');

        return LocaleHelper::isSupported($fallback) ? $fallback : LocaleHelper::getDefaultLocale();
    }

    private function getUrlLocale(Request $request): ?string
    {
        $route = $request->route();
        if ($route !== null) {
            $param = $route->parameter('locale');
            if (is_string($param) && LocaleHelper::isSupported($param)) {
                return $param;
            }
        }

        // Fallback: extract from path when middleware runs before route binding.
        $first = explode('/', trim($request->path(), '/'))[0] ?? null;
        if (is_string($first) && LocaleHelper::isSupported($first)) {
            return $first;
        }

        return null;
    }

    private function getBrowserLocale(Request $request): ?string
    {
        $header = $request->header('Accept-Language');
        if (! is_string($header) || $header === '') {
            return null;
        }

        foreach (explode(',', $header) as $entry) {
            $tag = trim(explode(';', $entry)[0]);
            $code = strtolower(substr($tag, 0, 2));
            if ($code !== '' && LocaleHelper::isSupported($code)) {
                return $code;
            }
        }

        return null;
    }
}
