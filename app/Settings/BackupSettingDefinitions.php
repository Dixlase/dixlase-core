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

namespace App\Settings;

use App\Enums\SettingScope;
use App\Services\Site\SettingDefinition;
use App\Services\Site\SettingDefinitionRegistry;

/**
 * Registers setting definitions for the admin backup screen.
 *
 * `AdminSystemBackupController` reads and writes these keys via
 * `SiteSetting::getValue` / `SiteSetting::setValue`, which routes
 * through `SettingResolver`. The resolver refuses any key that is
 * not registered here — the symptom is a 500 with
 * `UnknownSettingException: Setting key 'backup.default_targets' is
 * not registered in SettingDefinitionRegistry`.
 *
 * Both keys are PerSite: each site stores its own preferred default
 * targets / retention. When unset, the controller falls back to
 * `BackupServiceInterface::getDefaultTargets()` (computed) and to
 * the operator-supplied form value (retention), so a null default
 * here is the right "not configured yet" sentinel.
 */
class BackupSettingDefinitions
{
    public static function register(SettingDefinitionRegistry $registry): void
    {
        // JSON-encoded array of target names (database / media / private
        // / custom / logs / …). Written by the controller as the
        // result of `json_encode($validated['default_targets'])` and
        // read back as a raw string for json_decode().
        $registry->register(new SettingDefinition(
            name: 'backup.default_targets',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));

        // Retention window in days. Stored as a stringified integer
        // because the controller calls `setValue()` with the form
        // input as-is; read with `(int) $stored` after rejecting
        // empty / non-string values.
        $registry->register(new SettingDefinition(
            name: 'backup.default_retention_days',
            scope: SettingScope::PerSite,
            default: null,
            type: 'string',
        ));
    }
}
