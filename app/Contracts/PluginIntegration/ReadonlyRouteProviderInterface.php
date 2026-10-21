<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Contracts\PluginIntegration;

use App\Contracts\Plugin\PluginCapabilityInterface;

/**
 * Contract for extensions that declare which state-changing routes a site
 * makes read-only while it runs in restricted (demo) mode.
 *
 * Core keeps the guard and the admin read-only cues; an extension only
 * contributes the set of route names to lock. Implement this interface and
 * register it with a tag in the service container via your ServiceProvider:
 *
 * ```php
 * $this->app->tag([MyReadonlyRouteProvider::class], PluginServiceResolver::CAPABILITY_TAG);
 * ```
 *
 * Additionally, declare "readonly-routes" in the "capabilities" array of
 * plugin.json. Core's read-only route guard aggregates the names from every
 * registered provider via PluginServiceResolver; while no provider is
 * registered it falls back to its own built-in list.
 */
interface ReadonlyRouteProviderInterface extends PluginCapabilityInterface
{
    /**
     * Return the route names made read-only while restricted (demo) mode is on.
     *
     * Each entry is matched exactly, unless it ends with ".*", which makes it
     * a namespace prefix match (e.g. "admin.settings.systems.backup.*"). Only
     * state-changing (non-GET) requests are ever blocked; GET/HEAD/OPTIONS and
     * super-admins always pass through.
     *
     * @return list<string>
     */
    public function getReadonlyRouteNames(): array;
}
