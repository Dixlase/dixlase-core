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

namespace App\Services\Extension;

use App\DTO\Plugin\HealthScoreResult;
use App\Enums\ExtensionSecurityLevel;
use App\Enums\ExtensionSecurityPreset;
use App\Enums\PluginEnableAction;
use App\Services\SecuritySettingsRegistry;

/**
 * Resolves the activation action (allow / warn / acknowledge / block) for an
 * installed extension from its health score and the site's extension security
 * settings.
 *
 * The decision logic is identical for plugins and themes; the only per-type
 * difference is which "max health level" setting gates activation:
 * `extension_plugin_max_health_level` for plugins,
 * `extension_theme_max_health_level` for themes. Extracted from
 * PluginHealthScorer so both extension types share a single implementation
 * instead of one of them silently skipping the check.
 *
 * @internal For Core use only. Do not reference from plugins/themes.
 */
class ExtensionEnableActionResolver
{
    /**
     * Resolve the enable action for an extension.
     *
     * When $maxAllowedLevel is null it is derived from the active extension
     * security preset and the per-type max-health-level setting. The
     * Development preset bypasses health gating (max level forced to
     * NotVerified) so low-scoring extensions can still be enabled with a
     * warning.
     *
     * @param  'plugin'|'theme'  $extensionType
     */
    public function resolve(
        HealthScoreResult $result,
        string $extensionType = 'plugin',
        ?ExtensionSecurityLevel $maxAllowedLevel = null,
    ): PluginEnableAction {
        $maxAllowedLevel ??= $this->resolveMaxAllowedLevel($extensionType);

        // Block status that the active security settings do not allow.
        if (! $result->status->canActivate($maxAllowedLevel)) {
            return PluginEnableAction::Blocked;
        }

        // Within the allowed range, grade the action by score.
        if ($result->hasCriticalIssue || $result->score < 50) {
            return PluginEnableAction::AcknowledgementRequired;
        }

        return match (true) {
            $result->score >= 90 => PluginEnableAction::Allowed,
            $result->score >= 70 => PluginEnableAction::WarningRequired,
            default => PluginEnableAction::AcknowledgementRequired,
        };
    }

    /**
     * Derive the maximum health level allowed to activate, from the active
     * security preset and the per-type max-health-level setting.
     *
     * @param  'plugin'|'theme'  $extensionType
     */
    private function resolveMaxAllowedLevel(string $extensionType): ExtensionSecurityLevel
    {
        $preset = (string) SecuritySettingsRegistry::get(
            'extension_security_preset',
            ExtensionSecurityPreset::default()->value,
        );

        // Development preset intentionally bypasses health gating, even if a
        // stricter max-health-level value lingers from a previous preset.
        if ($preset === ExtensionSecurityPreset::Development->value) {
            return ExtensionSecurityLevel::NotVerified;
        }

        $settingKey = $extensionType === 'theme'
            ? 'extension_theme_max_health_level'
            : 'extension_plugin_max_health_level';

        $presetKey = $extensionType === 'theme'
            ? 'theme_max_health_level'
            : 'plugin_max_health_level';

        $default = (int) (ExtensionSecurityPreset::default()->getDefaultSettings()[$presetKey]
            ?? ExtensionSecurityLevel::Warning->value);

        return ExtensionSecurityLevel::from(
            (int) SecuritySettingsRegistry::get($settingKey, $default),
        );
    }
}
