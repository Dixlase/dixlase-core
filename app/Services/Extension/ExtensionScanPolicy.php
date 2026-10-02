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

namespace App\Services\Extension;

use App\Services\SecuritySettingsRegistry;

/**
 * Whether the extension security preset requires a scan before an
 * extension is installed.
 *
 * Shared by the plugin and theme install paths so that both apply the
 * same rule; before this the check lived in the plugin controller only,
 * and a theme -- which runs PHP just like a plugin -- was installed under
 * the Strict preset without any scan at all.
 */
class ExtensionScanPolicy
{
    /**
     * Strict / Balanced → true
     * Development → false
     * Custom → true if require_signature or require_permission_definition
     *          or permission_mismatch_action=block
     */
    public static function isScanRequired(): bool
    {
        $preset = SecuritySettingsRegistry::get('extension_security_preset', 'balanced');

        return match ($preset) {
            'strict', 'balanced' => true,
            'development' => false,
            'custom' => SecuritySettingsRegistry::get('extension_require_signature', false)
                || SecuritySettingsRegistry::get('extension_require_permission_definition', false)
                || SecuritySettingsRegistry::get('extension_permission_mismatch_action', 'warn') === 'block',
            default => true,
        };
    }
}
