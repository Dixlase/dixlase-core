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

use App\Models\AuditLog;
use App\Models\Member;
use App\Services\AuditService;

/**
 * Audit-log entries for core updates and rollbacks.
 *
 * Replacing the source tree, vendor/ and the schema is the largest
 * administrative action Dixlase has, yet neither dls:core:update nor
 * dls:core:rollback wrote to the audit log: CoreVersionHistory records the
 * transition, but it is not the hash-chained, sealed log that
 * audit:integrity verifies, so "who updated the core, and when" had no
 * answer there. Both the success and the failure of each operation are
 * recorded.
 *
 * Never throws: a failed audit write must not turn a finished update into
 * a failed one, or mask the error that failed it.
 */
class CoreUpdateAudit
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function record(string $action, bool $succeeded, string $from, string $to, ?int $appliedById, array $context = []): void
    {
        try {
            $actor = $appliedById !== null ? Member::find($appliedById) : null;

            app(AuditService::class)->log([
                'action' => $action,
                'category' => AuditLog::CATEGORY_SYSTEM,
                'severity' => $succeeded ? AuditLog::SEVERITY_NOTICE : AuditLog::SEVERITY_ERROR,
                'outcome' => $succeeded ? AuditLog::OUTCOME_SUCCESS : AuditLog::OUTCOME_FAILURE,
                'actor' => $actor,
                'target_label' => "Dixlase core v{$from} → v{$to}",
                'context' => ['from' => $from, 'to' => $to, 'applied_by_id' => $appliedById] + $context,
            ]);
        } catch (\Throwable) {
            // AuditService already swallows its own failures; this guards
            // the lookup above. Reporting must never fail the operation.
        }
    }
}
