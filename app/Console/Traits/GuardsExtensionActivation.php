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

namespace App\Console\Traits;

use App\Enums\PluginEnableAction;
use App\Services\Extension\ExtensionActivationGate;

/**
 * Applies the admin panel's activation check (health score and signature,
 * per extension security preset) to a console command that installs or
 * enables a plugin or switches the theme (dixlase-core#492).
 *
 * - Blocked is always refused; --force does not override it.
 * - Outcomes the admin panel asks the operator to confirm (WarningRequired,
 *   AcknowledgementRequired) are refused unless --force is passed, and are
 *   reported with a warning when it is.
 * - The Development preset is unrestricted.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
trait GuardsExtensionActivation
{
    /**
     * Whether the extension may proceed. Prints the reason when it may not.
     *
     * @param  'plugin'|'theme'  $kind
     * @param  'installed'|'enabled'|'switched'  $operation
     * @param  bool  $requireConfirmation  false when only a block should stop the
     *                                     operation (a plain install that does not enable)
     */
    protected function passesActivationGate(
        string $kind,
        string $slug,
        string $name,
        string $operation,
        bool $force,
        bool $requireConfirmation = true,
    ): bool {
        $gate = app(ExtensionActivationGate::class);

        if (! $gate->isEnforced()) {
            return true;
        }

        $this->line(__('admin/command/extension-gate.checking', ['name' => $name]));

        $action = $gate->resolve($kind, $slug);
        $replace = [
            'name' => $name,
            'kind' => $kind,
            'slug' => $slug,
            'action' => __('admin/command/extension-gate.action.'.$operation),
        ];

        if ($action === PluginEnableAction::Blocked) {
            $this->error(__('admin/command/extension-gate.blocked', $replace));
            $this->line('  '.__('admin/command/extension-gate.blocked_hint', $replace));

            return false;
        }

        if (! $requireConfirmation || ! $action->requiresWarning()) {
            return true;
        }

        $replace['level'] = __('admin/command/extension-gate.level.'.$action->value);

        if (! $force) {
            $this->error(__('admin/command/extension-gate.confirmation_required', $replace));
            $this->line('  '.__('admin/command/extension-gate.confirmation_hint', $replace));

            return false;
        }

        $this->warn(__('admin/command/extension-gate.forced', $replace));

        return true;
    }
}
