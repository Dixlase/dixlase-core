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

namespace App\Console\Commands\Core;

use App\DTO\Core\CoreIntegrityResult;
use App\Services\Core\CoreIntegrityVerifier;
use Illuminate\Console\Command;

/**
 * Verify Core integrity against its signed manifest.
 *
 * This is also the out-of-band verification entry point: run it from a
 * known-good PHP for assurance that does not depend on the running app being
 * untampered (see .claude/plans/core-signing.md §2).
 */
class VerifyCoreCommand extends Command
{
    protected $signature = 'dls:core:verify
                            {--json : Output the result as JSON}
                            {--base-path= : Override the core root (testing only)}';

    protected $description = 'Verify Core integrity (genuine / modified / unsigned) against its signed manifest';

    public function handle(CoreIntegrityVerifier $verifier): int
    {
        $result = $verifier->verify($this->option('base-path') ?: null);

        if ($this->option('json')) {
            $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $this->exitCode($result);
        }

        $this->renderHuman($result);

        return $this->exitCode($result);
    }

    protected function renderHuman(CoreIntegrityResult $result): void
    {
        [$icon, $label] = match ($result->status) {
            CoreIntegrityResult::STATUS_GENUINE => ['<fg=green>🟢</>', '<fg=green>GENUINE</>'],
            CoreIntegrityResult::STATUS_MODIFIED => ['<fg=yellow>🟡</>', '<fg=yellow>MODIFIED</>'],
            CoreIntegrityResult::STATUS_UNSIGNED => ['<fg=gray>⚪</>', '<fg=gray>UNSIGNED</>'],
            CoreIntegrityResult::STATUS_PENDING => ['⏳', '<fg=cyan>PENDING</>'],
            CoreIntegrityResult::STATUS_INVALID => ['<fg=red>🔴</>', '<fg=red>INVALID</>'],
            default => ['<fg=red>⚠️</>', '<fg=red>ERROR</>'],
        };

        $this->line(sprintf('%s Core integrity: %s', $icon, $label));
        if ($result->version !== null) {
            $this->line('  version : '.$result->version);
        }
        if ($result->keyId !== null) {
            $this->line('  key_id  : '.$result->keyId.($result->type ? ' ('.$result->type.')' : ''));
        }
        if ($result->signedAt !== null) {
            $this->line('  signed  : '.$result->signedAt);
        }
        if ($result->message !== null) {
            $this->line('  '.$result->message);
        }

        if ($result->isModified()) {
            $this->newLine();
            $this->line(sprintf('<fg=yellow>%d modified file(s):</>', $result->changedCount()));
            $this->printList('<fg=yellow>M</>', $result->mismatched);
            $this->printList('<fg=red>-</>', $result->missing);
            $this->printList('<fg=green>+</>', $result->extra);
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function printList(string $marker, array $paths): void
    {
        foreach ($paths as $path) {
            $this->line('  '.$marker.' '.$path);
        }
    }

    protected function exitCode(CoreIntegrityResult $result): int
    {
        return in_array($result->status, [CoreIntegrityResult::STATUS_INVALID, CoreIntegrityResult::STATUS_ERROR], true)
            ? self::FAILURE
            : self::SUCCESS;
    }
}
