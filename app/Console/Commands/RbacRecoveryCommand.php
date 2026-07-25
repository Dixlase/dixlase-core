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

namespace App\Console\Commands;

use App\Enums\MemberRole;
use App\Models\Member;
use Illuminate\Console\Command;

/**
 * RBAC emergency recovery command (break glass)
 *
 * Emergency recovery for when nobody can access the admin panel because no
 * member currently holds an admin-tier role. Roles in Dixlase are an integer
 * enum (MemberRole) stored on the members table; this command can promote a
 * named member to SUPER_ADMIN and report on the current distribution.
 *
 * Per-role permission editing is not supported because permissions are tied
 * to the MemberRole enum and are not individually mutable at runtime.
 */
class RbacRecoveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:rbac-recovery
                            {action=status : Action to perform (grant-super-admin, status, list)}
                            {--member= : Member ID or email to promote to SUPER_ADMIN}
                            {--reason= : Reason for recovery (required)}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emergency RBAC recovery — promote a member to SUPER_ADMIN when admin access is lost (break-glass)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'grant-super-admin' => $this->grantSuperAdmin(),
            'status' => $this->showStatus(),
            'list' => $this->listMembers(),
            default => $this->invalidAction($action),
        };
    }

    /**
     * Promote a member to SUPER_ADMIN.
     */
    protected function grantSuperAdmin(): int
    {
        $member = $this->findMember();
        if (! $member) {
            return self::FAILURE;
        }

        $reason = $this->getRequiredReason();
        if (! $reason) {
            return self::FAILURE;
        }

        $this->showMemberStatus($member);

        $this->warn(__('admin/command/rbac-recovery.warning_grant'));
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm(__('admin/command/rbac-recovery.confirm_grant', ['name' => ($member->display_name ?? $member->account_name)]))) {
            $this->info(__('admin/command/rbac-recovery.cancelled'));

            return self::SUCCESS;
        }

        $previousRole = $member->role;

        $member->update([
            'role' => MemberRole::SUPER_ADMIN,
        ]);

        $this->info(__('admin/command/rbac-recovery.grant_success', ['name' => ($member->display_name ?? $member->account_name)]));

        \App\Facades\Audit::logSecurity('rbac_emergency_grant_super_admin', [
            'severity' => 'critical',
            'outcome' => 'success',
            'context' => [
                'member_id' => $member->id,
                'member_email' => $member->email,
                'previous_role' => $previousRole?->name ?? 'unknown',
                'granted_role' => MemberRole::SUPER_ADMIN->name,
                'reason' => $reason,
                'triggered_by' => 'cli',
            ],
        ]);

        $this->warn(__('admin/command/rbac-recovery.security_notice'));

        return self::SUCCESS;
    }

    /**
     * Show the role distribution across all members.
     */
    protected function showStatus(): int
    {
        $this->info(__('admin/command/rbac-recovery.system_status_title'));
        $this->newLine();

        $totalMembers = Member::count();
        $superAdminCount = Member::where('role', MemberRole::SUPER_ADMIN)->count();
        $adminCount = Member::where('role', MemberRole::ADMIN)->count();

        $this->table(
            [__('admin/command/rbac-recovery.metric'), __('admin/command/rbac-recovery.value')],
            [
                [__('admin/command/rbac-recovery.total_members'), $totalMembers],
                [__('admin/command/rbac-recovery.super_admins'), $superAdminCount],
                [__('admin/command/rbac-recovery.admins'), $adminCount],
            ]
        );

        if ($superAdminCount === 0) {
            $this->newLine();
            $this->error(__('admin/command/rbac-recovery.warning_no_super_admin'));
        }

        return self::SUCCESS;
    }

    /**
     * List members with their current role.
     */
    protected function listMembers(): int
    {
        $this->info(__('admin/command/rbac-recovery.members_title'));
        $this->newLine();

        $members = Member::all();

        if ($members->isEmpty()) {
            $this->warn(__('admin/command/rbac-recovery.no_members'));

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($members as $member) {
            $rows[] = [
                $member->id,
                ($member->display_name ?? $member->account_name),
                $member->email,
                $member->role?->label() ?? '-',
            ];
        }

        $this->table(
            [
                __('admin/command/rbac-recovery.col_id'),
                __('admin/command/rbac-recovery.col_name'),
                __('admin/command/rbac-recovery.col_email'),
                __('admin/command/rbac-recovery.col_roles'),
            ],
            $rows
        );

        return self::SUCCESS;
    }

    /**
     * Find member by ID or email.
     */
    protected function findMember(): ?Member
    {
        $identifier = $this->option('member');

        if (! $identifier) {
            $identifier = $this->ask(__('admin/command/rbac-recovery.member_prompt'));
        }

        if (! $identifier) {
            $this->error(__('admin/command/rbac-recovery.member_required'));

            return null;
        }

        $member = is_numeric($identifier)
            ? Member::find($identifier)
            : Member::where('email', $identifier)->first();

        if (! $member) {
            $this->error(__('admin/command/rbac-recovery.member_not_found', ['identifier' => $identifier]));

            return null;
        }

        return $member;
    }

    /**
     * Get the required justification for the recovery action.
     */
    protected function getRequiredReason(): ?string
    {
        $reason = $this->option('reason');

        if (! $reason) {
            $reason = $this->ask(__('admin/command/rbac-recovery.reason_prompt'));
        }

        if (! $reason) {
            $this->error(__('admin/command/rbac-recovery.reason_required'));

            return null;
        }

        return $reason;
    }

    /**
     * Show the member's current role.
     */
    protected function showMemberStatus(Member $member): void
    {
        $this->info(__('admin/command/rbac-recovery.member_status_title', ['name' => ($member->display_name ?? $member->account_name)]));
        $this->newLine();

        $this->table(
            [__('admin/command/rbac-recovery.field'), __('admin/command/rbac-recovery.value')],
            [
                [__('admin/command/rbac-recovery.member_id'), $member->id],
                [__('admin/command/rbac-recovery.email'), $member->email],
                [__('admin/command/rbac-recovery.current_roles'), $member->role?->label() ?? __('admin/command/rbac-recovery.none')],
            ]
        );
    }

    /**
     * Handle invalid action.
     */
    protected function invalidAction(string $action): int
    {
        $this->error(__('admin/command/rbac-recovery.invalid_action', ['action' => $action]));
        $this->line(__('admin/command/rbac-recovery.valid_actions'));
        $this->line('  - grant-super-admin : '.__('admin/command/rbac-recovery.action_grant'));
        $this->line('  - status            : '.__('admin/command/rbac-recovery.action_status'));
        $this->line('  - list              : '.__('admin/command/rbac-recovery.action_list'));

        return self::FAILURE;
    }
}
