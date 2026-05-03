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

use App\Enums\CaptchaProvider;
use App\Services\CaptchaFailoverService;
use Illuminate\Console\Command;

/**
 * CAPTCHAフェイルオーバー管理コマンド
 */
class CaptchaFailoverCommand extends Command
{
    protected $signature = 'dls:admin:captcha-failover 
                            {action? : status / switch / reset / providers}
                            {--provider= : 切り替え先のプロバイダー}
                            {--permanent : 永続的な切り替え}
                            {--auto-failover= : 自動フェイルオーバーの有効/無効 (on/off)}';

    protected $description = 'CAPTCHAフェイルオーバー管理';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'status' => $this->showStatus(),
            'switch' => $this->switchProvider(),
            'reset' => $this->resetToDefault(),
            'providers' => $this->listProviders(),
            default => $this->showHelp(),
        };
    }

    protected function showStatus(): int
    {
        $status = CaptchaFailoverService::getStatus();

        $this->info(__('admin/command.captcha.status_title'));
        $this->newLine();

        // 基本情報
        $this->table(
            [__('admin/command.captcha.setting'), __('admin/command.captcha.value')],
            [
                [__('admin/command.captcha.primary_provider'), $this->getProviderLabel($status['primary_provider'])],
                [__('admin/command.captcha.active_provider'), $this->getProviderLabel($status['active_provider'])],
                [__('admin/command.captcha.is_failed_over'), $status['is_failed_over'] ? '⚠️ '.__('common.yes') : __('common.no')],
                [__('admin/command.captcha.auto_failover'), $status['auto_failover_enabled'] ? '✅ '.__('common.enabled') : '❌ '.__('common.disabled')],
            ]
        );

        $this->newLine();
        $this->info(__('admin/command.captcha.configured_providers'));

        $rows = [];
        foreach ($status['configured_providers'] as $provider => $info) {
            $statusIcon = '';
            if ($info['is_active']) {
                $statusIcon = '🟢 ';
            } elseif ($info['configured'] && $info['enabled'] && $info['verified']) {
                $statusIcon = '🟡 ';
            } elseif ($info['configured']) {
                $statusIcon = '⚪ ';
            } else {
                $statusIcon = '❌ ';
            }

            $rows[] = [
                $statusIcon.$info['label'],
                $info['configured'] ? __('common.yes') : __('common.no'),
                $info['enabled'] ? __('common.yes') : __('common.no'),
                $info['verified'] ? __('common.yes') : __('common.no'),
                $info['failure_count'],
            ];
        }

        $this->table(
            [
                __('admin/command.captcha.provider'),
                __('admin/command.captcha.configured'),
                __('admin/command.captcha.enabled'),
                __('admin/command.captcha.verified'),
                __('admin/command.captcha.failures'),
            ],
            $rows
        );

        return Command::SUCCESS;
    }

    protected function switchProvider(): int
    {
        $provider = $this->option('provider');

        if (! $provider) {
            $this->error(__('admin/command.captcha.provider_required'));

            return Command::FAILURE;
        }

        // プロバイダーの検証
        $validProviders = CaptchaProvider::getAllProviders();
        if (! in_array($provider, $validProviders)) {
            $this->error(__('admin/command.captcha.invalid_provider', ['provider' => $provider]));
            $this->line(__('admin/command.captcha.valid_providers').': '.implode(', ', $validProviders));

            return Command::FAILURE;
        }

        $permanent = $this->option('permanent');

        if ($permanent) {
            if (! $this->confirm(__('admin/command.captcha.confirm_permanent_switch', ['provider' => $this->getProviderLabel($provider)]))) {
                $this->info(__('admin/command.captcha.cancelled'));

                return Command::SUCCESS;
            }
        }

        $success = CaptchaFailoverService::switchProvider($provider, $permanent);

        if ($success) {
            $this->info(__('admin/command.captcha.switched', [
                'provider' => $this->getProviderLabel($provider),
                'type' => $permanent ? __('admin/command.captcha.permanent') : __('admin/command.captcha.temporary'),
            ]));
        } else {
            $this->error(__('admin/command.captcha.switch_failed'));
        }

        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    protected function resetToDefault(): int
    {
        CaptchaFailoverService::resetToDefault();
        $this->info(__('admin/command.captcha.reset_success'));

        return Command::SUCCESS;
    }

    protected function listProviders(): int
    {
        $this->info(__('admin/command.captcha.available_providers'));
        $this->newLine();

        foreach (CaptchaProvider::cases() as $provider) {
            $this->line("  - {$provider->value}: {$provider->label()}");
        }

        return Command::SUCCESS;
    }

    protected function showHelp(): int
    {
        // 自動フェイルオーバー設定
        if ($this->option('auto-failover') !== null) {
            $enabled = strtolower($this->option('auto-failover')) === 'on';
            CaptchaFailoverService::setAutoFailoverEnabled($enabled);
            $this->info(__('admin/command.captcha.auto_failover_set', [
                'status' => $enabled ? __('common.enabled') : __('common.disabled'),
            ]));

            return Command::SUCCESS;
        }

        $this->info(__('admin/command.captcha.usage'));
        $this->newLine();
        $this->line('  captcha status              '.__('admin/command.captcha.action_status'));
        $this->line('  captcha switch --provider=X '.__('admin/command.captcha.action_switch'));
        $this->line('  captcha switch --provider=X --permanent '.__('admin/command.captcha.action_switch_permanent'));
        $this->line('  captcha reset               '.__('admin/command.captcha.action_reset'));
        $this->line('  captcha providers           '.__('admin/command.captcha.action_providers'));
        $this->line('  captcha --auto-failover=on  '.__('admin/command.captcha.action_auto_on'));
        $this->line('  captcha --auto-failover=off '.__('admin/command.captcha.action_auto_off'));

        return Command::SUCCESS;
    }

    protected function getProviderLabel(string $provider): string
    {
        $enum = CaptchaProvider::tryFrom($provider);

        return $enum ? $enum->label() : $provider;
    }
}
