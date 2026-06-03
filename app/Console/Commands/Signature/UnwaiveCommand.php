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

use App\Services\Signature\SignatureWaiverService;

/**
 * Revoke the active signature waiver for an extension.
 *
 * The extension's signature warning is restored; the historical waiver row is
 * preserved (revoked_at set, active sentinel cleared).
 */
class UnwaiveCommand extends AbstractSignatureCommand
{
    protected $signature = 'dls:signature:unwaive
                            {target : Plugin slug or directory name}
                            {--scope=plugin : plugin|theme|core (Phase 1: plugin only)}
                            {--json : Output the result as JSON}';

    protected $description = 'Revoke the active signature waiver for an extension (restores its warning)';

    public function handle(SignatureWaiverService $service): int
    {
        $scope = $this->resolveScope();
        if (is_int($scope)) {
            return $scope;
        }

        $target = (string) $this->argument('target');

        $revoked = $service->unwaive($scope, $target);

        if ($this->option('json')) {
            $this->line(json_encode([
                'action' => 'unwaive',
                'status' => $revoked ? 'revoked' : 'no_active_waiver',
                'scope' => $scope,
                'target' => $this->directoryName($target),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if (! $revoked) {
            $this->warn(sprintf('No active waiver found for %s "%s".', $scope, $this->directoryName($target)));

            return self::SUCCESS;
        }

        $this->info(sprintf('Revoked the waiver for %s "%s".', $scope, $this->directoryName($target)));
        $this->line('Run `php artisan dls:plugin:audit '.$this->directoryName($target).'` to refresh the displayed status.');

        return self::SUCCESS;
    }
}
