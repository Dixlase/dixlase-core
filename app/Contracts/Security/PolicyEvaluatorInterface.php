<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes (reserved hook; default implementation always defers to RBAC)
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

namespace App\Contracts\Security;

use App\Enums\PolicyDecision;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Attribute-Based Access Control (ABAC) hook for `PermissionService`.
 *
 * Reserved Phase 1 extension point for the Zero Trust roadmap. The default
 * implementation returns {@see PolicyDecision::Defer} for every input —
 * authorisation decisions fall through to the existing role-based
 * mechanism unchanged. Operators who want richer policies ("editors may
 * publish only during business hours from a trusted IP range", "admins
 * cannot delete users from a country flagged in the risk engine", …)
 * install a plugin that implements actual policy evaluation.
 *
 * Compatibility: the method signature is part of the Plugin API stability
 * pledge (see PLUGIN-API.md). Adding new optional parameters is
 * non-breaking; renaming arguments or changing the return type follows
 * the deprecation policy.
 *
 * Implementers must:
 *  - Be deterministic for the same input (no hidden state).
 *  - Be safe to call repeatedly during a single request.
 *  - Not throw on missing context fields; missing fields are signals.
 *  - Treat the call as read-only: no DB writes, no audit log entries
 *    (the caller — typically `PermissionService` — is responsible for
 *    those).
 *  - Return {@see PolicyDecision::Defer} when no policy applies; this
 *    preserves RBAC semantics by default.
 *
 * @see \App\Services\Security\NullPolicyEvaluator Default implementation
 */
interface PolicyEvaluatorInterface
{
    /**
     * Evaluate whether `$actor` may perform `$action` on `$resource`
     * given `$context`.
     *
     * @param  Authenticatable|null  $actor  The authenticated principal, or null for anonymous / system flows.
     * @param  string  $action  Permission key being checked. Naming format: see docs/development/naming.md "Permission keys".
     * @param  object|null  $resource  Optional resource the action targets (model, DTO, …). Null for class-level checks.
     * @param  array<string,mixed>  $context  Free-form attributes (request IP, time, risk score, …). Never null.
     */
    public function evaluate(
        ?Authenticatable $actor,
        string $action,
        ?object $resource,
        array $context,
    ): PolicyDecision;
}
