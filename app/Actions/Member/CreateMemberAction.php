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

namespace App\Actions\Member;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Models\Member;
use App\Services\MailServerValidatorService;
use App\Services\PasswordService;
use Illuminate\Support\Facades\Log;

/**
 * Create a new member account
 *
 * Handles password hashing, email verification status,
 * and optional verification email sending.
 */
class CreateMemberAction extends AbstractAction
{
    protected function requiredPermission(): ?Permission
    {
        return Permission::MEMBERS_CREATE;
    }

    protected function auditAction(): string
    {
        return 'member.created';
    }

    protected function auditCategory(): string
    {
        return 'account';
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $data['password'] = PasswordService::hash($data['password']);

        $isMailServerTested = MailServerValidatorService::isMailServerTested();
        $emailVerified = (string) ($data['email_verified'] ?? ($isMailServerTested ? '0' : '1'));

        if (! $isMailServerTested) {
            $data['email_verified_at'] = now();
        } elseif ($emailVerified === '1') {
            $data['email_verified_at'] = now();
        } else {
            $data['email_verified_at'] = null;
        }

        unset($data['email_verified']);

        $member = Member::create($data);

        $emailSent = false;
        $emailFailed = false;

        if ($isMailServerTested && $emailVerified === '0') {
            try {
                $member->sendEmailVerificationNotification('create');
                $emailSent = true;
            } catch (\Exception $e) {
                Log::error('Failed to send verification email', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
                $emailFailed = true;
            }
        }

        return ActionResult::success(
            model: $member,
            label: $member->display_name ?? $member->account_name,
            metadata: [
                'email_sent' => $emailSent,
                'email_failed' => $emailFailed,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'account_name' => $data['account_name'] ?? null,
            'email' => $data['email'] ?? null,
            'role' => $data['role'] ?? null,
        ];
    }
}
