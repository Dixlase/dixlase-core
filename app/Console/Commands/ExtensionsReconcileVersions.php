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

namespace App\Console\Commands;

namespace App\Console\Commands;

use App\Models\Plugin;
use App\Models\Theme;
use App\Services\Extension\ExtensionVersionReconciler;
use Illuminate\Console\Command;
use Throwable;

/**
 * Reconcile the recorded plugin and theme versions with the manifests on disk.
 *
 * The same reconciliation the rescan performs (ExtensionVersionReconciler),
 * without the rest of the audit, so a deploy can run it once it has placed
 * files and the admin panel is correct immediately rather than after the next
 * scheduled rescan. It covers every *installed* extension, where
 * dls:extensions:rescan covers only the enabled ones.
 *
 * `--dry-run` reports the drift and exits non-zero when it finds any, so it
 * can be used as a check rather than a repair.
 */
class ExtensionsReconcileVersions extends Command
{
    protected $signature = 'dls:extensions:reconcile-versions
                            {--dry-run : Report the drift without writing, and exit non-zero when any is found}';

    protected $description = 'Reconcile the recorded plugin and theme versions with the manifests on disk';

    public function handle(ExtensionVersionReconciler $reconciler): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $changes = $reconciler->reconcileAll($dryRun);
        } catch (Throwable $e) {
            $this->error('Version reconciliation failed: '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($changes as $change) {
            $this->line(sprintf(
                '  %s %s: recorded %s, on disk %s%s',
                $change['kind'],
                $change['slug'],
                $change['from'] ?? '(none)',
                $change['to'],
                $dryRun ? '' : ' — updated',
            ));
        }

        if ($changes === []) {
            $this->info($this->nothingInstalled()
                ? 'No installed extensions.'
                : 'Every installed extension already matches its manifest.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d extension(s) %s.',
            count($changes),
            $dryRun ? 'differ from their manifest' : 'reconciled',
        ));

        return $dryRun ? self::FAILURE : self::SUCCESS;
    }

    /**
     * True when there are no installed extension rows at all, so there was
     * nothing to compare.
     *
     * Worth its own message: "every installed extension already matches its
     * manifest" reads in a deploy log as a check that passed, when on a site
     * with no rows nothing was checked. A site that was deployed but never
     * installed is exactly that case.
     *
     * Not wrapped: reconcileAll() has already read both tables by the time
     * this runs, so a failure here is a real fault and should be loud rather
     * than resolved into one message or the other.
     */
    protected function nothingInstalled(): bool
    {
        return Plugin::query()->installed()->count() === 0
            && Theme::query()->installed()->count() === 0;
    }
}
