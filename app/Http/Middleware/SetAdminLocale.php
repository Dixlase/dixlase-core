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
use App\Helpers\AdminHelper;
use App\Helpers\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the application locale for admin requests.
 *
 * Admin URLs do not carry a locale prefix; the operator's chosen language
 * is sourced from their member profile. The resolution order is:
 *
 *   1. member.locale       — operator's profile setting
 *   2. Site.primary_locale — site default when the operator has no preference
 *   3. Accept-Language     — for unauthenticated screens (login, password reset)
 *   4. config('app.fallback_locale')
 *
 * Front-end requests are handled separately by SetFrontLocale, which is
 * URL-prefix aware. This middleware is a no-op for non-admin requests.
 */
class SetAdminLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! AdminHelper::isAdminRequest($request)) {
            return $next($request);
        }

        $locale = $this->resolveLocale($request);
        if ($locale !== null && LocaleHelper::isSupported($locale)) {
            app()->setLocale($locale);
            // Update URL::defaults so any front URL generated from an
            // admin page (e.g. "view on front" links) honours the
            // operator's chosen language.
            URL::defaults(['locale' => $locale]);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): ?string
    {
        $memberLocale = $this->getMemberLocale();
        if ($memberLocale !== null) {
            return $memberLocale;
        }

        $siteLocale = LocaleHelper::getSiteDefaultLocale();
        if (LocaleHelper::isSupported($siteLocale)) {
            return $siteLocale;
        }

        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale !== null) {
            return $browserLocale;
        }

        return (string) config('app.fallback_locale', 'en');
    }

    private function getMemberLocale(): ?string
    {
        if (! auth('member')->check()) {
            return null;
        }

        $member = auth('member')->user();
        if ($member === null) {
            return null;
        }

        $locale = $member->locale ?? null;
        if ($locale instanceof Locale) {
            $locale = $locale->value;
        }

        if (! is_string($locale) || $locale === '') {
            return null;
        }

        return LocaleHelper::isSupported($locale) ? $locale : null;
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
