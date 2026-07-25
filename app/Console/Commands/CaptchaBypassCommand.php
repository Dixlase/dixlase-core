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

use App\Services\AuditService;
use App\Services\CaptchaBypassService;
use Illuminate\Console\Command;

/**
 * CAPTCHA emergency bypass command (break glass)
 *
 * Allows administrator to log in when CAPTCHA provider fails
 * Emergency recovery feature to temporarily bypass CAPTCHA verification
 */
class CaptchaBypassCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:admin:captcha-bypass
                            {action=status : Action to perform (enable, disable, status)}
                            {--minutes=10 : Duration in minutes (max 60)}
                            {--scope=admin_login : Scope of bypass (admin_login, all)}
                            {--reason= : Reason for enabling bypass (required for enable)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emergency CAPTCHA bypass for disaster recovery (break-glass)';

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
     * Enable CAPTCHA bypass
     */
    protected function enableBypass(): int
    {
        $minutes = min((int) $this->option('minutes'), 60); // Max 60 minutes
        $scope = $this->option('scope');
        $reason = $this->option('reason');

        // Validate scope
        if (! in_array($scope, ['admin_login', 'all'])) {
            $this->error(__('admin/command/captcha-bypass.invalid_scope', ['scope' => $scope]));

            return self::FAILURE;
        }

        // Require reason
        if (empty($reason)) {
            $reason = $this->ask(__('admin/command/captcha-bypass.reason_prompt'));
            if (empty($reason)) {
                $this->error(__('admin/command/captcha-bypass.reason_required'));

                return self::FAILURE;
            }
        }

        // Confirm action
        $this->warn(__('admin/command/captcha-bypass.warning'));
        $this->newLine();
        $this->line(__('admin/command/captcha-bypass.confirm_details', [
            'minutes' => $minutes,
            'scope' => $scope,
            'reason' => $reason,
        ]));
        $this->newLine();

        if (! $this->confirm(__('admin/command/captcha-bypass.confirm_enable'))) {
            $this->info(__('admin/command/captcha-bypass.cancelled'));

            return self::SUCCESS;
        }

        // Enable bypass
        $result = CaptchaBypassService::enable($minutes, $scope, $reason);

        if ($result) {
            $expiresAt = now()->addMinutes($minutes);

            $this->info(__('admin/command/captcha-bypass.enabled', [
                'minutes' => $minutes,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]));

            // Log to audit
            app(AuditService::class)->log([
                'action' => 'captcha_bypass_enabled',
                'category' => 'security',
                'severity' => 'critical',
                'outcome' => 'success',
                'context' => [
                    'minutes' => $minutes,
                    'scope' => $scope,
                    'reason' => $reason,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'triggered_by' => 'cli',
                ],
            ]);

            return self::SUCCESS;
        }

        $this->error(__('admin/command/captcha-bypass.enable_failed'));

        return self::FAILURE;
    }

    /**
     * Disable CAPTCHA bypass
     */
    protected function disableBypass(): int
    {
        if (! CaptchaBypassService::isActive()) {
            $this->info(__('admin/command/captcha-bypass.not_active'));

            return self::SUCCESS;
        }

        CaptchaBypassService::disable();

        $this->info(__('admin/command/captcha-bypass.disabled'));

        // Log to audit
        app(AuditService::class)->log([
            'action' => 'captcha_bypass_disabled',
            'category' => 'security',
            'severity' => 'warning',
            'outcome' => 'success',
            'context' => [
                'triggered_by' => 'cli',
                'manual_disable' => true,
            ],
        ]);

        return self::SUCCESS;
    }

    /**
     * Show bypass status
     */
    protected function showStatus(): int
    {
        $status = CaptchaBypassService::getStatus();

        $this->info(__('admin/command/captcha-bypass.status_title'));
        $this->newLine();

        if ($status['active']) {
            $this->warn(__('admin/command/captcha-bypass.status_active'));
            $this->table(
                [__('admin/command/captcha-bypass.field'), __('admin/command/captcha-bypass.value')],
                [
                    [__('admin/command/captcha-bypass.scope'), $status['scope']],
                    [__('admin/command/captcha-bypass.reason'), $status['reason']],
                    [__('admin/command/captcha-bypass.expires_at'), $status['expires_at']],
                    [__('admin/command/captcha-bypass.remaining'), $status['remaining_minutes'].' '.__('admin/command/captcha-bypass.minutes')],
                    [__('admin/command/captcha-bypass.enabled_at'), $status['enabled_at']],
                ]
            );
        } else {
            $this->info(__('admin/command/captcha-bypass.status_inactive'));
        }

        // Show recent bypass history
        $history = CaptchaBypassService::getRecentHistory(5);
        if ($history->isNotEmpty()) {
            $this->newLine();
            $this->info(__('admin/command/captcha-bypass.recent_history'));

            $rows = [];
            foreach ($history as $log) {
                $rows[] = [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->action,
                    $log->context['scope'] ?? '-',
                    $log->context['reason'] ?? '-',
                ];
            }

            $this->table(
                [
                    __('admin/command/captcha-bypass.time'),
                    __('admin/command/captcha-bypass.action'),
                    __('admin/command/captcha-bypass.scope'),
                    __('admin/command/captcha-bypass.reason'),
                ],
                $rows
            );
        }

        return self::SUCCESS;
    }

    /**
     * Handle invalid action
     */
    protected function invalidAction(string $action): int
    {
        $this->error(__('admin/command/captcha-bypass.invalid_action', ['action' => $action]));
        $this->line(__('admin/command/captcha-bypass.valid_actions'));
        $this->line('  - enable   : '.__('admin/command/captcha-bypass.action_enable'));
        $this->line('  - disable  : '.__('admin/command/captcha-bypass.action_disable'));
        $this->line('  - status   : '.__('admin/command/captcha-bypass.action_status'));

        return self::FAILURE;
    }
}
