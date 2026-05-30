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

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LockdownService;
use Illuminate\Console\Command;

/**
 * Emergency lockdown command
 *
 * Immediate system protection during security incidents
 */
class LockdownCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lockdown
                            {action : Action to perform (activate, deactivate, status, history)}
                            {--type=full : Lockdown type (full, admin, api, login)}
                            {--reason= : Reason for lockdown}
                            {--duration= : Auto-release duration in minutes}
                            {--allow-ip=* : IP addresses to allow}
                            {--allow-member=* : Member IDs to allow}
                            {--force : Force action without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage emergency lockdown';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'activate' => $this->handleActivate(),
            'deactivate' => $this->handleDeactivate(),
            'status' => $this->handleStatus(),
            'history' => $this->handleHistory(),
            default => $this->handleUnknownAction($action),
        };
    }

    /**
     * Activate lockdown
     */
    protected function handleActivate(): int
    {
        $type = $this->option('type');
        $reason = $this->option('reason') ?? 'Activated via CLI';
        $duration = $this->option('duration') ? (int) $this->option('duration') : null;
        $allowedIps = $this->option('allow-ip') ?: null;
        $allowedMembers = $this->option('allow-member') ? array_map('intval', $this->option('allow-member')) : null;

        // Confirmation
        if (! $this->option('force')) {
            $this->warn(__('admin/command/lockdown.warning'));
            $this->newLine();
            $this->line(__('admin/command/lockdown.type').": {$type}");
            $this->line(__('admin/command/lockdown.reason').": {$reason}");
            if ($duration) {
                $this->line(__('admin/command/lockdown.duration').": {$duration} ".__('admin/command/lockdown.minutes'));
            }
            $this->newLine();

            if (! $this->confirm(__('admin/command/lockdown.confirm_activate'))) {
                $this->info(__('admin/command/lockdown.cancelled'));

                return self::SUCCESS;
            }
        }

        // Lockdown activation
        $lockdown = LockdownService::activate(
            $type,
            $reason,
            null, // triggered_by is null because this is CLI execution
            $duration,
            $allowedIps,
            $allowedMembers
        );

        $this->error(__('admin/command/lockdown.activated'));
        $this->newLine();
        $this->table(
            [__('admin/command/lockdown.field'), __('admin/command/lockdown.value')],
            [
                [__('admin/command/lockdown.type'), $lockdown->type],
                [__('admin/command/lockdown.reason'), $lockdown->reason],
                [__('admin/command/lockdown.triggered_at'), $lockdown->triggered_at->format('Y-m-d H:i:s')],
                [__('admin/command/lockdown.auto_release'), $lockdown->auto_release_at?->format('Y-m-d H:i:s') ?? '-'],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Release lockdown
     */
    protected function handleDeactivate(): int
    {
        $lockdown = LockdownService::getStatus();

        if (! $lockdown) {
            $this->info(__('admin/command/lockdown.not_active'));

            return self::SUCCESS;
        }

        // Confirmation
        if (! $this->option('force')) {
            $this->info(__('admin/command/lockdown.current_status'));
            $this->line(__('admin/command/lockdown.type').": {$lockdown->type}");
            $this->line(__('admin/command/lockdown.reason').": {$lockdown->reason}");
            $this->newLine();

            if (! $this->confirm(__('admin/command/lockdown.confirm_deactivate'))) {
                $this->info(__('admin/command/lockdown.cancelled'));

                return self::SUCCESS;
            }
        }

        $reason = $this->option('reason') ?? 'Deactivated via CLI';
        LockdownService::deactivate(null, $reason);

        $this->info(__('admin/command/lockdown.deactivated'));

        return self::SUCCESS;
    }

    /**
     * Display lockdown status
     */
    protected function handleStatus(): int
    {
        $lockdown = LockdownService::getStatus();

        if (! $lockdown) {
            $this->info(__('admin/command/lockdown.not_active'));

            return self::SUCCESS;
        }

        $this->error(__('admin/command/lockdown.is_active'));
        $this->newLine();

        $this->table(
            [__('admin/command/lockdown.field'), __('admin/command/lockdown.value')],
            [
                [__('admin/command/lockdown.type'), $lockdown->type],
                [__('admin/command/lockdown.reason'), $lockdown->reason ?? '-'],
                [__('admin/command/lockdown.triggered_at'), $lockdown->triggered_at->format('Y-m-d H:i:s')],
                [__('admin/command/lockdown.duration'), $lockdown->triggered_at->diffForHumans(now(), true)],
                [__('admin/command/lockdown.auto_release'), $lockdown->auto_release_at?->format('Y-m-d H:i:s') ?? '-'],
                [__('admin/command/lockdown.allowed_ips'), $lockdown->allowed_ips ? implode(', ', $lockdown->allowed_ips) : '-'],
                [__('admin/command/lockdown.allowed_members'), $lockdown->allowed_members ? implode(', ', $lockdown->allowed_members) : '-'],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Display lockdown history
     */
    protected function handleHistory(): int
    {
        $history = \App\Models\LockdownHistory::recent(30)
            ->orderBy('performed_at', 'desc')
            ->limit(20)
            ->get();

        if ($history->isEmpty()) {
            $this->info(__('admin/command/lockdown.no_history'));

            return self::SUCCESS;
        }

        $this->info(__('admin/command/lockdown.history_title'));
        $this->newLine();

        $rows = $history->map(function ($item) {
            return [
                $item->performed_at->format('Y-m-d H:i:s'),
                $item->action,
                $item->type,
                $item->reason ?? '-',
                $item->ip_address ?? '-',
            ];
        })->toArray();

        $this->table(
            [
                __('admin/command/lockdown.datetime'),
                __('admin/command/lockdown.action'),
                __('admin/command/lockdown.type'),
                __('admin/command/lockdown.reason'),
                __('admin/command/lockdown.ip'),
            ],
            $rows
        );

        return self::SUCCESS;
    }

    /**
     * Unknown action
     */
    protected function handleUnknownAction(string $action): int
    {
        $this->error(__('admin/command/lockdown.unknown_action', ['action' => $action]));
        $this->line(__('admin/command/lockdown.available_actions'));
        $this->line('  - activate   : '.__('admin/command/lockdown.action_activate'));
        $this->line('  - deactivate : '.__('admin/command/lockdown.action_deactivate'));
        $this->line('  - status     : '.__('admin/command/lockdown.action_status'));
        $this->line('  - history    : '.__('admin/command/lockdown.action_history'));

        return self::FAILURE;
    }
}
