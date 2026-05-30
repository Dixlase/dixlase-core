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

namespace App\Console\Commands;

use App\Facades\Audit;
use App\Helpers\IpAccessControlHelper;
use App\Models\SecuritySetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * IP control settings reset command
 *
 * Recovery command for when administrator is locked out by IP restrictions
 */
class SecurityResetIpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:reset-ip
                            {--disable-all : Disable all IP restrictions}
                            {--add-ip= : Add IP to allow list}
                            {--remove-blocked= : Remove IP from block list}
                            {--show : Display current IP restriction settings}
                            {--force : 確認なしで実行}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset or change IP restriction settings (for recovery when administrator is locked out)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! Schema::hasTable('global_settings')) {
            $this->error(__('admin/command/security-reset-ip.table_not_found'));

            return Command::FAILURE;
        }

        // Display current settings
        if ($this->option('show')) {
            return $this->showCurrentSettings();
        }

        // Disable all IP restrictions
        if ($this->option('disable-all')) {
            return $this->disableAllIpRestrictions();
        }

        // Add IP to allow list
        if ($ip = $this->option('add-ip')) {
            return $this->addToAllowedList($ip);
        }

        // Remove IP from block list
        if ($ip = $this->option('remove-blocked')) {
            return $this->removeFromBlockedList($ip);
        }

        // Display help if no options are specified
        $this->info(__('admin/command/security-reset-ip.usage'));
        $this->newLine();
        $this->line('  --show              '.__('admin/command/security-reset-ip.option_show'));
        $this->line('  --disable-all       '.__('admin/command/security-reset-ip.option_disable_all'));
        $this->line('  --add-ip=<IP>       '.__('admin/command/security-reset-ip.option_add_ip'));
        $this->line('  --remove-blocked=<IP> '.__('admin/command/security-reset-ip.option_remove_blocked'));
        $this->line('  --force             '.__('admin/command/security-reset-ip.option_force'));

        return Command::SUCCESS;
    }

    /**
     * Display current IP restriction settings
     */
    protected function showCurrentSettings(): int
    {
        $this->info(__('admin/command/security-reset-ip.current_settings'));
        $this->newLine();

        // Admin panel IP restriction
        $enableAllowed = SecuritySetting::get('enable_allowed_admin_ips', false);
        $allowedIps = SecuritySetting::get('allowed_admin_ips', '');
        $enableBlocked = SecuritySetting::get('enable_blocked_admin_ips', false);
        $blockedIps = SecuritySetting::get('blocked_admin_ips', '');

        $this->table(
            [__('admin/command/security-reset-ip.setting'), __('admin/command/security-reset-ip.value')],
            [
                [__('admin/command/security-reset-ip.admin_allow_enabled'), $enableAllowed ? '✅ '.__('common.enabled') : '❌ '.__('common.disabled')],
                [__('admin/command/security-reset-ip.admin_allowed_ips'), $allowedIps ?: __('admin/command/security-reset-ip.none')],
                [__('admin/command/security-reset-ip.admin_block_enabled'), $enableBlocked ? '✅ '.__('common.enabled') : '❌ '.__('common.disabled')],
                [__('admin/command/security-reset-ip.admin_blocked_ips'), $blockedIps ?: __('admin/command/security-reset-ip.none')],
            ]
        );

        // Front IP restriction
        $enableFrontAllowed = SecuritySetting::get('enable_allowed_front_ips', false);
        $frontAllowedIps = SecuritySetting::get('allowed_front_ips', '');
        $enableFrontBlocked = SecuritySetting::get('enable_blocked_front_ips', false);
        $frontBlockedIps = SecuritySetting::get('blocked_front_ips', '');

        $this->newLine();
        $this->info(__('admin/command/security-reset-ip.front_settings'));
        $this->table(
            [__('admin/command/security-reset-ip.setting'), __('admin/command/security-reset-ip.value')],
            [
                [__('admin/command/security-reset-ip.front_allow_enabled'), $enableFrontAllowed ? '✅ '.__('common.enabled') : '❌ '.__('common.disabled')],
                [__('admin/command/security-reset-ip.front_allowed_ips'), $frontAllowedIps ?: __('admin/command/security-reset-ip.none')],
                [__('admin/command/security-reset-ip.front_block_enabled'), $enableFrontBlocked ? '✅ '.__('common.enabled') : '❌ '.__('common.disabled')],
                [__('admin/command/security-reset-ip.front_blocked_ips'), $frontBlockedIps ?: __('admin/command/security-reset-ip.none')],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Disable all IP restrictions
     */
    protected function disableAllIpRestrictions(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm(__('admin/command/security-reset-ip.confirm_disable_all'))) {
                $this->info(__('admin/command/security-reset-ip.cancelled'));

                return Command::SUCCESS;
            }
        }

        // Disable admin panel IP restriction
        SecuritySetting::set('enable_allowed_admin_ips', 0);
        SecuritySetting::set('enable_blocked_admin_ips', 0);

        // Disable front IP restriction
        SecuritySetting::set('enable_allowed_front_ips', 0);
        SecuritySetting::set('enable_blocked_front_ips', 0);

        // Record to audit log
        $this->logAudit('ip_restrictions_disabled', [
            'action' => 'disable_all',
            'executed_via' => 'cli',
        ]);

        $this->info(__('admin/command/security-reset-ip.disabled_all'));
        $this->warn(__('admin/command/security-reset-ip.security_warning'));

        return Command::SUCCESS;
    }

    /**
     * Add IP to allow list
     */
    protected function addToAllowedList(string $ip): int
    {
        if (! IpAccessControlHelper::isValidIpOrCidr($ip)) {
            $this->error(__('admin/command/security-reset-ip.invalid_ip', ['ip' => $ip]));

            return Command::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm(__('admin/command/security-reset-ip.confirm_add_ip', ['ip' => $ip]))) {
                $this->info(__('admin/command/security-reset-ip.cancelled'));

                return Command::SUCCESS;
            }
        }

        $ipList = IpAccessControlHelper::parseList((string) SecuritySetting::get('allowed_admin_ips', ''));

        if (in_array($ip, $ipList, true)) {
            $this->warn(__('admin/command/security-reset-ip.ip_already_exists', ['ip' => $ip]));

            return Command::SUCCESS;
        }

        $ipList[] = $ip;
        SecuritySetting::set('allowed_admin_ips', implode("\n", $ipList));

        // Record to audit log
        $this->logAudit('ip_added_to_allowlist', [
            'ip' => $ip,
            'executed_via' => 'cli',
        ]);

        $this->info(__('admin/command/security-reset-ip.ip_added', ['ip' => $ip]));

        return Command::SUCCESS;
    }

    /**
     * Remove IP from block list
     */
    protected function removeFromBlockedList(string $ip): int
    {
        if (! IpAccessControlHelper::isValidIpOrCidr($ip)) {
            $this->error(__('admin/command/security-reset-ip.invalid_ip', ['ip' => $ip]));

            return Command::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm(__('admin/command/security-reset-ip.confirm_remove_ip', ['ip' => $ip]))) {
                $this->info(__('admin/command/security-reset-ip.cancelled'));

                return Command::SUCCESS;
            }
        }

        $ipList = IpAccessControlHelper::parseList((string) SecuritySetting::get('blocked_admin_ips', ''));

        if (! in_array($ip, $ipList, true)) {
            $this->warn(__('admin/command/security-reset-ip.ip_not_in_blocklist', ['ip' => $ip]));

            return Command::SUCCESS;
        }

        $ipList = array_values(array_diff($ipList, [$ip]));
        SecuritySetting::set('blocked_admin_ips', implode("\n", $ipList));

        // Record to audit log
        $this->logAudit('ip_removed_from_blocklist', [
            'ip' => $ip,
            'executed_via' => 'cli',
        ]);

        $this->info(__('admin/command/security-reset-ip.ip_removed', ['ip' => $ip]));

        return Command::SUCCESS;
    }

    /**
     * Record to audit log.
     *
     * Uses the Audit facade — the Audit::logSecurity() helper takes the
     * action name plus a data array. The previous implementation tried to
     * call AuditService::log() statically with named arguments, which is
     * invalid on both counts (the method is non-static and accepts a single
     * array). The facade swallows any logging failure internally so a
     * broken audit channel never blocks the recovery flow this command is
     * meant to provide.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logAudit(string $action, array $context = []): void
    {
        Audit::logSecurity($action, [
            'severity' => 'warning',
            'outcome' => 'success',
            'context' => $context,
        ]);
    }
}
