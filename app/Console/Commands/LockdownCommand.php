<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

use App\Models\LockdownStatus;
use App\Services\LockdownService;
use Illuminate\Console\Command;

/**
 * 緊急ロックダウンコマンド
 *
 * セキュリティインシデント時の即座のシステム保護
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
     * ロックダウンを発動
     */
    protected function handleActivate(): int
    {
        $type = $this->option('type');
        $reason = $this->option('reason') ?? 'Activated via CLI';
        $duration = $this->option('duration') ? (int) $this->option('duration') : null;
        $allowedIps = $this->option('allow-ip') ?: null;
        $allowedMembers = $this->option('allow-member') ? array_map('intval', $this->option('allow-member')) : null;

        // 確認
        if (!$this->option('force')) {
            $this->warn(__('command.lockdown.warning'));
            $this->newLine();
            $this->line(__('command.lockdown.type') . ": {$type}");
            $this->line(__('command.lockdown.reason') . ": {$reason}");
            if ($duration) {
                $this->line(__('command.lockdown.duration') . ": {$duration} " . __('command.lockdown.minutes'));
            }
            $this->newLine();

            if (!$this->confirm(__('command.lockdown.confirm_activate'))) {
                $this->info(__('command.lockdown.cancelled'));
                return self::SUCCESS;
            }
        }

        // ロックダウン発動
        $lockdown = LockdownService::activate(
            $type,
            $reason,
            null, // CLI実行なのでtriggered_byはnull
            $duration,
            $allowedIps,
            $allowedMembers
        );

        $this->error(__('command.lockdown.activated'));
        $this->newLine();
        $this->table(
            [__('command.lockdown.field'), __('command.lockdown.value')],
            [
                [__('command.lockdown.type'), $lockdown->type],
                [__('command.lockdown.reason'), $lockdown->reason],
                [__('command.lockdown.triggered_at'), $lockdown->triggered_at->format('Y-m-d H:i:s')],
                [__('command.lockdown.auto_release'), $lockdown->auto_release_at?->format('Y-m-d H:i:s') ?? '-'],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * ロックダウンを解除
     */
    protected function handleDeactivate(): int
    {
        $lockdown = LockdownService::getStatus();

        if (!$lockdown) {
            $this->info(__('command.lockdown.not_active'));
            return self::SUCCESS;
        }

        // 確認
        if (!$this->option('force')) {
            $this->info(__('command.lockdown.current_status'));
            $this->line(__('command.lockdown.type') . ": {$lockdown->type}");
            $this->line(__('command.lockdown.reason') . ": {$lockdown->reason}");
            $this->newLine();

            if (!$this->confirm(__('command.lockdown.confirm_deactivate'))) {
                $this->info(__('command.lockdown.cancelled'));
                return self::SUCCESS;
            }
        }

        $reason = $this->option('reason') ?? 'Deactivated via CLI';
        LockdownService::deactivate(null, $reason);

        $this->info(__('command.lockdown.deactivated'));

        return self::SUCCESS;
    }

    /**
     * ロックダウン状態を表示
     */
    protected function handleStatus(): int
    {
        $lockdown = LockdownService::getStatus();

        if (!$lockdown) {
            $this->info(__('command.lockdown.not_active'));
            return self::SUCCESS;
        }

        $this->error(__('command.lockdown.is_active'));
        $this->newLine();

        $this->table(
            [__('command.lockdown.field'), __('command.lockdown.value')],
            [
                [__('command.lockdown.type'), $lockdown->type],
                [__('command.lockdown.reason'), $lockdown->reason ?? '-'],
                [__('command.lockdown.triggered_at'), $lockdown->triggered_at->format('Y-m-d H:i:s')],
                [__('command.lockdown.duration'), $lockdown->triggered_at->diffForHumans(now(), true)],
                [__('command.lockdown.auto_release'), $lockdown->auto_release_at?->format('Y-m-d H:i:s') ?? '-'],
                [__('command.lockdown.allowed_ips'), $lockdown->allowed_ips ? implode(', ', $lockdown->allowed_ips) : '-'],
                [__('command.lockdown.allowed_members'), $lockdown->allowed_members ? implode(', ', $lockdown->allowed_members) : '-'],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * ロックダウン履歴を表示
     */
    protected function handleHistory(): int
    {
        $history = \App\Models\LockdownHistory::recent(30)
            ->orderBy('performed_at', 'desc')
            ->limit(20)
            ->get();

        if ($history->isEmpty()) {
            $this->info(__('command.lockdown.no_history'));
            return self::SUCCESS;
        }

        $this->info(__('command.lockdown.history_title'));
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
                __('command.lockdown.datetime'),
                __('command.lockdown.action'),
                __('command.lockdown.type'),
                __('command.lockdown.reason'),
                __('command.lockdown.ip'),
            ],
            $rows
        );

        return self::SUCCESS;
    }

    /**
     * 不明なアクション
     */
    protected function handleUnknownAction(string $action): int
    {
        $this->error(__('command.lockdown.unknown_action', ['action' => $action]));
        $this->line(__('command.lockdown.available_actions'));
        $this->line('  - activate   : ' . __('command.lockdown.action_activate'));
        $this->line('  - deactivate : ' . __('command.lockdown.action_deactivate'));
        $this->line('  - status     : ' . __('command.lockdown.action_status'));
        $this->line('  - history    : ' . __('command.lockdown.action_history'));

        return self::FAILURE;
    }
}
