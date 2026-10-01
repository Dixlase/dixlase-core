<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core only. Do not reference from plugins/themes
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

namespace App\Services\Core;

use App\Models\FileIntegrityAudit;
use App\Services\FileIntegrityService;
use Closure;

/**
 * Keeps the core file-integrity baseline in step with core updates and
 * rollbacks.
 *
 * The baseline (core_hashes.json) was written at install and never again,
 * so the first legitimate update made the daily dls:integrity:scan report
 * every file the release changed as tampering — and an operator taught to
 * ignore that warning is the wrong outcome for a tamper-detection feature.
 *
 * Regenerating unconditionally would turn the feature around ("whatever is
 * on disk is now genuine"), so the rule is: the tree must match the
 * baseline immediately BEFORE the update or rollback starts, and the new
 * baseline is written only after it finished successfully. A tree that was
 * already modified keeps its old baseline, and the scan keeps reporting it.
 */
class CoreIntegrityBaselineRefresher
{
    public function __construct(private FileIntegrityService $integrity) {}

    /**
     * Whether the core tree matches its baseline right now.
     *
     * Null when there is no baseline yet: nothing to preserve, so the
     * update may write one.
     */
    public function treeMatchesBaseline(): ?bool
    {
        $baseline = $this->integrity->loadBaselineArray();
        if ($baseline === null || ! isset($baseline['files'])) {
            return null;
        }

        $current = $this->integrity->generateCoreBaseline()['files'];

        // A generated file (the plugin Tailwind sources) is rewritten on
        // every plugin change; older baselines still list it. Comparing it
        // kept the refresh from ever running on a site with a plugin.
        return $this->integrity->withoutGeneratedFiles($current)
            == $this->integrity->withoutGeneratedFiles($baseline['files']);
    }

    /**
     * Write a new baseline after a successful update or rollback.
     *
     * Never throws: a baseline problem must not fail a finished update.
     *
     * @param  ?bool  $matchedBefore  treeMatchesBaseline() taken before the operation started
     * @param  Closure(string): void  $log
     */
    public function refreshAfter(?bool $matchedBefore, ?int $appliedById, Closure $log): void
    {
        if ($matchedBefore === false) {
            $log('WARNING: core files did not match the integrity baseline before this operation, so the baseline was left unchanged. Review Security → File integrity, then regenerate it there once the tree is confirmed.');

            return;
        }

        try {
            $baseline = $this->integrity->generateCoreBaseline();
            if (! $this->integrity->saveBaselineArray($baseline)) {
                $log('WARNING: could not write the new integrity baseline; the next integrity scan will report the updated files.');

                return;
            }

            FileIntegrityAudit::create([
                'scope' => FileIntegrityAudit::SCOPE_CORE,
                'trigger' => FileIntegrityAudit::TRIGGER_UPDATE,
                'initiated_by_type' => $appliedById !== null ? FileIntegrityAudit::INITIATED_BY_USER : FileIntegrityAudit::INITIATED_BY_CLI,
                'initiated_by_id' => $appliedById,
                'status' => FileIntegrityAudit::STATUS_OK,
                'hash_algo' => 'sha256',
                'baseline_version' => $baseline['meta']['app_version'] ?? null,
                'total_files_scanned' => count($baseline['files']),
                'started_at' => now(),
                'finished_at' => now(),
                'duration_ms' => 0,
                'summary' => __('admin/command/integrity.baseline_regenerated'),
            ]);

            $log('Integrity baseline regenerated ('.count($baseline['files']).' files).');
        } catch (\Throwable $e) {
            $log('WARNING: integrity baseline regeneration failed: '.$e->getMessage());
        }
    }
}
