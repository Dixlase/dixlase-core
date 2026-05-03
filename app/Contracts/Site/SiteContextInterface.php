<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Contracts\Site;

use App\Models\Site;
use Illuminate\Database\ConnectionInterface;

/**
 * Provides the current site context for the request.
 *
 * In v0.1.0 the primary site (id=1) is always returned. Future versions
 * will resolve the site from request hostname, path prefix, or the
 * admin session, without changing this contract.
 */
interface SiteContextInterface
{
    /**
     * Get the ID of the current site.
     */
    public function currentSiteId(): int;

    /**
     * Get the current Site model.
     */
    public function currentSite(): Site;

    /**
     * Set the current site by ID.
     *
     * Used by the resolution middleware and the admin site switcher.
     */
    public function setCurrent(int $siteId): void;

    /**
     * Check whether the given plugin slug is active for the current site.
     *
     * Phase 4 will route this through site_plugin_activations. For v0.1.0
     * the implementation falls back to the global plugin enabled flag.
     */
    public function isPluginActive(string $slug): bool;

    /**
     * Check whether the given theme slug is active for the current site.
     *
     * Phase 4 will route this through site_theme_activations. For v0.1.0
     * the implementation falls back to the global active theme.
     */
    public function isThemeActive(string $slug): bool;

    /**
     * Resolve the database connection for the current site.
     *
     * In v0.1.0 always returns the default connection. When DB-per-site
     * is introduced in v2 this returns the dedicated connection without
     * callers needing to change.
     */
    public function connection(): ConnectionInterface;
}
