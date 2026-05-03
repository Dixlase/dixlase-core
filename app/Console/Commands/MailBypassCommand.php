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

use App\Services\AuditService;
use App\Services\MailBypassService;
use Illuminate\Console\Command;

/**
 * メール送信緊急バイパスコマンド（ブレークグラス）
 *
 * SMTPサーバー障害時に、メール依存機能（Two-FA、パスワードリセット等）を
 * 一時的にバイパスする緊急復旧機能
 */
class MailBypassCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:mail-bypass
                            {action=status : Action to perform (enable, disable, status)}
                            {--minutes=30 : Duration in minutes (max 120)}
                            {--scope=two_fa : Scope of bypass (two_fa, password_reset, all)}
                            {--reason= : Reason for enabling bypass (required)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emergency mail bypass for SMTP failure recovery (break-glass)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'enable' => $this->enableBypass(),
            'disable' => $this->disableBypass(),
            'status' => $this->showStatus(),
            default => $this->invalidAction($action),
        };
    }

    /**
     * Enable mail bypass
     */
    protected function enableBypass(): int
    {
        $minutes = min((int) $this->option('minutes'), 120); // Max 120 minutes
        $scope = $this->option('scope');
        $reason = $this->option('reason');

        // Validate scope
        if (! in_array($scope, ['two_fa', 'password_reset', 'all'])) {
            $this->error(__('admin/command.mail_bypass.invalid_scope', ['scope' => $scope]));

            return self::FAILURE;
        }

        // Require reason
        if (empty($reason)) {
            $reason = $this->ask(__('admin/command.mail_bypass.reason_prompt'));
            if (empty($reason)) {
                $this->error(__('admin/command.mail_bypass.reason_required'));

                return self::FAILURE;
            }
        }

        // Confirm action
        $this->warn(__('admin/command.mail_bypass.warning'));
        $this->newLine();
        $this->line(__('admin/command.mail_bypass.confirm_details', [
            'minutes' => $minutes,
            'scope' => $scope,
            'reason' => $reason,
        ]));
        $this->newLine();

        if (! $this->confirm(__('admin/command.mail_bypass.confirm_enable'))) {
            $this->info(__('admin/command.mail_bypass.cancelled'));

            return self::SUCCESS;
        }

        // Enable bypass
        $result = MailBypassService::enable($minutes, $scope, $reason);

        if ($result) {
            $expiresAt = now()->addMinutes($minutes);

            $this->info(__('admin/command.mail_bypass.enabled', [
                'minutes' => $minutes,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]));

            // Log to audit
            AuditService::log(
                action: 'mail_bypass_enabled',
                category: 'security',
                severity: 'critical',
                outcome: 'success',
                context: [
                    'minutes' => $minutes,
                    'scope' => $scope,
                    'reason' => $reason,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'triggered_by' => 'cli',
                ]
            );

            $this->warn(__('admin/command.mail_bypass.security_notice'));

            return self::SUCCESS;
        }

        $this->error(__('admin/command.mail_bypass.enable_failed'));

        return self::FAILURE;
    }

    /**
     * Disable mail bypass
     */
    protected function disableBypass(): int
    {
        if (! MailBypassService::isActive()) {
            $this->info(__('admin/command.mail_bypass.not_active'));

            return self::SUCCESS;
        }

        MailBypassService::disable();

        $this->info(__('admin/command.mail_bypass.disabled'));

        // Log to audit
        AuditService::log(
            action: 'mail_bypass_disabled',
            category: 'security',
            severity: 'warning',
            outcome: 'success',
            context: [
                'triggered_by' => 'cli',
                'manual_disable' => true,
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Show bypass status
     */
    protected function showStatus(): int
    {
        $status = MailBypassService::getStatus();

        $this->info(__('admin/command.mail_bypass.status_title'));
        $this->newLine();

        if ($status['active']) {
            $this->warn(__('admin/command.mail_bypass.status_active'));
            $this->table(
                [__('admin/command.mail_bypass.field'), __('admin/command.mail_bypass.value')],
                [
                    [__('admin/command.mail_bypass.scope'), $status['scope']],
                    [__('admin/command.mail_bypass.reason'), $status['reason']],
                    [__('admin/command.mail_bypass.expires_at'), $status['expires_at']],
                    [__('admin/command.mail_bypass.remaining'), $status['remaining_minutes'].' '.__('admin/command.mail_bypass.minutes')],
                    [__('admin/command.mail_bypass.enabled_at'), $status['enabled_at']],
                ]
            );

            $this->newLine();
            $this->warn(__('admin/command.mail_bypass.affected_features'));

            if ($status['scope'] === 'all' || $status['scope'] === 'two_fa') {
                $this->line('  - '.__('admin/command.mail_bypass.feature_two_fa'));
            }
            if ($status['scope'] === 'all' || $status['scope'] === 'password_reset') {
                $this->line('  - '.__('admin/command.mail_bypass.feature_password_reset'));
            }
        } else {
            $this->info(__('admin/command.mail_bypass.status_inactive'));
        }

        return self::SUCCESS;
    }

    /**
     * Handle invalid action
     */
    protected function invalidAction(string $action): int
    {
        $this->error(__('admin/command.mail_bypass.invalid_action', ['action' => $action]));
        $this->line(__('admin/command.mail_bypass.valid_actions'));
        $this->line('  - enable   : '.__('admin/command.mail_bypass.action_enable'));
        $this->line('  - disable  : '.__('admin/command.mail_bypass.action_disable'));
        $this->line('  - status   : '.__('admin/command.mail_bypass.action_status'));

        return self::FAILURE;
    }
}
