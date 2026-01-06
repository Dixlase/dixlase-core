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

namespace App\Console\Commands;

use App\Models\SecuritySetting;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * セキュリティ設定緊急リセットコマンド（ブレークグラス）
 * 
 * セキュリティ設定の破損・暴走時に、安全なデフォルト値にリセットする
 * - 不正な設定値でログイン不能
 * - 存在しないCAPTCHAドライバ指定
 * - 設定の循環参照
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
                            {--category= : Reset specific category only (auth, captcha, ip, lockdown)}
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
     * Reset to minimal safe configuration
     * Disables all security features to allow login
     */
    protected function resetMinimal(): int
    {
        $reason = $this->getRequiredReason();
        if (!$reason) {
            return self::FAILURE;
        }

        // Export current settings first
        $this->exportCurrentSettings();

        // Confirm action
        $this->warn(__('admin/command.security_reset.warning_minimal'));
        $this->newLine();
        $this->line(__('admin/command.security_reset.minimal_description'));
        $this->newLine();

        if (!$this->option('force') && !$this->confirm(__('admin/command.security_reset.confirm_minimal'))) {
            $this->info(__('admin/command.security_reset.cancelled'));
            return self::SUCCESS;
        }

        // Store current settings for audit
        $previousSettings = $this->getCurrentSecuritySettings();

        // Apply minimal safe settings
        $minimalSettings = $this->getMinimalSafeSettings();
        $this->applySettings($minimalSettings);

        // Clear all security-related caches
        $this->clearSecurityCaches();

        $this->info(__('admin/command.security_reset.minimal_success'));

        // Log to audit
        AuditService::log(
            action: 'security_emergency_reset_minimal',
            category: 'security',
            severity: 'critical',
            outcome: 'success',
            actorId: null,
            context: [
                'previous_settings_count' => count($previousSettings),
                'applied_settings' => array_keys($minimalSettings),
                'reason' => $reason,
                'triggered_by' => 'cli',
            ]
        );

        $this->warn(__('admin/command.security_reset.security_notice'));
        $this->line(__('admin/command.security_reset.restore_hint'));

        return self::SUCCESS;
    }

    /**
     * Reset to full default configuration
     */
    protected function resetFull(): int
    {
        $reason = $this->getRequiredReason();
        if (!$reason) {
            return self::FAILURE;
        }

        $category = $this->option('category');

        // Export current settings first
        $this->exportCurrentSettings();

        // Confirm action
        if ($category) {
            $this->warn(__('admin/command.security_reset.warning_category', ['category' => $category]));
        } else {
            $this->warn(__('admin/command.security_reset.warning_full'));
        }
        $this->newLine();

        if (!$this->option('force') && !$this->confirm(__('admin/command.security_reset.confirm_full'))) {
            $this->info(__('admin/command.security_reset.cancelled'));
            return self::SUCCESS;
        }

        // Store current settings for audit
        $previousSettings = $this->getCurrentSecuritySettings();

        // Apply default settings
        $defaultSettings = $category 
            ? $this->getDefaultSettingsForCategory($category)
            : $this->getFullDefaultSettings();
        
        $this->applySettings($defaultSettings);

        // Clear all security-related caches
        $this->clearSecurityCaches();

        $this->info(__('admin/command.security_reset.full_success', [
            'count' => count($defaultSettings),
        ]));

        // Log to audit
        AuditService::log(
            action: 'security_emergency_reset_full',
            category: 'security',
            severity: 'critical',
            outcome: 'success',
            actorId: null,
            context: [
                'category' => $category ?? 'all',
                'previous_settings_count' => count($previousSettings),
                'applied_settings_count' => count($defaultSettings),
                'reason' => $reason,
                'triggered_by' => 'cli',
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Show current security settings status
     */
    protected function showStatus(): int
    {
        $this->info(__('admin/command.security_reset.status_title'));
        $this->newLine();

        $settings = $this->getCurrentSecuritySettings();

        // Group by category
        $categories = [
            'auth' => ['two_fa_enabled', 'two_fa_mode', 'password_reset_enabled'],
            'captcha' => ['captcha_enabled', 'captcha_driver', 'captcha_authentication_result'],
            'ip' => ['ip_whitelist_enabled', 'ip_blacklist_enabled'],
            'lockdown' => ['lockdown_active', 'lockdown_type'],
            'login' => ['login_lockout_enabled', 'login_max_attempts'],
        ];

        foreach ($categories as $category => $keys) {
            $this->info("【" . strtoupper($category) . "】");
            
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
     * Export current settings to file
     */
    protected function exportSettings(): int
    {
        $path = $this->option('export-path') ?? storage_path('app/security_settings_backup_' . date('Y-m-d_His') . '.json');
        
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
     * Get current security settings
     */
    protected function getCurrentSecuritySettings(): array
    {
        $settings = [];
        
        $keys = [
            'two_fa_enabled', 'two_fa_mode',
            'captcha_enabled', 'captcha_driver', 'captcha_site_key', 'captcha_authentication_result',
            'ip_whitelist_enabled', 'ip_blacklist_enabled',
            'lockdown_active', 'lockdown_type',
            'login_lockout_enabled', 'login_max_attempts', 'login_lockout_duration',
            'password_reset_enabled',
        ];

        foreach ($keys as $key) {
            $settings[$key] = SecuritySetting::get($key);
        }

        return $settings;
    }

    /**
     * Get minimal safe settings (disable everything)
     */
    protected function getMinimalSafeSettings(): array
    {
        return [
            'captcha_enabled' => false,
            'captcha_authentication_result' => false,
            'ip_whitelist_enabled' => false,
            'ip_blacklist_enabled' => false,
            'lockdown_active' => false,
            'login_lockout_enabled' => false,
        ];
    }

    /**
     * Get full default settings
     */
    protected function getFullDefaultSettings(): array
    {
        return [
            'two_fa_enabled' => false,
            'two_fa_mode' => 'disabled',
            'captcha_enabled' => false,
            'captcha_driver' => 'google',
            'captcha_authentication_result' => false,
            'ip_whitelist_enabled' => false,
            'ip_blacklist_enabled' => false,
            'lockdown_active' => false,
            'lockdown_type' => null,
            'login_lockout_enabled' => true,
            'login_max_attempts' => 5,
            'login_lockout_duration' => 15,
            'password_reset_enabled' => true,
        ];
    }

    /**
     * Get default settings for a specific category
     */
    protected function getDefaultSettingsForCategory(string $category): array
    {
        return match ($category) {
            'auth' => [
                'two_fa_enabled' => false,
                'two_fa_mode' => 'disabled',
            ],
            'captcha' => [
                'captcha_enabled' => false,
                'captcha_driver' => 'google',
                'captcha_authentication_result' => false,
            ],
            'ip' => [
                'ip_whitelist_enabled' => false,
                'ip_blacklist_enabled' => false,
            ],
            'lockdown' => [
                'lockdown_active' => false,
                'lockdown_type' => null,
            ],
            'login' => [
                'login_lockout_enabled' => true,
                'login_max_attempts' => 5,
                'login_lockout_duration' => 15,
            ],
            default => [],
        };
    }

    /**
     * Apply settings
     */
    protected function applySettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            SecuritySetting::set($key, $value);
        }
    }

    /**
     * Clear security-related caches
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
            'security_settings_*',
        ];

        foreach ($cacheKeys as $key) {
            if (str_contains($key, '*')) {
                // Pattern-based cache clear would need custom implementation
                continue;
            }
            Cache::forget($key);
        }

        $this->line(__('admin/command.security_reset.cache_cleared'));
    }

    /**
     * Export current settings before reset
     */
    protected function exportCurrentSettings(): void
    {
        $path = storage_path('app/security_settings_pre_reset_' . date('Y-m-d_His') . '.json');
        
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
     * Get required reason
     */
    protected function getRequiredReason(): ?string
    {
        $reason = $this->option('reason');

        if (!$reason) {
            $reason = $this->ask(__('admin/command.security_reset.reason_prompt'));
        }

        if (!$reason) {
            $this->error(__('admin/command.security_reset.reason_required'));
            return null;
        }

        return $reason;
    }

    /**
     * Handle invalid action
     */
    protected function invalidAction(string $action): int
    {
        $this->error(__('admin/command.security_reset.invalid_action', ['action' => $action]));
        $this->line(__('admin/command.security_reset.valid_actions'));
        $this->line('  - minimal : ' . __('admin/command.security_reset.action_minimal'));
        $this->line('  - full    : ' . __('admin/command.security_reset.action_full'));
        $this->line('  - status  : ' . __('admin/command.security_reset.action_status'));
        $this->line('  - export  : ' . __('admin/command.security_reset.action_export'));

        return self::FAILURE;
    }
}
