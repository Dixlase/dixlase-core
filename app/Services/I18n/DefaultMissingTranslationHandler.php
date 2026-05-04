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

declare(strict_types=1);

namespace App\Services\I18n;

use App\Contracts\I18n\MissingTranslationHandler;
use App\Contracts\Site\SiteContextInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Core default behaviour for unmatched translations: 302 redirect to the
 * same path under the site's primary locale.
 *
 * Any multilingual plugin (first-party, third-party, or a custom
 * in-house implementation) — or another extension such as a redirects
 * plugin — can rebind MissingTranslationHandler to replace this with a
 * 404, a fallback render, or custom redirect rules.
 */
class DefaultMissingTranslationHandler implements MissingTranslationHandler
{
    public function __construct(
        private readonly SiteContextInterface $siteContext,
    ) {}

    public function handle(Request $request, string $requestedLocale): Response
    {
        $targetLocale = $this->siteContext->currentSite()->primary_locale
            ?? config('app.fallback_locale', 'en');

        // If the requested locale already matches the fallback, returning
        // another redirect to the same URL would loop. Surface a 404 so
        // upstream sees there genuinely is no content.
        if ($requestedLocale === $targetLocale) {
            return response('', Response::HTTP_NOT_FOUND);
        }

        $path = $this->stripLocalePrefix($request->path(), $requestedLocale);
        $query = $request->getQueryString();
        $target = '/'.$targetLocale.($path === '' ? '' : '/'.$path).($query !== null ? '?'.$query : '');

        return redirect($target, Response::HTTP_FOUND);
    }

    private function stripLocalePrefix(string $path, string $locale): string
    {
        $prefix = $locale.'/';
        if (str_starts_with($path, $prefix)) {
            return substr($path, strlen($prefix));
        }
        if ($path === $locale) {
            return '';
        }

        return $path;
    }
}
