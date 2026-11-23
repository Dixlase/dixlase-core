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

declare(strict_types=1);

namespace App\Services\Install;

use App\Contracts\Install\FreshInstallBootstrapperInterface;
use App\Models\AuditLog;
use App\Models\AuditLogDailySeal;
use App\Models\AuditLogKeyEpoch;
use App\Models\AuditLogRetentionPoint;
use App\Models\FileIntegrityAudit;
use App\Services\FileIntegrityService;
use Illuminate\Support\Facades\DB;

/**
 * Default implementation of {@see FreshInstallBootstrapperInterface}.
 *
 * Kept deliberately small: it holds core's own knowledge of what a clean
 * audit trail and a recorded core baseline look like, so a provisioner
 * does not have to. It works entirely on the default connection, which the
 * caller is responsible for pointing at the target install.
 */
class FreshInstallBootstrapper implements FreshInstallBootstrapperInterface
{
    public function __construct(
        private readonly FileIntegrityService $fileIntegrity,
    ) {}

    public function resetAuditTrail(): void
    {
        DB::transaction(function (): void {
            // Empty every table that holds audit-trail data. These are
            // mass deletes rather than truncates so they stay inside the
            // transaction (TRUNCATE commits implicitly on MySQL) and so a
            // table protected by a foreign key is cleared in order.
            AuditLogRetentionPoint::query()->delete();
            AuditLogDailySeal::query()->delete();
            AuditLogKeyEpoch::query()->delete();
            AuditLog::query()->delete();

            // Reset the chain lock to its brand-new-install value (a single
            // row, generation 0) rather than deleting it, so the next audit
            // entry starts a fresh chain exactly as it would after a clean
            // install. insertOrIgnore + update covers both "row present"
            // (copied) and "row somehow missing" cases.
            DB::table('audit_log_chain_lock')->insertOrIgnore(['id' => 1, 'generation' => 0]);
            DB::table('audit_log_chain_lock')->where('id', 1)->update(['generation' => 0]);
        });
    }

    public function recordCoreBaselineAudit(): void
    {
        $baseline = $this->fileIntegrity->loadBaselineArray();

        // Nothing to reflect when the copy carries no baseline file. The
        // method records an existing baseline; it does not generate one.
        if ($baseline === null) {
            return;
        }

        FileIntegrityAudit::create([
            'scope' => FileIntegrityAudit::SCOPE_CORE,
            'trigger' => FileIntegrityAudit::TRIGGER_INSTALL,
            'initiated_by_type' => FileIntegrityAudit::INITIATED_BY_SYSTEM,
            'status' => FileIntegrityAudit::STATUS_OK,
            'hash_algo' => $baseline['meta']['hash_algo'] ?? 'sha256',
            'baseline_version' => $baseline['meta']['app_version'] ?? null,
            'total_files_scanned' => count($baseline['files'] ?? []),
            'started_at' => now(),
            'finished_at' => now(),
            'duration_ms' => 0,
            'summary' => __('admin/command/integrity.baseline_generated'),
        ]);
    }
}
