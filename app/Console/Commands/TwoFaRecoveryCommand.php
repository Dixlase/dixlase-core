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

use App\Enums\AuthenticationMode;
use App\Models\Member;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Two-factor authentication emergency recovery command (break glass)
 *
 * Emergency recovery feature to rescue administrators who are completely locked out by two-factor authentication
 * - Cannot authenticate via email (mail server failure, etc.)
 * - Cannot authenticate via device (device loss, etc.)
 * - Used up all recovery codes
 */
class TwoFaRecoveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:two-fa-recovery
                            {action=status : Action to perform (disable, reset-codes, status, list)}
                            {--member= : Member ID or email to recover}
                            {--reason= : Reason for recovery (required)}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emergency two-factor authentication recovery (break-glass)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'disable' => $this->disableTwoFa(),
            'reset-codes' => $this->resetRecoveryCodes(),
            'status' => $this->showStatus(),
            'list' => $this->listMembers(),
            default => $this->invalidAction($action),
        };
    }

    /**
     * Disable two-factor authentication for a member
     */
    protected function disableTwoFa(): int
    {
        $member = $this->findMember();
        if (! $member) {
            return self::FAILURE;
        }

        $reason = $this->getRequiredReason();
        if (! $reason) {
            return self::FAILURE;
        }

        // Show current 2FA status
        $this->showMemberTwoFaStatus($member);

        // Confirm action
        $this->warn(__('admin/command.two_fa_recovery.warning_disable'));
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm(__('admin/command.two_fa_recovery.confirm_disable', ['name' => ($member->display_name ?? $member->account_name)]))) {
            $this->info(__('admin/command.two_fa_recovery.cancelled'));

            return self::SUCCESS;
        }

        // Store previous state for audit
        $previousMode = $member->two_fa_mode;

        // Disable 2FA
        $member->update([
            'two_fa_mode' => AuthenticationMode::Disabled->value,
            'two_fa_recovery_codes' => null,
        ]);

        // Clear any pending 2FA tokens
        $member->twoFactorTokens()->delete();

        $this->info(__('admin/command.two_fa_recovery.disabled_success', ['name' => ($member->display_name ?? $member->account_name)]));

        // Log to audit
        AuditService::log(
            action: 'two_factor_emergency_disabled',
            category: 'security',
            severity: 'critical',
            outcome: 'success',
            actorId: null, // CLI operation
            context: [
                'member_id' => $member->id,
                'member_email' => $member->email,
                'previous_mode' => $previousMode?->value ?? 'unknown',
                'reason' => $reason,
                'triggered_by' => 'cli',
            ]
        );

        $this->warn(__('admin/command.two_fa_recovery.security_notice'));

        return self::SUCCESS;
    }

    /**
     * Reset recovery codes for a member
     */
    protected function resetRecoveryCodes(): int
    {
        $member = $this->findMember();
        if (! $member) {
            return self::FAILURE;
        }

        $reason = $this->getRequiredReason();
        if (! $reason) {
            return self::FAILURE;
        }

        // Check if 2FA is enabled
        if ($member->two_fa_mode === AuthenticationMode::Disabled->value || $member->two_fa_mode === null) {
            $this->error(__('admin/command.two_fa_recovery.two_fa_not_enabled', ['name' => ($member->display_name ?? $member->account_name)]));

            return self::FAILURE;
        }

        // Confirm action
        $this->warn(__('admin/command.two_fa_recovery.warning_reset_codes'));
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm(__('admin/command.two_fa_recovery.confirm_reset_codes', ['name' => ($member->display_name ?? $member->account_name)]))) {
            $this->info(__('admin/command.two_fa_recovery.cancelled'));

            return self::SUCCESS;
        }

        // Generate new recovery codes
        $codes = $this->generateRecoveryCodes();
        $hashedCodes = array_map(fn ($code) => Hash::make($code), $codes);

        $member->update([
            'two_fa_recovery_codes' => json_encode($hashedCodes),
        ]);

        $this->info(__('admin/command.two_fa_recovery.codes_reset_success', ['name' => ($member->display_name ?? $member->account_name)]));
        $this->newLine();

        // Display new codes
        $this->warn(__('admin/command.two_fa_recovery.new_codes_warning'));
        $this->newLine();

        foreach ($codes as $index => $code) {
            $this->line(sprintf('  %d. %s', $index + 1, $code));
        }

        $this->newLine();
        $this->warn(__('admin/command.two_fa_recovery.codes_save_warning'));

        // Log to audit
        AuditService::log(
            action: 'two_fa_recovery_codes_reset',
            category: 'security',
            severity: 'critical',
            outcome: 'success',
            actorId: null,
            context: [
                'member_id' => $member->id,
                'member_email' => $member->email,
                'codes_count' => count($codes),
                'reason' => $reason,
                'triggered_by' => 'cli',
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Show 2FA status for a member or system
     */
    protected function showStatus(): int
    {
        $memberOption = $this->option('member');

        if ($memberOption) {
            $member = $this->findMember();
            if (! $member) {
                return self::FAILURE;
            }
            $this->showMemberTwoFaStatus($member);
        } else {
            $this->showSystemTwoFaStatus();
        }

        return self::SUCCESS;
    }

    /**
     * List members with 2FA enabled
     */
    protected function listMembers(): int
    {
        $members = Member::whereNotNull('two_fa_mode')
            ->where('two_fa_mode', '!=', AuthenticationMode::Disabled->value)
            ->get();

        if ($members->isEmpty()) {
            $this->info(__('admin/command.two_fa_recovery.no_members_with_two_fa'));

            return self::SUCCESS;
        }

        $this->info(__('admin/command.two_fa_recovery.members_with_two_fa', ['count' => $members->count()]));
        $this->newLine();

        $rows = [];
        foreach ($members as $member) {
            $hasRecoveryCodes = ! empty($member->two_fa_recovery_codes);
            $codesCount = $hasRecoveryCodes ? count(json_decode($member->two_fa_recovery_codes, true) ?? []) : 0;

            $rows[] = [
                $member->id,
                ($member->display_name ?? $member->account_name),
                $member->email,
                $member->two_fa_mode?->label() ?? '-',
                $codesCount > 0 ? $codesCount : __('admin/command.two_fa_recovery.no_codes'),
            ];
        }

        $this->table(
            [
                __('admin/command.two_fa_recovery.col_id'),
                __('admin/command.two_fa_recovery.col_name'),
                __('admin/command.two_fa_recovery.col_email'),
                __('admin/command.two_fa_recovery.col_mode'),
                __('admin/command.two_fa_recovery.col_recovery_codes'),
            ],
            $rows
        );

        return self::SUCCESS;
    }

    /**
     * Find member by ID or email
     */
    protected function findMember(): ?Member
    {
        $identifier = $this->option('member');

        if (! $identifier) {
            $identifier = $this->ask(__('admin/command.two_fa_recovery.member_prompt'));
        }

        if (! $identifier) {
            $this->error(__('admin/command.two_fa_recovery.member_required'));

            return null;
        }

        // Try to find by ID first, then by email
        $member = is_numeric($identifier)
            ? Member::find($identifier)
            : Member::where('email', $identifier)->first();

        if (! $member) {
            $this->error(__('admin/command.two_fa_recovery.member_not_found', ['identifier' => $identifier]));

            return null;
        }

        return $member;
    }

    /**
     * Get required reason
     */
    protected function getRequiredReason(): ?string
    {
        $reason = $this->option('reason');

        if (! $reason) {
            $reason = $this->ask(__('admin/command.two_fa_recovery.reason_prompt'));
        }

        if (! $reason) {
            $this->error(__('admin/command.two_fa_recovery.reason_required'));

            return null;
        }

        return $reason;
    }

    /**
     * Show member's 2FA status
     */
    protected function showMemberTwoFaStatus(Member $member): void
    {
        $this->info(__('admin/command.two_fa_recovery.member_status_title', ['name' => ($member->display_name ?? $member->account_name)]));
        $this->newLine();

        $hasRecoveryCodes = ! empty($member->two_fa_recovery_codes);
        $codesCount = $hasRecoveryCodes ? count(json_decode($member->two_fa_recovery_codes, true) ?? []) : 0;

        $this->table(
            [__('admin/command.two_fa_recovery.field'), __('admin/command.two_fa_recovery.value')],
            [
                [__('admin/command.two_fa_recovery.member_id'), $member->id],
                [__('admin/command.two_fa_recovery.email'), $member->email],
                [__('admin/command.two_fa_recovery.two_fa_mode'), $member->two_fa_mode?->label() ?? __('admin/command.two_fa_recovery.disabled')],
                [__('admin/command.two_fa_recovery.recovery_codes_remaining'), $codesCount > 0 ? $codesCount : __('admin/command.two_fa_recovery.none')],
            ]
        );
    }

    /**
     * Show system-wide 2FA status
     */
    protected function showSystemTwoFaStatus(): void
    {
        $this->info(__('admin/command.two_fa_recovery.system_status_title'));
        $this->newLine();

        $totalMembers = Member::count();
        $membersWithTwoFa = Member::whereNotNull('two_fa_mode')
            ->where('two_fa_mode', '!=', AuthenticationMode::Disabled->value)
            ->count();
        $membersWithoutRecoveryCodes = Member::whereNotNull('two_fa_mode')
            ->where('two_fa_mode', '!=', AuthenticationMode::Disabled->value)
            ->whereNull('two_fa_recovery_codes')
            ->count();

        $this->table(
            [__('admin/command.two_fa_recovery.metric'), __('admin/command.two_fa_recovery.value')],
            [
                [__('admin/command.two_fa_recovery.total_members'), $totalMembers],
                [__('admin/command.two_fa_recovery.members_with_two_fa'), $membersWithTwoFa],
                [__('admin/command.two_fa_recovery.members_without_codes'), $membersWithoutRecoveryCodes],
            ]
        );

        if ($membersWithoutRecoveryCodes > 0) {
            $this->newLine();
            $this->warn(__('admin/command.two_fa_recovery.warning_no_codes', ['count' => $membersWithoutRecoveryCodes]));
        }
    }

    /**
     * Generate recovery codes
     */
    protected function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))).'-'.strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }

    /**
     * Handle invalid action
     */
    protected function invalidAction(string $action): int
    {
        $this->error(__('admin/command.two_fa_recovery.invalid_action', ['action' => $action]));
        $this->line(__('admin/command.two_fa_recovery.valid_actions'));
        $this->line('  - disable     : '.__('admin/command.two_fa_recovery.action_disable'));
        $this->line('  - reset-codes : '.__('admin/command.two_fa_recovery.action_reset_codes'));
        $this->line('  - status      : '.__('admin/command.two_fa_recovery.action_status'));
        $this->line('  - list        : '.__('admin/command.two_fa_recovery.action_list'));

        return self::FAILURE;
    }
}
