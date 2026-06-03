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

namespace App\Console\Commands\Signature;

use App\Models\SignatureWaiver;
use App\Services\Signature\SignatureWaiverService;

/**
 * List active signature waivers (read-only, all scopes).
 */
class WaiversCommand extends AbstractSignatureCommand
{
    protected $signature = 'dls:signature:waivers
                            {--scope= : Optional filter: plugin|theme|core}
                            {--json : Output the result as JSON}';

    protected $description = 'List active signature waivers';

    public function handle(SignatureWaiverService $service): int
    {
        $scope = (string) $this->option('scope');

        if ($scope !== '' && ! in_array($scope, SignatureWaiver::SCOPES, true)) {
            $this->error(sprintf(
                'Unknown --scope "%s". Expected one of: %s.',
                $scope,
                implode(', ', SignatureWaiver::SCOPES)
            ));

            return self::FAILURE;
        }

        $waivers = $service->listActiveWaivers($scope !== '' ? $scope : null);

        if ($this->option('json')) {
            $this->line(json_encode([
                'action' => 'waivers',
                'count' => $waivers->count(),
                'waivers' => $waivers->map(fn (SignatureWaiver $w) => [
                    'id' => $w->id,
                    'scope' => $w->scope,
                    'target' => $w->target_slug,
                    'waived_at' => optional($w->waived_at)->toIso8601String(),
                    'waived_by_label' => $w->waived_by_label,
                    'reason' => $w->reason,
                ])->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if ($waivers->isEmpty()) {
            $this->info('No active signature waivers.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Scope', 'Target', 'Waived at', 'By', 'Reason'],
            $waivers->map(fn (SignatureWaiver $w) => [
                $w->id,
                $w->scope,
                $w->target_slug,
                optional($w->waived_at)->toDateTimeString(),
                $w->waived_by_label ?? '-',
                $w->reason ?? '-',
            ])->all()
        );

        return self::SUCCESS;
    }
}
