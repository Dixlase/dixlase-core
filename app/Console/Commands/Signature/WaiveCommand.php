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
use RuntimeException;

/**
 * Record an operator signature waiver for an extension.
 *
 * Waiving suppresses the "invalid / unsigned" warning for an extension the
 * operator deliberately accepts (branded rebuild, local fork, dev work). It is
 * an overlay — the objective verifier status is left untouched.
 */
class WaiveCommand extends AbstractSignatureCommand
{
    protected $signature = 'dls:signature:waive
                            {target : Plugin/theme slug or directory name, or "core" for --scope=core}
                            {--scope=plugin : plugin|theme|core}
                            {--reason= : Why the signature is being waived (required)}
                            {--confirm : Required to actually record the waiver}
                            {--json : Output the result as JSON}';

    protected $description = 'Record an operator waiver so an extension (or core) is no longer flagged as invalid/unsigned';

    public function handle(SignatureWaiverService $service): int
    {
        $scope = $this->resolveScope();
        if (is_int($scope)) {
            return $scope;
        }

        if (! $this->ensureCoreMutationAllowed($scope)) {
            return self::FAILURE;
        }

        $target = (string) $this->argument('target');

        if (! $this->targetExists($scope, $target)) {
            $this->error(sprintf('%s "%s" not found.', ucfirst($scope), $this->canonicalTarget($scope, $target)));

            return self::FAILURE;
        }

        $reason = trim((string) $this->option('reason'));
        if ($reason === '') {
            $this->error('A --reason is required when waiving a signature.');

            return self::FAILURE;
        }

        if (! $this->option('confirm')) {
            $this->warn('This records a signature waiver — the extension will no longer be flagged as invalid/unsigned.');
            $this->line('Re-run with --confirm to proceed. You are responsible for trusting this extension.');

            return self::SUCCESS;
        }

        try {
            $waiver = $service->waive($scope, $target, $reason, null, 'CLI');
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'action' => 'waive',
                'status' => 'waived',
                'scope' => $waiver->scope,
                'target' => $waiver->target_slug,
                'id' => $waiver->id,
                'reason' => $waiver->reason,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info(sprintf('Waived %s "%s" (waiver #%d).', $waiver->scope, $waiver->target_slug, $waiver->id));
        $this->line($waiver->scope === 'core'
            ? 'Run `php artisan dls:core:verify` to see the waived overlay.'
            : 'Run `php artisan dls:plugin:audit '.$waiver->target_slug.'` to refresh the displayed status.');

        return self::SUCCESS;
    }
}
