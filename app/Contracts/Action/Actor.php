<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

declare(strict_types=1);

namespace App\Contracts\Action;

use App\Enums\MemberRole;
use App\Enums\Permission;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents the entity performing an operation
 *
 * All CMS operations flow through Actions, and every Action receives
 * an Actor to identify who (or what) initiated the operation.
 * This enables unified permission checks and audit logging
 * regardless of whether the caller is a human user, system process,
 * API client, or (in the future) an AI agent.
 */
interface Actor
{
    /**
     * Get the unique identifier for this actor
     *
     * Returns null for system-level actors that have no persistent identity.
     */
    public function getActorId(): ?int;

    /**
     * Get the actor type slug
     *
     * Used for audit logging and permission scoping.
     * Expected values: 'member', 'system', 'api'
     */
    public function getActorType(): string;

    /**
     * Get a human-readable name for this actor
     *
     * Used in audit logs and UI display.
     */
    public function getActorName(): string;

    /**
     * Get the Eloquent model for audit log morphTo relation
     *
     * Returns null for actors without a backing model (e.g. SystemActor).
     */
    public function toAuditMorph(): ?Model;

    /**
     * Check if this actor has a specific permission
     */
    public function hasPermission(Permission $permission): bool;

    /**
     * Check if this actor has at least the given role level
     */
    public function hasRole(MemberRole $role): bool;
}
