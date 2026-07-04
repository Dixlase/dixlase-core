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

namespace App\Contracts\PluginIntegration;

use App\DTO\PluginIntegration\DeployProtectionSource;

/**
 * Aggregated view of every "protect from cross-environment sync
 * overwrite" declaration made by installed plugins and themes.
 *
 * The problem this contract solves is direction-independent: extensions
 * that store user-generated production data (cookie consents, contact
 * inquiries, uploaded attachments, prod-signed keys) must never have
 * that data replaced by a sync from a different environment (dev,
 * typically). The catch is that each plugin's own runtime shape is
 * only known to that plugin — the deploy tool cannot guess which
 * table names or storage paths matter without an authoritative list
 * from the plugin itself.
 *
 * Extensions declare their protected surface in the `deploy` section
 * of `plugin.json` / `theme.json`:
 *
 *   {
 *     "name": "DixlaseLegal",
 *     ...
 *     "deploy": {
 *       "protected_tables": [
 *         "dls_plg_dixlase_legal_cookie_consents"
 *       ],
 *       "protected_storage_paths": [
 *         "inquiries/attachments/"
 *       ]
 *     }
 *   }
 *
 * Deploy tools resolve the registry through the container and union
 * its output into their exclude / protection list — the same additive
 * shape used by `dixlase-deploy.json`'s operator-side `protectedTables`
 * so no privilege boundary changes.
 *
 * Registration is declarative — extensions do not need to bind a
 * service or tag a class. The Core registry reads `plugin.json` /
 * `theme.json` directly at first use. A malformed `deploy` section
 * (missing keys, non-array values) causes only that extension's
 * contribution to be dropped, not a fatal error, so a bad manifest
 * does not disable protection for the rest of the fleet.
 *
 * Resolution:
 *
 *   $registry = app(DeployProtectionRegistryInterface::class);
 *   $tables   = $registry->protectedTables();
 *   $paths    = $registry->protectedStoragePaths();
 *
 * Implementations MUST cache the result within a request lifecycle
 * — repeatedly walking the plugins/ tree on every call would multiply
 * the boot cost of every deploy operation.
 */
interface DeployProtectionRegistryInterface
{
    /**
     * Every table name declared by any installed extension. De-duplicated
     * across extensions; ordering is not guaranteed. Returns an empty
     * array when no extension declares protection.
     *
     * @return list<string>
     */
    public function protectedTables(): array;

    /**
     * Every storage path (relative to `storage/app/private/`) declared
     * by any installed extension. Same shape guarantees as
     * `protectedTables()`.
     *
     * @return list<string>
     */
    public function protectedStoragePaths(): array;

    /**
     * Per-extension breakdown — deploy tools use this to render a
     * "protection audit" line so the operator can see who contributes
     * what before the first push.
     *
     * @return list<DeployProtectionSource>
     */
    public function sources(): array;
}
