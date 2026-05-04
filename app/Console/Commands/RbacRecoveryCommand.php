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

namespace App\Console\Commands;

use App\Enums\Permission;
use App\Models\Member;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Console\Command;

/**
 * RBAC権限緊急復旧コマンド（ブレークグラス）
 *
 * Emergency recovery function for when the admin panel becomes inaccessible due to permission settings misconfiguration
 * - Admin permission was removed from all roles
 * - Even SUPER_ADMIN cannot access the admin panel
 */
class RbacRecoveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:rbac-recovery
                            {action=status : Action to perform (grant-super-admin, reset-role, status, list)}
                            {--member= : Member ID or email to grant super admin}
                            {--role= : Role ID or name to reset}
                            {--reason= : Reason for recovery (required)}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emergency RBAC permission recovery (break-glass)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'grant-super-admin' => $this->grantSuperAdmin(),
            'reset-role' => $this->resetRole(),
            'status' => $this->showStatus(),
            'list' => $this->listMembersAndRoles(),
            default => $this->invalidAction($action),
        };
    }

    /**
     * Grant super admin permission to a member
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

        // Show current status
        $this->showMemberPermissionStatus($member);

        // Confirm action
        $this->warn(__('admin/command.rbac_recovery.warning_grant'));
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm(__('admin/command.rbac_recovery.confirm_grant', ['name' => ($member->display_name ?? $member->account_name)]))) {
            $this->info(__('admin/command.rbac_recovery.cancelled'));

            return self::SUCCESS;
        }

        // Find or create super admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();

        if (! $superAdminRole) {
            // Create super admin role with all permissions
            $superAdminRole = Role::create([
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Full system access',
                'permissions' => array_column(Permission::cases(), 'value'),
                'is_system' => true,
            ]);
            $this->info(__('admin/command.rbac_recovery.role_created'));
        }

        // Assign role to member
        $previousRoles = $member->roles->pluck('name')->toArray();

        if (! $member->roles->contains($superAdminRole->id)) {
            $member->roles()->attach($superAdminRole->id);
        }

        $this->info(__('admin/command.rbac_recovery.grant_success', ['name' => ($member->display_name ?? $member->account_name)]));

        // Log to audit
        AuditService::log(
            action: 'rbac_emergency_grant_super_admin',
            category: 'security',
            severity: 'critical',
            outcome: 'success',
            actorId: null,
            context: [
                'member_id' => $member->id,
                'member_email' => $member->email,
                'previous_roles' => $previousRoles,
                'granted_role' => 'super_admin',
                'reason' => $reason,
                'triggered_by' => 'cli',
            ]
        );

        $this->warn(__('admin/command.rbac_recovery.security_notice'));

        return self::SUCCESS;
    }

    /**
     * Reset a role to default permissions
     */
    protected function resetRole(): int
    {
        $role = $this->findRole();
        if (! $role) {
            return self::FAILURE;
        }

        $reason = $this->getRequiredReason();
        if (! $reason) {
            return self::FAILURE;
        }

        // Show current status
        $this->info(__('admin/command.rbac_recovery.role_status_title', ['name' => $role->display_name]));
        $this->line(__('admin/command.rbac_recovery.current_permissions', ['count' => count($role->permissions ?? [])]));
        $this->newLine();

        // Confirm action
        $this->warn(__('admin/command.rbac_recovery.warning_reset'));
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm(__('admin/command.rbac_recovery.confirm_reset', ['name' => $role->display_name]))) {
            $this->info(__('admin/command.rbac_recovery.cancelled'));

            return self::SUCCESS;
        }

        $previousPermissions = $role->permissions ?? [];

        // Reset to default based on role name
        $defaultPermissions = $this->getDefaultPermissionsForRole($role->name);

        $role->update([
            'permissions' => $defaultPermissions,
        ]);

        $this->info(__('admin/command.rbac_recovery.reset_success', [
            'name' => $role->display_name,
            'count' => count($defaultPermissions),
        ]));

        // Log to audit
        AuditService::log(
            action: 'rbac_emergency_reset_role',
            category: 'security',
            severity: 'critical',
            outcome: 'success',
            actorId: null,
            context: [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'previous_permissions_count' => count($previousPermissions),
                'new_permissions_count' => count($defaultPermissions),
                'reason' => $reason,
                'triggered_by' => 'cli',
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Show RBAC status
     */
    protected function showStatus(): int
    {
        $this->info(__('admin/command.rbac_recovery.system_status_title'));
        $this->newLine();

        // Count members with super admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdminCount = $superAdminRole ? $superAdminRole->members()->count() : 0;

        // Count total roles and members
        $totalRoles = Role::count();
        $totalMembers = Member::count();
        $membersWithRoles = Member::has('roles')->count();

        $this->table(
            [__('admin/command.rbac_recovery.metric'), __('admin/command.rbac_recovery.value')],
            [
                [__('admin/command.rbac_recovery.total_roles'), $totalRoles],
                [__('admin/command.rbac_recovery.total_members'), $totalMembers],
                [__('admin/command.rbac_recovery.members_with_roles'), $membersWithRoles],
                [__('admin/command.rbac_recovery.super_admins'), $superAdminCount],
            ]
        );

        // Warning if no super admins
        if ($superAdminCount === 0) {
            $this->newLine();
            $this->error(__('admin/command.rbac_recovery.warning_no_super_admin'));
        }

        return self::SUCCESS;
    }

    /**
     * List members and roles
     */
    protected function listMembersAndRoles(): int
    {
        // List roles
        $this->info(__('admin/command.rbac_recovery.roles_title'));
        $this->newLine();

        $roles = Role::withCount('members')->get();

        if ($roles->isEmpty()) {
            $this->warn(__('admin/command.rbac_recovery.no_roles'));
        } else {
            $rows = [];
            foreach ($roles as $role) {
                $rows[] = [
                    $role->id,
                    $role->name,
                    $role->display_name,
                    count($role->permissions ?? []),
                    $role->members_count,
                ];
            }

            $this->table(
                [
                    __('admin/command.rbac_recovery.col_id'),
                    __('admin/command.rbac_recovery.col_name'),
                    __('admin/command.rbac_recovery.col_display_name'),
                    __('admin/command.rbac_recovery.col_permissions'),
                    __('admin/command.rbac_recovery.col_members'),
                ],
                $rows
            );
        }

        // List members with their roles
        $this->newLine();
        $this->info(__('admin/command.rbac_recovery.members_title'));
        $this->newLine();

        $members = Member::with('roles')->get();

        if ($members->isEmpty()) {
            $this->warn(__('admin/command.rbac_recovery.no_members'));
        } else {
            $rows = [];
            foreach ($members as $member) {
                $roleNames = $member->roles->pluck('display_name')->join(', ') ?: '-';
                $rows[] = [
                    $member->id,
                    ($member->display_name ?? $member->account_name),
                    $member->email,
                    $roleNames,
                ];
            }

            $this->table(
                [
                    __('admin/command.rbac_recovery.col_id'),
                    __('admin/command.rbac_recovery.col_name'),
                    __('admin/command.rbac_recovery.col_email'),
                    __('admin/command.rbac_recovery.col_roles'),
                ],
                $rows
            );
        }

        return self::SUCCESS;
    }

    /**
     * Find member by ID or email
     */
    protected function findMember(): ?Member
    {
        $identifier = $this->option('member');

        if (! $identifier) {
            $identifier = $this->ask(__('admin/command.rbac_recovery.member_prompt'));
        }

        if (! $identifier) {
            $this->error(__('admin/command.rbac_recovery.member_required'));

            return null;
        }

        $member = is_numeric($identifier)
            ? Member::find($identifier)
            : Member::where('email', $identifier)->first();

        if (! $member) {
            $this->error(__('admin/command.rbac_recovery.member_not_found', ['identifier' => $identifier]));

            return null;
        }

        return $member;
    }

    /**
     * Find role by ID or name
     */
    protected function findRole(): ?Role
    {
        $identifier = $this->option('role');

        if (! $identifier) {
            $identifier = $this->ask(__('admin/command.rbac_recovery.role_prompt'));
        }

        if (! $identifier) {
            $this->error(__('admin/command.rbac_recovery.role_required'));

            return null;
        }

        $role = is_numeric($identifier)
            ? Role::find($identifier)
            : Role::where('name', $identifier)->first();

        if (! $role) {
            $this->error(__('admin/command.rbac_recovery.role_not_found', ['identifier' => $identifier]));

            return null;
        }

        return $role;
    }

    /**
     * Get required reason
     */
    protected function getRequiredReason(): ?string
    {
        $reason = $this->option('reason');

        if (! $reason) {
            $reason = $this->ask(__('admin/command.rbac_recovery.reason_prompt'));
        }

        if (! $reason) {
            $this->error(__('admin/command.rbac_recovery.reason_required'));

            return null;
        }

        return $reason;
    }

    /**
     * Show member's permission status
     */
    protected function showMemberPermissionStatus(Member $member): void
    {
        $this->info(__('admin/command.rbac_recovery.member_status_title', ['name' => ($member->display_name ?? $member->account_name)]));
        $this->newLine();

        $roles = $member->roles->pluck('display_name')->join(', ') ?: __('admin/command.rbac_recovery.none');

        $this->table(
            [__('admin/command.rbac_recovery.field'), __('admin/command.rbac_recovery.value')],
            [
                [__('admin/command.rbac_recovery.member_id'), $member->id],
                [__('admin/command.rbac_recovery.email'), $member->email],
                [__('admin/command.rbac_recovery.current_roles'), $roles],
            ]
        );
    }

    /**
     * Get default permissions for a role
     */
    protected function getDefaultPermissionsForRole(string $roleName): array
    {
        return match ($roleName) {
            'super_admin' => array_column(Permission::cases(), 'value'),
            'admin' => [
                Permission::DASHBOARD_VIEW->value,
                Permission::MEMBERS_VIEW->value,
                Permission::MEMBERS_CREATE->value,
                Permission::MEMBERS_EDIT->value,
                Permission::SETTINGS_VIEW->value,
                Permission::SETTINGS_EDIT->value,
            ],
            'editor' => [
                Permission::DASHBOARD_VIEW->value,
            ],
            default => [Permission::DASHBOARD_VIEW->value],
        };
    }

    /**
     * Handle invalid action
     */
    protected function invalidAction(string $action): int
    {
        $this->error(__('admin/command.rbac_recovery.invalid_action', ['action' => $action]));
        $this->line(__('admin/command.rbac_recovery.valid_actions'));
        $this->line('  - grant-super-admin : '.__('admin/command.rbac_recovery.action_grant'));
        $this->line('  - reset-role        : '.__('admin/command.rbac_recovery.action_reset'));
        $this->line('  - status            : '.__('admin/command.rbac_recovery.action_status'));
        $this->line('  - list              : '.__('admin/command.rbac_recovery.action_list'));

        return self::FAILURE;
    }
}
