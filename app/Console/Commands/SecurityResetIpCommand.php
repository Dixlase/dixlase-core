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

use App\Models\SecuritySetting;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * IP制御設定リセットコマンド
 *
 * IP制限で管理者が締め出された場合の復旧用コマンド
 */
class SecurityResetIpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:reset-ip 
                            {--disable-all : 全てのIP制限を無効化}
                            {--add-ip= : 許可リストにIPを追加}
                            {--remove-blocked= : ブロックリストからIPを削除}
                            {--show : 現在のIP制限設定を表示}
                            {--force : 確認なしで実行}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'IP制限設定をリセットまたは変更（管理者締め出し時の復旧用）';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! Schema::hasTable('global_settings')) {
            $this->error(__('admin/command.security_reset_ip.table_not_found'));

            return Command::FAILURE;
        }

        // 現在の設定を表示
        if ($this->option('show')) {
            return $this->showCurrentSettings();
        }

        // 全てのIP制限を無効化
        if ($this->option('disable-all')) {
            return $this->disableAllIpRestrictions();
        }

        // 許可リストにIPを追加
        if ($ip = $this->option('add-ip')) {
            return $this->addToAllowedList($ip);
        }

        // ブロックリストからIPを削除
        if ($ip = $this->option('remove-blocked')) {
            return $this->removeFromBlockedList($ip);
        }

        // オプションが指定されていない場合はヘルプを表示
        $this->info(__('admin/command.security_reset_ip.usage'));
        $this->newLine();
        $this->line('  --show              '.__('admin/command.security_reset_ip.option_show'));
        $this->line('  --disable-all       '.__('admin/command.security_reset_ip.option_disable_all'));
        $this->line('  --add-ip=<IP>       '.__('admin/command.security_reset_ip.option_add_ip'));
        $this->line('  --remove-blocked=<IP> '.__('admin/command.security_reset_ip.option_remove_blocked'));
        $this->line('  --force             '.__('admin/command.security_reset_ip.option_force'));

        return Command::SUCCESS;
    }

    /**
     * 現在のIP制限設定を表示
     */
    protected function showCurrentSettings(): int
    {
        $this->info(__('admin/command.security_reset_ip.current_settings'));
        $this->newLine();

        // 管理画面IP制限
        $enableAllowed = SecuritySetting::get('enable_allowed_admin_ips', false);
        $allowedIps = SecuritySetting::get('allowed_admin_ips', '');
        $enableBlocked = SecuritySetting::get('enable_blocked_admin_ips', false);
        $blockedIps = SecuritySetting::get('blocked_admin_ips', '');

        $this->table(
            [__('admin/command.security_reset_ip.setting'), __('admin/command.security_reset_ip.value')],
            [
                [__('admin/command.security_reset_ip.admin_allow_enabled'), $enableAllowed ? '✅ '.__('common.enabled') : '❌ '.__('common.disabled')],
                [__('admin/command.security_reset_ip.admin_allowed_ips'), $allowedIps ?: __('admin/command.security_reset_ip.none')],
                [__('admin/command.security_reset_ip.admin_block_enabled'), $enableBlocked ? '✅ '.__('common.enabled') : '❌ '.__('common.disabled')],
                [__('admin/command.security_reset_ip.admin_blocked_ips'), $blockedIps ?: __('admin/command.security_reset_ip.none')],
            ]
        );

        // フロントIP制限
        $enableFrontAllowed = SecuritySetting::get('enable_allowed_front_ips', false);
        $frontAllowedIps = SecuritySetting::get('allowed_front_ips', '');
        $enableFrontBlocked = SecuritySetting::get('enable_blocked_front_ips', false);
        $frontBlockedIps = SecuritySetting::get('blocked_front_ips', '');

        $this->newLine();
        $this->info(__('admin/command.security_reset_ip.front_settings'));
        $this->table(
            [__('admin/command.security_reset_ip.setting'), __('admin/command.security_reset_ip.value')],
            [
                [__('admin/command.security_reset_ip.front_allow_enabled'), $enableFrontAllowed ? '✅ '.__('common.enabled') : '❌ '.__('common.disabled')],
                [__('admin/command.security_reset_ip.front_allowed_ips'), $frontAllowedIps ?: __('admin/command.security_reset_ip.none')],
                [__('admin/command.security_reset_ip.front_block_enabled'), $enableFrontBlocked ? '✅ '.__('common.enabled') : '❌ '.__('common.disabled')],
                [__('admin/command.security_reset_ip.front_blocked_ips'), $frontBlockedIps ?: __('admin/command.security_reset_ip.none')],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * 全てのIP制限を無効化
     */
    protected function disableAllIpRestrictions(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm(__('admin/command.security_reset_ip.confirm_disable_all'))) {
                $this->info(__('admin/command.security_reset_ip.cancelled'));

                return Command::SUCCESS;
            }
        }

        // 管理画面IP制限を無効化
        SecuritySetting::set('enable_allowed_admin_ips', 0);
        SecuritySetting::set('enable_blocked_admin_ips', 0);

        // フロントIP制限を無効化
        SecuritySetting::set('enable_allowed_front_ips', 0);
        SecuritySetting::set('enable_blocked_front_ips', 0);

        // 監査ログに記録
        $this->logAudit('ip_restrictions_disabled', [
            'action' => 'disable_all',
            'executed_via' => 'cli',
        ]);

        $this->info(__('admin/command.security_reset_ip.disabled_all'));
        $this->warn(__('admin/command.security_reset_ip.security_warning'));

        return Command::SUCCESS;
    }

    /**
     * 許可リストにIPを追加
     */
    protected function addToAllowedList(string $ip): int
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error(__('admin/command.security_reset_ip.invalid_ip', ['ip' => $ip]));

            return Command::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm(__('admin/command.security_reset_ip.confirm_add_ip', ['ip' => $ip]))) {
                $this->info(__('admin/command.security_reset_ip.cancelled'));

                return Command::SUCCESS;
            }
        }

        $currentIps = SecuritySetting::get('allowed_admin_ips', '');
        $ipList = array_filter(explode(',', $currentIps));

        if (in_array($ip, $ipList)) {
            $this->warn(__('admin/command.security_reset_ip.ip_already_exists', ['ip' => $ip]));

            return Command::SUCCESS;
        }

        $ipList[] = $ip;
        SecuritySetting::set('allowed_admin_ips', implode(',', $ipList));

        // 監査ログに記録
        $this->logAudit('ip_added_to_allowlist', [
            'ip' => $ip,
            'executed_via' => 'cli',
        ]);

        $this->info(__('admin/command.security_reset_ip.ip_added', ['ip' => $ip]));

        return Command::SUCCESS;
    }

    /**
     * ブロックリストからIPを削除
     */
    protected function removeFromBlockedList(string $ip): int
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error(__('admin/command.security_reset_ip.invalid_ip', ['ip' => $ip]));

            return Command::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm(__('admin/command.security_reset_ip.confirm_remove_ip', ['ip' => $ip]))) {
                $this->info(__('admin/command.security_reset_ip.cancelled'));

                return Command::SUCCESS;
            }
        }

        $currentIps = SecuritySetting::get('blocked_admin_ips', '');
        $ipList = array_filter(explode(',', $currentIps));

        if (! in_array($ip, $ipList)) {
            $this->warn(__('admin/command.security_reset_ip.ip_not_in_blocklist', ['ip' => $ip]));

            return Command::SUCCESS;
        }

        $ipList = array_diff($ipList, [$ip]);
        SecuritySetting::set('blocked_admin_ips', implode(',', $ipList));

        // 監査ログに記録
        $this->logAudit('ip_removed_from_blocklist', [
            'ip' => $ip,
            'executed_via' => 'cli',
        ]);

        $this->info(__('admin/command.security_reset_ip.ip_removed', ['ip' => $ip]));

        return Command::SUCCESS;
    }

    /**
     * 監査ログに記録
     */
    protected function logAudit(string $action, array $context = []): void
    {
        if (class_exists(AuditService::class)) {
            AuditService::log(
                action: $action,
                category: 'security',
                severity: 'warning',
                outcome: 'success',
                context: $context
            );
        }
    }
}
