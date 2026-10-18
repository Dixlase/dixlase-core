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

namespace App\Services\Member;

use App\Facades\Audit;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Write the audit row for an operation that changes how a member signs in.
 *
 * Revoking a passkey or the recovery codes, registering a passkey, ending
 * sessions and releasing a lockout are reached both from the member's own
 * profile and from another member's security page. Recording them here keeps
 * the action name the same on both paths; the actor is whoever is signed in
 * to the admin panel, and the row says whether that was someone else.
 */
final class MemberSecurityAudit
{
    /**
     * @param  Model|null  $subject  The member whose credentials or sessions changed; null for an operation on many members
     * @param  array<string, mixed>  $context
     */
    public static function record(string $action, ?Model $subject, array $context = []): void
    {
        $actor = Auth::guard('member')->user();
        $byOther = $actor instanceof Model && ! $actor->is($subject);

        Audit::log([
            'category' => AuditLog::getCategoryForAction($action),
            'action' => $action,
            // Another member taking a credential away is the case an operator
            // looks for, so it is raised above the action's usual severity.
            'severity' => $byOther ? AuditLog::SEVERITY_WARNING : AuditLog::getDefaultSeverity($action),
            'actor' => $actor,
            'target' => $subject,
            'context' => $context + ['by_other_member' => $byOther],
        ]);
    }
}
