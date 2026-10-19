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

use App\Services\Custom\CustomOverrideInspector;
use Illuminate\Console\Command;
use Throwable;

/**
 * Report how the view overrides under `custom/` stand against upstream
 * (dixlase-core#482).
 *
 * An override replaces its upstream view wholesale, so whatever upstream adds
 * to that view later never reaches the site, silently. This command lists
 * every override and whether upstream has changed since its baseline was
 * recorded. It exits non-zero when an upstream file changed or disappeared,
 * so it can run as a deploy or post-update check.
 *
 * `--record` stores the current upstream of every override as its baseline
 * in `custom/.overrides.json`. Run it after placing or reviewing an override.
 */
class CustomDiff extends Command
{
    protected $signature = 'dls:custom:diff
                            {--record : Record the current upstream of every override as its baseline}
                            {--json : Print the report as JSON}';

    protected $description = 'Compare the view overrides under custom/ with the upstream files they replace';

    /**
     * Status labels for the table, in report order.
     */
    private const LABELS = [
        CustomOverrideInspector::UPSTREAM_CHANGED => 'upstream changed',
        CustomOverrideInspector::UPSTREAM_MISSING => 'upstream missing',
        CustomOverrideInspector::UNTRACKED => 'no baseline',
        CustomOverrideInspector::CURRENT => 'current',
        CustomOverrideInspector::NOT_COMPARED => 'not compared',
    ];

    public function handle(CustomOverrideInspector $inspector): int
    {
        try {
            if ($this->option('record')) {
                $count = $inspector->record();
                $this->info(sprintf('Recorded the upstream baseline of %d override(s) in %s.', $count, $this->manifestPath($inspector)));

                return self::SUCCESS;
            }

            $results = $inspector->inspect();
        } catch (Throwable $e) {
            $this->error('Override comparison failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $failed = array_filter($results, fn (array $r): bool => in_array($r['status'], [
            CustomOverrideInspector::UPSTREAM_CHANGED,
            CustomOverrideInspector::UPSTREAM_MISSING,
        ], true));

        if ($this->option('json')) {
            $this->line((string) json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $failed === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($results === []) {
            $this->info('No overrides under '.$this->relativeToBase($inspector->customRoot()).'/.');

            return self::SUCCESS;
        }

        $order = array_flip(array_keys(self::LABELS));
        usort($results, fn (array $a, array $b): int => [$order[$a['status']], $a['path']] <=> [$order[$b['status']], $b['path']]);

        $this->table(['Status', 'Override', 'Upstream'], array_map(fn (array $r): array => [
            self::LABELS[$r['status']].($r['identical'] ? ' (identical)' : ''),
            $r['path'],
            $r['upstream'] ?? '-',
        ], $results));

        $counts = array_count_values(array_column($results, 'status'));
        $this->line(implode(', ', array_map(
            fn (string $status): string => sprintf('%d %s', $counts[$status], self::LABELS[$status]),
            array_keys(array_intersect_key(self::LABELS, $counts)),
        )).'.');

        $root = $this->relativeToBase($inspector->customRoot());
        foreach ($failed as $r) {
            $this->newLine();
            if ($r['status'] === CustomOverrideInspector::UPSTREAM_MISSING) {
                $this->warn("{$r['path']}: {$r['upstream']} no longer exists, so this override is no longer used.");

                continue;
            }
            $this->warn("{$r['path']}: upstream changed since the baseline. Compare and carry the change over:");
            $this->line("  diff -u {$r['upstream']} {$root}/{$r['path']}");
        }

        if (isset($counts[CustomOverrideInspector::UNTRACKED])) {
            $this->newLine();
            $this->line('Overrides without a baseline cannot tell when upstream changes. Once they are reviewed, run: php artisan dls:custom:diff --record');
        } elseif ($failed !== []) {
            $this->newLine();
            $this->line('After updating the overrides, record the new baseline: php artisan dls:custom:diff --record');
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    private function manifestPath(CustomOverrideInspector $inspector): string
    {
        return $this->relativeToBase($inspector->customRoot()).'/'.CustomOverrideInspector::MANIFEST;
    }

    private function relativeToBase(string $absolute): string
    {
        return ltrim(substr($absolute, strlen(base_path())), '/');
    }
}
