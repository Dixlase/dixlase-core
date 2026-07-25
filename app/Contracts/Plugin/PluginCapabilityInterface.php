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

namespace App\Contracts\Plugin;

/**
 * Base interface for plugin capability declaration
 *
 * Base interface to implement when a plugin provides specific capabilities
 * Each capability-specific interface (e.g. MailCapableInterface) inherits this
 *
 * By registering with tags in the service container via the plugin's ServiceProvider,
 * PluginServiceResolver automatically discovers and resolves them
 */
interface PluginCapabilityInterface
{
    /**
     * Get the plugin slug
     *
     * @return string e.g. 'dixlase-inquiry'
     */
    public function getPluginSlug(): string;

    /**
     * Whether this capability is currently available
     *
     * Depending on the plugin settings state and dependencies,
     * the capability may be temporarily disabled
     */
    public function isCapabilityAvailable(): bool;
}
