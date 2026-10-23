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

use App\Enums\ExtensionCompatibilityStatus;
use App\Exceptions\ExtensionUpdateBlockedException;
use App\Services\Extension\ExtensionCompatibilityChecker;
use App\Services\Extension\ExtensionRefusalReport;

/**
 * Enforces a manifest's requires.dixlase and requires.php where an install
 * or update is decided (dixlase-core#473).
 *
 * - A requirement that is not met refuses the operation; --force does not
 *   override it, because the extension says it will not work here.
 * - A constraint that cannot be parsed is reported as a warning and not
 *   enforced.
 * - Boot does not enforce these, matching the advisory requires.dixlase_api
 *   check in the service providers.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
trait EnforcesExtensionRequirements
{
    /**
     * The refusal message when the manifest declares a requirement this
     * site does not meet, or null. Prints a warning for each constraint
     * that cannot be parsed. A refusal is also recorded for the admin panel.
     *
     * @param  array<string, mixed>  $manifest
     * @param  'installed'|'updated'  $operation
     */
    protected function unmetRequirementMessage(array $manifest, string $name, string $operation): ?string
    {
        $problems = (new ExtensionCompatibilityChecker())->checkRequirements($manifest);
        $version = is_string($manifest['version'] ?? null) ? $manifest['version'] : '';
        $refusals = [];

        foreach ($problems as $problem) {
            if ($problem['status'] === ExtensionCompatibilityStatus::MalformedConstraint) {
                $this->warn(__('admin/command/extension-gate.requires_malformed', [
                    'name' => $name,
                    'key' => $problem['requirement'],
                    'declared' => $problem['declared'],
                ]));

                continue;
            }

            $refusals[] = __('admin/command/extension-gate.requires_unmet', [
                'name' => trim("{$name} {$version}"),
                'requirement' => __('admin/command/extension-gate.requirement.'.$problem['requirement']),
                'declared' => $problem['declared'],
                'current' => $problem['current'],
                'action' => __('admin/command/extension-gate.action.'.$operation),
            ]);
        }

        if ($refusals === []) {
            return null;
        }

        $message = implode("\n", $refusals);
        app(ExtensionRefusalReport::class)->record($message);

        return $message;
    }

    /**
     * Refuse an update whose extracted manifest declares a requirement this
     * site does not meet. Throwing hands control to the update command's
     * failure path, which restores the previous version.
     *
     * @throws ExtensionUpdateBlockedException
     */
    protected function refuseUnmetRequirements(string $manifestPath, string $name): void
    {
        $data = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : null;
        if (! is_array($data)) {
            return;
        }

        $message = $this->unmetRequirementMessage($data, $name, 'updated');
        if ($message !== null) {
            throw new ExtensionUpdateBlockedException($message);
        }
    }
}
