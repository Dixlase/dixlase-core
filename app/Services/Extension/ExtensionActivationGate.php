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

use App\Enums\ExtensionSecurityPreset;
use App\Enums\PluginEnableAction;
use App\Models\ThemeAudit;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\SecuritySettingsRegistry;
use App\Services\Theme\ThemeHealthScorer;
use Illuminate\Support\Facades\Log;

/**
 * Resolves whether a plugin may be installed / enabled, or a theme switched
 * to, from the command line under the active extension security preset.
 *
 * The admin panel runs ExtensionEnableActionResolver (health score and
 * signature) before it installs or enables a plugin and before it switches
 * the theme. The CLI commands `dls:plugin:install`, `dls:plugin:enable` and
 * `dls:theme:switch` use this service so the preset means the same thing on
 * every path (dixlase-core#492):
 *
 * - The Development preset is unrestricted; nothing is scanned or refused.
 * - Otherwise the extension is scanned when no current scan result exists
 *   (no audit yet, or its files changed since the last one), and the stored
 *   result is resolved by ExtensionEnableActionResolver.
 * - The check fails closed: a scan or score that cannot be computed is
 *   treated as Blocked.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
class ExtensionActivationGate
{
    /**
     * Whether the active preset enforces the gate at all.
     *
     * Only the Development preset is exempt, mirroring the resolver, which
     * also lifts the health and signature requirements under Development.
     */
    public function isEnforced(): bool
    {
        $preset = (string) SecuritySettingsRegistry::get(
            'extension_security_preset',
            ExtensionSecurityPreset::default()->value,
        );

        return $preset !== ExtensionSecurityPreset::Development->value;
    }

    /**
     * Resolve the activation action for an extension, scanning it first when
     * no current scan result exists.
     *
     * Returns Allowed without scanning when the gate is not enforced.
     *
     * @param  'plugin'|'theme'  $kind
     */
    public function resolve(string $kind, string $slug): PluginEnableAction
    {
        if (! $this->isEnforced()) {
            return PluginEnableAction::Allowed;
        }

        try {
            if ($kind === 'theme') {
                $scorer = app(ThemeHealthScorer::class);
                if ($this->themeNeedsScan($scorer, $slug)) {
                    app(ExtensionRescanService::class)->rescanTheme($slug);
                }
            } else {
                $scorer = app(PluginHealthScorer::class);
                if ($scorer->needsRescan($slug)) {
                    app(ExtensionRescanService::class)->rescanPlugin($slug);
                }
            }

            return $scorer->determineEnableAction($scorer->calculate($slug));
        } catch (\Throwable $e) {
            Log::warning('Extension activation check failed', [
                'kind' => $kind,
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return PluginEnableAction::Blocked;
        }
    }

    /**
     * Whether a theme has no current scan result: never audited, or its code
     * files changed since the stored audit. The plugin scorer has the same
     * check as PluginHealthScorer::needsRescan().
     */
    private function themeNeedsScan(ThemeHealthScorer $scorer, string $slug): bool
    {
        $audit = ThemeAudit::getBySlug($slug);

        if ($audit === null || $audit->audited_at === null || $audit->files_hash === null) {
            return true;
        }

        return $scorer->computeFilesHash($slug) !== $audit->files_hash;
    }
}
