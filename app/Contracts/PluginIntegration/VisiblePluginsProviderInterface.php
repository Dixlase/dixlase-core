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
 * Contract for extensions that restrict which plugins appear on the plugins
 * admin screen while a site runs in a curated (e.g. demo) mode.
 *
 * Core keeps the plugins screen and its listing; an extension only contributes
 * an allowlist of plugin slugs. Implement this interface and register it with a
 * tag in the service container via your ServiceProvider:
 *
 * ```php
 * $this->app->tag([MyVisiblePluginsProvider::class], PluginServiceResolver::CAPABILITY_TAG);
 * ```
 *
 * Additionally, declare "visible-plugins" in the "capabilities" array of
 * plugin.json. Core aggregates the slugs from every registered provider via
 * PluginServiceResolver; while no provider is registered, every plugin is shown
 * (no restriction).
 */
interface VisiblePluginsProviderInterface extends PluginCapabilityInterface
{
    /**
     * Return the plugin slugs that may appear on the plugins admin screen, or
     * null to impose no restriction.
     *
     * Slugs from every registered provider are merged: a plugin is shown when
     * at least one provider lists it. Returning an empty array hides every
     * plugin. Both the installed and the uninstalled lists are filtered.
     *
     * @return list<string>|null
     */
    public function getVisiblePluginSlugs(): ?array;
}
