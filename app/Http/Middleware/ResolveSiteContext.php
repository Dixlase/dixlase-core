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

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Site\SiteContextInterface;
use App\Helpers\LocaleHelper;
use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current site context for the request.
 *
 * In v0.1.0 the primary site (id=1) is always selected. The middleware
 * is placed in the request lifecycle now so that v2 can introduce
 * hostname-based or path-prefix-based resolution without changing the
 * registration point.
 *
 * Resolution order (when implemented in later phases):
 *   1. Exact host match (sites.host)
 *   2. Host + path prefix match (sites.host + sites.path_prefix)
 *   3. Path-prefix-only match against primary site host
 *   4. Fallback to the primary active site
 */
class ResolveSiteContext
{
    public function __construct(
        private readonly SiteContextInterface $siteContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $primaryLocale = null;

        // The middleware is registered globally and therefore runs during
        // /install before the database is provisioned. Wrap all DB probes
        // so that connection / permission / missing-table errors do not
        // prevent CheckInstallationReady from routing the user to the
        // installer. Once installed, normal resolution resumes.
        try {
            if (Schema::hasTable('sites')) {
                $site = $this->resolveSite($request);
                if ($site !== null) {
                    $this->siteContext->setCurrent($site->id);
                    $primaryLocale = $site->primary_locale;
                }
            }
        } catch (\Throwable $e) {
            // DB unreachable, missing privileges, or tables not yet
            // migrated. Leave context unset; downstream middleware
            // (CheckInstallationReady) handles the install redirect.
        }

        // Seed URL::defaults('locale') with the site's primary locale so
        // any code that calls route() for a locale-prefixed front route
        // (e.g. admin "view on front" links) generates a working URL even
        // before SetFrontLocale / SetAdminLocale narrow the choice down.
        $defaultLocale = is_string($primaryLocale) && LocaleHelper::isSupported($primaryLocale)
            ? $primaryLocale
            : (string) config('app.fallback_locale', LocaleHelper::getDefaultLocale());
        URL::defaults(['locale' => $defaultLocale]);

        return $next($request);
    }

    /**
     * Resolve the site for the given request.
     *
     * v0.1.0: always returns the primary site. Future phases will add
     * hostname / path-prefix resolution here without changing callers.
     */
    private function resolveSite(Request $request): ?Site
    {
        return Site::primary();
    }
}
