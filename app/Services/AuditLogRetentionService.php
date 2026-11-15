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

namespace App\Services;

use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\AuditLogDailySeal;
use App\Models\AuditLogRetentionPoint;
use App\Services\Security\IntegrityKeyring;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * The one path that deletes audit log rows (#1048).
 *
 * Every caller — the audit log screen, the database cleanup screen and
 * `dls:cleanup` — goes through prune(), which:
 *
 * 1. records the run in the audit log before anything is deleted, so the
 *    deletion is part of the chain it shortens, and refuses to go on when
 *    that row cannot be written;
 * 2. deletes whole days only: the cutoff is moved back to the start of a
 *    day, and rows are cut off the start of the chain by id, so the chain
 *    left behind has no holes;
 * 3. removes the daily seals that covered deleted rows, so seal
 *    verification does not report them;
 * 4. records a signed retention point for the first chained row left,
 *    which full-chain verification accepts in place of the genesis row.
 *
 * Steps 3 and 4 also run when nothing is deleted, which is how a site that
 * pruned rows before this existed gets its verification back: the current
 * first row is adopted, and that is in the audit log as well.
 */
class AuditLogRetentionService
{
    public function __construct(
        private readonly IntegrityKeyring $keyring,
        private readonly AuditLogIntegrityService $integrity,
    ) {}

    /**
     * Delete audit log rows older than the given number of days.
     *
     * @param  string  $via  Where the run came from, for the audit row (admin_logs, admin_database, cli)
     * @return array{deleted:int, seals_removed:int, cutoff:Carbon, first_log_id:int|null, point_recorded:bool}
     *
     * @throws \InvalidArgumentException when fewer than one day would be kept
     * @throws \RuntimeException when the run could not be recorded
     */
    public function prune(int $days, ?Model $actor = null, string $via = 'cli'): array
    {
        if ($days < 1) {
            throw new \InvalidArgumentException('Audit log retention keeps at least one day.');
        }

        $cutoff = now()->subDays($days)->startOfDay();
        $firstKeptId = AuditLog::where('occurred_at', '>=', $cutoff)->min('id');
        $planned = $firstKeptId === null
            ? AuditLog::count()
            : AuditLog::where('id', '<', $firstKeptId)->count();

        $recorded = Audit::log([
            'category' => 'system',
            'action' => AuditLog::ACTION_AUDIT_LOG_CLEANUP,
            'actor' => $actor,
            'severity' => 'high',
            'context' => [
                'older_than_days' => $days,
                'cutoff' => $cutoff->toIso8601String(),
                'deleted' => $planned,
                'via' => $via,
            ],
        ]);

        if ($recorded === null) {
            throw new \RuntimeException('The audit log retention run could not be recorded in the audit log, so nothing was deleted.');
        }

        $result = AuditLog::underChainLock(function () use ($cutoff): array {
            // The row just written is newer than the cutoff, so a first
            // kept row always exists.
            $firstKeptId = (int) AuditLog::where('occurred_at', '>=', $cutoff)->min('id');
            $deleted = AuditLog::where('id', '<', $firstKeptId)->delete();

            $first = AuditLog::withHashChain()->orderBy('id')->first();
            $sealsRemoved = 0;
            $pointRecorded = false;

            if ($first !== null) {
                $sealsRemoved = AuditLogDailySeal::where('first_log_id', '<', $first->id)->delete();

                if ($first->previous_hash !== AuditLog::GENESIS_HASH
                    && AuditLogRetentionPoint::available()
                    && ! AuditLogRetentionPoint::latestValid($this->keyring)?->anchors($first)) {
                    AuditLogRetentionPoint::record($first, $cutoff, $deleted, $this->keyring);
                    $pointRecorded = true;
                }
            }

            return [
                'deleted' => $deleted,
                'seals_removed' => $sealsRemoved,
                'cutoff' => $cutoff,
                'first_log_id' => $first?->id,
                'point_recorded' => $pointRecorded,
            ];
        });

        $this->integrity->forgetHealthCache();

        return $result;
    }
}
