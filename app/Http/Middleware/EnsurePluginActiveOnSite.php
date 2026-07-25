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

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Site\SiteContextInterface;
use App\Support\Api\ApiErrorResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate API routes contributed by a plugin behind that plugin's
 * activation status on the resolved site.
 *
 * Auto-attached by App\Helpers\PluginHelper::loadEnabledApiRoutes() to
 * every plugin's routes/api/v1.php so a globally-installed plugin that
 * has been disabled for the current site returns a 404 JSON envelope
 * instead of leaking endpoints. Per-site plugin activation is recorded
 * in dls_site_plugin_activations and exposed by SiteContext.
 *
 * Usage (registered automatically by core; plugin authors do not need
 * to wire it explicitly):
 *
 *   Route::middleware(EnsurePluginActiveOnSite::class.':my-plugin-slug')
 *       ->group(...);
 */
class EnsurePluginActiveOnSite
{
    public function __construct(
        private readonly SiteContextInterface $siteContext,
    ) {}

    public function handle(Request $request, Closure $next, string $pluginSlug): Response
    {
        if (! $this->siteContext->isPluginActive($pluginSlug)) {
            return ApiErrorResponse::make(
                code: 'not_found',
                status: 404,
                message: 'The requested resource was not found.',
            );
        }

        return $next($request);
    }
}
