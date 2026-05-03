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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

namespace App\Actions\Member;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Facades\Audit;
use App\Models\Member;
use App\Services\PasswordService;

/**
 * Update an existing member account
 *
 * Handles conditional password hashing (only when provided)
 * and records before/after diffs for audit.
 */
class UpdateMemberAction extends AbstractAction
{
    public function __construct(
        protected readonly Member $member,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::MEMBERS_UPDATE;
    }

    protected function auditAction(): string
    {
        return 'member.updated';
    }

    protected function auditCategory(): string
    {
        return 'account';
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $before = $this->member->toArray();

        if (! empty($data['password'])) {
            $data['password'] = PasswordService::hash($data['password']);
        } else {
            unset($data['password']);
        }

        $this->member->update($data);

        return ActionResult::success(
            model: $this->member,
            label: $this->member->display_name ?? $this->member->account_name,
            metadata: ['before' => $before],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        $before = $result->metadata['before'] ?? [];
        $after = $this->member->fresh()?->toArray() ?? [];

        return [
            'diff' => Audit::diff($before, $after),
        ];
    }
}
