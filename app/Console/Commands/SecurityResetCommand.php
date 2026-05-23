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

use App\Models\SecuritySetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Security settings emergency reset command (break glass)
 *
 * Reset to safe default values when security settings are corrupted or
 * malfunctioning (login impossible due to invalid setting values, non-existent
 * CAPTCHA driver specified, circular references, etc).
 *
 * Scope: keys registered in the security settings registry. Lockdown is a
 * separate subsystem (see `php artisan lockdown ...`) and is not touched here.
 */
class SecurityResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:reset
                            {action=status : Action to perform (minimal, full, status, export)}
                            {--category= : Reset specific category only (auth, captcha, ip, login)}
                            {--reason= : Reason for reset (required)}
                            {--force : Skip confirmation}
                            {--export-path= : Path to export current settings before reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emergency security settings reset (break-glass)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'minimal' => $this->resetMinimal(),
            'full' => $this->resetFull(),
            'status' => $this->showStatus(),
            'export' => $this->exportSettings(),
            default => $this->invalidAction($action),
        };
    }

    /**
     * Reset to minimal safe configuration. Disables all gating security features
     * so an operator can log back in.
     */
    protected function resetMinimal(): int
    {
        $reason = $this->getRequiredReason();
        if (! $reason) {
            return self::FAILURE;
        }

        $this->exportCurrentSettings();

        $this->warn(__('admin/command.security_reset.warning_minimal'));
        $this->newLine();
        $this->line(__('admin/command.security_reset.minimal_description'));
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm(__('admin/command.security_reset.confirm_minimal'))) {
            $this->info(__('admin/command.security_reset.cancelled'));

            return self::SUCCESS;
        }

        $previousSettings = $this->getCurrentSecuritySettings();

        $minimalSettings = $this->getMinimalSafeSettings();
        $this->applySettings($minimalSettings);

        $this->clearSecurityCaches();

        $this->info(__('admin/command.security_reset.minimal_success'));

        \App\Facades\Audit::logSecurity('security_emergency_reset_minimal', [
            'severity' => 'critical',
            'outcome' => 'success',
            'context' => [
                'previous_settings_count' => count($previousSettings),
                'applied_settings' => array_keys($minimalSettings),
                'reason' => $reason,
                'triggered_by' => 'cli',
            ],
        ]);

        $this->warn(__('admin/command.security_reset.security_notice'));
        $this->line(__('admin/command.security_reset.restore_hint'));

        return self::SUCCESS;
    }

    /**
     * Reset to full default configuration (optionally limited to one category).
     */
    protected function resetFull(): int
    {
        $reason = $this->getRequiredReason();
        if (! $reason) {
            return self::FAILURE;
        }

        $category = $this->option('category');

        $this->exportCurrentSettings();

        if ($category) {
            $this->warn(__('admin/command.security_reset.warning_category', ['category' => $category]));
        } else {
            $this->warn(__('admin/command.security_reset.warning_full'));
        }
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm(__('admin/command.security_reset.confirm_full'))) {
            $this->info(__('admin/command.security_reset.cancelled'));

            return self::SUCCESS;
        }

        $previousSettings = $this->getCurrentSecuritySettings();

        $defaultSettings = $category
            ? $this->getDefaultSettingsForCategory($category)
            : $this->getFullDefaultSettings();

        $this->applySettings($defaultSettings);

        $this->clearSecurityCaches();

        $this->info(__('admin/command.security_reset.full_success', [
            'count' => count($defaultSettings),
        ]));

        \App\Facades\Audit::logSecurity('security_emergency_reset_full', [
            'severity' => 'critical',
            'outcome' => 'success',
            'context' => [
                'category' => $category ?? 'all',
                'previous_settings_count' => count($previousSettings),
                'applied_settings_count' => count($defaultSettings),
                'reason' => $reason,
                'triggered_by' => 'cli',
            ],
        ]);

        return self::SUCCESS;
    }

    /**
     * Show current security settings status grouped by category.
     */
    protected function showStatus(): int
    {
        $this->info(__('admin/command.security_reset.status_title'));
        $this->newLine();

        $settings = $this->getCurrentSecuritySettings();

        $categories = [
            'auth' => ['two_fa_mode', 'password_reset_enabled'],
            'captcha' => ['captcha_enabled', 'captcha_driver', 'captcha_authentication_result'],
            'ip' => [
                'enable_allowed_admin_ips',
                'enable_blocked_admin_ips',
                'enable_allowed_front_ips',
                'enable_blocked_front_ips',
            ],
            'login' => ['login_attempt_limit_enabled', 'login_attempt_max_attempts'],
        ];

        foreach ($categories as $category => $keys) {
            $this->info('【'.strtoupper($category).'】');

            $rows = [];
            foreach ($keys as $key) {
                $value = $settings[$key] ?? '-';
                if (is_bool($value)) {
                    $value = $value ? '✅ Enabled' : '❌ Disabled';
                } elseif ($value === '1' || $value === 1) {
                    $value = '✅ Enabled';
                } elseif ($value === '0' || $value === 0) {
                    $value = '❌ Disabled';
                }
                $rows[] = [$key, $value];
            }

            $this->table(
                [__('admin/command.security_reset.setting'), __('admin/command.security_reset.value')],
                $rows
            );
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Export current settings to file.
     */
    protected function exportSettings(): int
    {
        $path = $this->option('export-path') ?? storage_path('app/security_settings_backup_'.date('Y-m-d_His').'.json');

        $settings = $this->getCurrentSecuritySettings();

        $export = [
            'exported_at' => now()->toIso8601String(),
            'settings' => $settings,
        ];

        file_put_contents($path, json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info(__('admin/command.security_reset.exported', ['path' => $path]));

        return self::SUCCESS;
    }

    /**
     * Get current security settings.
     *
     * @return array<string, mixed>
     */
    protected function getCurrentSecuritySettings(): array
    {
        $settings = [];

        $keys = [
            'two_fa_mode',
            'captcha_enabled', 'captcha_driver', 'captcha_site_key', 'captcha_authentication_result',
            'enable_allowed_admin_ips', 'enable_blocked_admin_ips',
            'enable_allowed_front_ips', 'enable_blocked_front_ips',
            'login_attempt_limit_enabled', 'login_attempt_max_attempts', 'login_attempt_lockout_duration',
            'password_reset_enabled',
        ];

        foreach ($keys as $key) {
            $settings[$key] = SecuritySetting::get($key);
        }

        return $settings;
    }

    /**
     * Get minimal safe settings (disable all gating security features so an
     * operator can recover access).
     *
     * @return array<string, mixed>
     */
    protected function getMinimalSafeSettings(): array
    {
        return [
            'captcha_enabled' => false,
            'captcha_authentication_result' => false,
            'enable_allowed_admin_ips' => false,
            'enable_blocked_admin_ips' => false,
            'enable_allowed_front_ips' => false,
            'enable_blocked_front_ips' => false,
            'login_attempt_limit_enabled' => false,
        ];
    }

    /**
     * Get full default settings.
     *
     * @return array<string, mixed>
     */
    protected function getFullDefaultSettings(): array
    {
        return [
            'two_fa_mode' => 'disabled',
            'captcha_enabled' => false,
            'captcha_driver' => 'google',
            'captcha_authentication_result' => false,
            'enable_allowed_admin_ips' => false,
            'enable_blocked_admin_ips' => false,
            'enable_allowed_front_ips' => false,
            'enable_blocked_front_ips' => false,
            'login_attempt_limit_enabled' => true,
            'login_attempt_max_attempts' => 5,
            'login_attempt_lockout_duration' => 15,
            'password_reset_enabled' => true,
        ];
    }

    /**
     * Get default settings for a specific category.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultSettingsForCategory(string $category): array
    {
        return match ($category) {
            'auth' => [
                'two_fa_mode' => 'disabled',
            ],
            'captcha' => [
                'captcha_enabled' => false,
                'captcha_driver' => 'google',
                'captcha_authentication_result' => false,
            ],
            'ip' => [
                'enable_allowed_admin_ips' => false,
                'enable_blocked_admin_ips' => false,
                'enable_allowed_front_ips' => false,
                'enable_blocked_front_ips' => false,
            ],
            'login' => [
                'login_attempt_limit_enabled' => true,
                'login_attempt_max_attempts' => 5,
                'login_attempt_lockout_duration' => 15,
            ],
            default => [],
        };
    }

    /**
     * Apply settings.
     *
     * @param  array<string, mixed>  $settings
     */
    protected function applySettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            SecuritySetting::set($key, $value);
        }
    }

    /**
     * Clear security-related caches.
     */
    protected function clearSecurityCaches(): void
    {
        $cacheKeys = [
            'captcha_bypass',
            'captcha_bypass_data',
            'mail_bypass',
            'mail_bypass_data',
            'captcha_failure_count',
            'captcha_active_provider',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        $this->line(__('admin/command.security_reset.cache_cleared'));
    }

    /**
     * Export current settings to a timestamped backup file before reset.
     */
    protected function exportCurrentSettings(): void
    {
        $path = storage_path('app/security_settings_pre_reset_'.date('Y-m-d_His').'.json');

        $settings = $this->getCurrentSecuritySettings();

        $export = [
            'exported_at' => now()->toIso8601String(),
            'reason' => 'pre_reset_backup',
            'settings' => $settings,
        ];

        file_put_contents($path, json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info(__('admin/command.security_reset.backup_created', ['path' => $path]));
    }

    /**
     * Get required reason for the recovery action.
     */
    protected function getRequiredReason(): ?string
    {
        $reason = $this->option('reason');

        if (! $reason) {
            $reason = $this->ask(__('admin/command.security_reset.reason_prompt'));
        }

        if (! $reason) {
            $this->error(__('admin/command.security_reset.reason_required'));

            return null;
        }

        return $reason;
    }

    /**
     * Handle invalid action.
     */
    protected function invalidAction(string $action): int
    {
        $this->error(__('admin/command.security_reset.invalid_action', ['action' => $action]));
        $this->line(__('admin/command.security_reset.valid_actions'));
        $this->line('  - minimal : '.__('admin/command.security_reset.action_minimal'));
        $this->line('  - full    : '.__('admin/command.security_reset.action_full'));
        $this->line('  - status  : '.__('admin/command.security_reset.action_status'));
        $this->line('  - export  : '.__('admin/command.security_reset.action_export'));

        return self::FAILURE;
    }
}
