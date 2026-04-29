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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Actors;

use App\Contracts\Action\Actor;
use App\Enums\MemberRole;
use App\Enums\Permission;
use Illuminate\Database\Eloquent\Model;

/**
 * Actor for system-initiated operations
 *
 * Used when the CMS itself performs operations (CLI commands, queue jobs,
 * cron tasks, seeders). Has full permissions by default since system
 * operations are trusted internal processes.
 */
class SystemActor implements Actor
{
    public function __construct(
        protected readonly string $processName = 'system',
    ) {}

    public function getActorId(): ?int
    {
        return null;
    }

    public function getActorType(): string
    {
        return 'system';
    }

    public function getActorName(): string
    {
        return $this->processName;
    }

    public function toAuditMorph(): ?Model
    {
        return null;
    }

    public function hasPermission(Permission $permission): bool
    {
        return true;
    }

    public function hasRole(MemberRole $role): bool
    {
        return true;
    }
}
