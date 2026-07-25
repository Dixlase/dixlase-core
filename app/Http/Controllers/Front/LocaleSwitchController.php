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

namespace App\Http\Controllers\Front;

use App\Helpers\LocaleHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Persists the visitor's explicit locale choice and bounces them to the
 * same page under the chosen locale.
 *
 * Only this endpoint writes the dixlase_locale cookie. SetFrontLocale
 * reads the cookie but never writes it, so a casual /ja/ visit does not
 * pin the visitor to Japanese for subsequent locale-less requests.
 */
class LocaleSwitchController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = (string) $request->input('locale', '');
        if (! LocaleHelper::isSupported($locale)) {
            return back()->withErrors(['locale' => 'Unsupported locale.']);
        }

        $target = $this->resolveTarget($request, $locale);

        return redirect($target, 302)
            ->withCookie(cookie(
                name: LocaleHelper::COOKIE_NAME,
                value: $locale,
                minutes: LocaleHelper::COOKIE_LIFETIME_MINUTES,
                path: '/',
                domain: null,
                secure: $request->isSecure(),
                httpOnly: false, // readable by JS so language switchers can update without a round-trip
                raw: false,
                sameSite: 'lax',
            ));
    }

    /**
     * Determine the URL to bounce the visitor to. Preference order:
     *   1. ?redirect= query param, swapped to the new locale prefix
     *   2. Referer header, swapped to the new locale prefix
     *   3. /{locale}/
     */
    private function resolveTarget(Request $request, string $locale): string
    {
        $candidate = (string) $request->input('redirect', '');
        if ($candidate === '') {
            $candidate = (string) $request->headers->get('referer', '');
        }

        if ($candidate !== '') {
            $path = $this->extractPath($candidate, $request->getSchemeAndHttpHost());
            if ($path !== null) {
                return $this->swapLocale($path, $locale);
            }
        }

        return '/'.$locale;
    }

    /**
     * Reduce an absolute URL to its path+query, accepting only same-host
     * URLs. Returns null when the URL is off-site (open-redirect guard).
     */
    private function extractPath(string $url, string $expectedHost): ?string
    {
        // Already a relative path
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $parsed = parse_url($url);
        if ($parsed === false || ! isset($parsed['scheme'], $parsed['host'])) {
            return null;
        }

        $candidateHost = $parsed['scheme'].'://'.$parsed['host'].(isset($parsed['port']) ? ':'.$parsed['port'] : '');
        if ($candidateHost !== $expectedHost) {
            return null;
        }

        $path = $parsed['path'] ?? '/';
        if (isset($parsed['query'])) {
            $path .= '?'.$parsed['query'];
        }

        return $path;
    }

    /**
     * Swap or insert a locale segment in the given path.
     */
    private function swapLocale(string $path, string $locale): string
    {
        [$pathOnly, $query] = array_pad(explode('?', $path, 2), 2, null);

        $segments = explode('/', trim((string) $pathOnly, '/'));

        if (isset($segments[0]) && LocaleHelper::isSupported($segments[0])) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        $rebuilt = '/'.implode('/', array_filter($segments, static fn ($s) => $s !== ''));

        return $rebuilt.($query !== null && $query !== '' ? '?'.$query : '');
    }
}
