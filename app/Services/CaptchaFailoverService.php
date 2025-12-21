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

namespace App\Services;

use App\Enums\CaptchaProvider;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CAPTCHAフェイルオーバーサービス
 * 
 * 複数のCAPTCHAサービスを管理し、障害時に自動/手動で切り替え可能にする
 */
class CaptchaFailoverService
{
    /**
     * キャッシュキー
     */
    private const CACHE_KEY_FAILURE_COUNT = 'captcha_failure_count';
    private const CACHE_KEY_LAST_FAILURE = 'captcha_last_failure';
    private const CACHE_KEY_ACTIVE_PROVIDER = 'captcha_active_provider';

    /**
     * フェイルオーバー設定
     */
    private const FAILURE_THRESHOLD = 3;  // この回数連続失敗でフェイルオーバー
    private const FAILURE_WINDOW_MINUTES = 5;  // 失敗カウントのウィンドウ
    private const COOLDOWN_MINUTES = 30;  // フェイルオーバー後のクールダウン

    /**
     * 現在アクティブなプロバイダーを取得
     */
    public static function getActiveProvider(): string
    {
        // キャッシュに一時的なアクティブプロバイダーがあればそれを使用
        $cachedProvider = Cache::get(self::CACHE_KEY_ACTIVE_PROVIDER);
        if ($cachedProvider) {
            return $cachedProvider;
        }

        // デフォルトは設定されたプライマリプロバイダー
        return SecuritySetting::get('captcha_driver', CaptchaProvider::GOOGLE->value);
    }

    /**
     * プロバイダーごとの設定を取得
     */
    public static function getProviderConfig(string $provider): array
    {
        $prefix = self::getProviderPrefix($provider);
        
        return [
            'site_key' => SecuritySetting::get("{$prefix}_site_key", ''),
            'secret_key' => SecuritySetting::get("{$prefix}_secret_key", ''),
            'enabled' => filter_var(
                SecuritySetting::get("{$prefix}_enabled", false),
                FILTER_VALIDATE_BOOLEAN
            ),
            'verified' => filter_var(
                SecuritySetting::get("{$prefix}_verified", false),
                FILTER_VALIDATE_BOOLEAN
            ),
        ];
    }

    /**
     * プロバイダーの設定を保存
     */
    public static function saveProviderConfig(string $provider, array $config): void
    {
        $prefix = self::getProviderPrefix($provider);

        if (isset($config['site_key'])) {
            SecuritySetting::set("{$prefix}_site_key", $config['site_key']);
        }
        if (isset($config['secret_key'])) {
            SecuritySetting::set("{$prefix}_secret_key", $config['secret_key']);
        }
        if (isset($config['enabled'])) {
            SecuritySetting::set("{$prefix}_enabled", $config['enabled'] ? '1' : '0');
        }
        if (isset($config['verified'])) {
            SecuritySetting::set("{$prefix}_verified", $config['verified'] ? '1' : '0');
        }

        // Google Enterprise固有の設定
        if ($provider === CaptchaProvider::GOOGLE_ENTERPRISE->value) {
            if (isset($config['project_id'])) {
                SecuritySetting::set("{$prefix}_project_id", $config['project_id']);
            }
            if (isset($config['min_score'])) {
                SecuritySetting::set("{$prefix}_min_score", (string) $config['min_score']);
            }
        }

        // Google reCAPTCHA固有の設定
        if ($provider === CaptchaProvider::GOOGLE->value) {
            if (isset($config['version'])) {
                SecuritySetting::set("{$prefix}_version", $config['version']);
            }
            if (isset($config['min_score'])) {
                SecuritySetting::set("{$prefix}_min_score", (string) $config['min_score']);
            }
        }
    }

    /**
     * 設定済みで有効なプロバイダー一覧を取得
     */
    public static function getConfiguredProviders(): array
    {
        $providers = [];

        foreach (CaptchaProvider::cases() as $provider) {
            $config = self::getProviderConfig($provider->value);
            if (!empty($config['site_key']) && !empty($config['secret_key'])) {
                $providers[$provider->value] = [
                    'provider' => $provider,
                    'config' => $config,
                    'label' => $provider->label(),
                ];
            }
        }

        return $providers;
    }

    /**
     * フェイルオーバー可能なプロバイダーを取得
     */
    public static function getFailoverProvider(): ?string
    {
        $currentProvider = self::getActiveProvider();
        $configuredProviders = self::getConfiguredProviders();

        // フェイルオーバー優先順位を取得
        $priority = self::getFailoverPriority();

        foreach ($priority as $provider) {
            if ($provider !== $currentProvider && isset($configuredProviders[$provider])) {
                $config = $configuredProviders[$provider]['config'];
                if ($config['enabled'] && $config['verified']) {
                    return $provider;
                }
            }
        }

        return null;
    }

    /**
     * フェイルオーバー優先順位を取得
     */
    public static function getFailoverPriority(): array
    {
        $priority = SecuritySetting::get('captcha_failover_priority', '');
        
        if (!empty($priority)) {
            return array_filter(explode(',', $priority));
        }

        // デフォルト優先順位
        return [
            CaptchaProvider::TURNSTILE->value,
            CaptchaProvider::GOOGLE_ENTERPRISE->value,
            CaptchaProvider::GOOGLE->value,
        ];
    }

    /**
     * フェイルオーバー優先順位を設定
     */
    public static function setFailoverPriority(array $priority): void
    {
        SecuritySetting::set('captcha_failover_priority', implode(',', $priority));
    }

    /**
     * 検証失敗を記録
     */
    public static function recordFailure(string $provider, string $errorType): void
    {
        $cacheKey = self::CACHE_KEY_FAILURE_COUNT . ":{$provider}";
        $count = Cache::get($cacheKey, 0) + 1;
        
        Cache::put($cacheKey, $count, now()->addMinutes(self::FAILURE_WINDOW_MINUTES));
        Cache::put(self::CACHE_KEY_LAST_FAILURE . ":{$provider}", [
            'time' => now()->toIso8601String(),
            'error' => $errorType,
        ], now()->addMinutes(self::FAILURE_WINDOW_MINUTES));

        Log::warning('CAPTCHA verification failure recorded', [
            'provider' => $provider,
            'error_type' => $errorType,
            'failure_count' => $count,
            'threshold' => self::FAILURE_THRESHOLD,
        ]);

        // 自動フェイルオーバーが有効で閾値を超えた場合
        if (self::isAutoFailoverEnabled() && $count >= self::FAILURE_THRESHOLD) {
            self::triggerAutoFailover($provider);
        }
    }

    /**
     * 検証成功を記録（失敗カウントをリセット）
     */
    public static function recordSuccess(string $provider): void
    {
        $cacheKey = self::CACHE_KEY_FAILURE_COUNT . ":{$provider}";
        Cache::forget($cacheKey);
    }

    /**
     * 自動フェイルオーバーを実行
     */
    protected static function triggerAutoFailover(string $failedProvider): void
    {
        $failoverProvider = self::getFailoverProvider();

        if (!$failoverProvider) {
            Log::error('CAPTCHA auto-failover failed: no available failover provider', [
                'failed_provider' => $failedProvider,
            ]);
            return;
        }

        // 一時的にアクティブプロバイダーを切り替え
        Cache::put(
            self::CACHE_KEY_ACTIVE_PROVIDER,
            $failoverProvider,
            now()->addMinutes(self::COOLDOWN_MINUTES)
        );

        Log::warning('CAPTCHA auto-failover triggered', [
            'from' => $failedProvider,
            'to' => $failoverProvider,
            'cooldown_minutes' => self::COOLDOWN_MINUTES,
        ]);

        // 監査ログに記録
        if (class_exists(AuditService::class)) {
            AuditService::log(
                action: 'captcha_auto_failover',
                category: 'security',
                severity: 'warning',
                outcome: 'success',
                context: [
                    'from_provider' => $failedProvider,
                    'to_provider' => $failoverProvider,
                ]
            );
        }

        // 管理者に通知
        self::notifyAdminOfFailover($failedProvider, $failoverProvider);
    }

    /**
     * 手動でプロバイダーを切り替え
     */
    public static function switchProvider(string $provider, bool $permanent = false): bool
    {
        $configuredProviders = self::getConfiguredProviders();

        if (!isset($configuredProviders[$provider])) {
            Log::error('CAPTCHA switch failed: provider not configured', [
                'provider' => $provider,
            ]);
            return false;
        }

        $config = $configuredProviders[$provider]['config'];
        if (!$config['enabled'] || !$config['verified']) {
            Log::error('CAPTCHA switch failed: provider not enabled or verified', [
                'provider' => $provider,
                'enabled' => $config['enabled'],
                'verified' => $config['verified'],
            ]);
            return false;
        }

        if ($permanent) {
            // 永続的な切り替え（プライマリプロバイダーを変更）
            SecuritySetting::set('captcha_driver', $provider);
            Cache::forget(self::CACHE_KEY_ACTIVE_PROVIDER);

            // 互換性のため、統一キーも更新
            SecuritySetting::set('captcha_site_key', $config['site_key']);
            SecuritySetting::set('captcha_secret_key', $config['secret_key']);
        } else {
            // 一時的な切り替え
            Cache::put(
                self::CACHE_KEY_ACTIVE_PROVIDER,
                $provider,
                now()->addMinutes(self::COOLDOWN_MINUTES)
            );
        }

        Log::info('CAPTCHA provider switched', [
            'to' => $provider,
            'permanent' => $permanent,
        ]);

        // 監査ログに記録
        if (class_exists(AuditService::class)) {
            AuditService::log(
                action: 'captcha_provider_switched',
                category: 'security',
                severity: 'info',
                outcome: 'success',
                context: [
                    'to_provider' => $provider,
                    'permanent' => $permanent,
                ]
            );
        }

        return true;
    }

    /**
     * 一時的な切り替えをリセット（プライマリに戻す）
     */
    public static function resetToDefault(): void
    {
        Cache::forget(self::CACHE_KEY_ACTIVE_PROVIDER);
        
        // 全プロバイダーの失敗カウントをリセット
        foreach (CaptchaProvider::cases() as $provider) {
            Cache::forget(self::CACHE_KEY_FAILURE_COUNT . ":{$provider->value}");
            Cache::forget(self::CACHE_KEY_LAST_FAILURE . ":{$provider->value}");
        }

        Log::info('CAPTCHA provider reset to default');
    }

    /**
     * 自動フェイルオーバーが有効かどうか
     */
    public static function isAutoFailoverEnabled(): bool
    {
        return filter_var(
            SecuritySetting::get('captcha_auto_failover_enabled', true),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * 自動フェイルオーバーの有効/無効を設定
     */
    public static function setAutoFailoverEnabled(bool $enabled): void
    {
        SecuritySetting::set('captcha_auto_failover_enabled', $enabled ? '1' : '0');
    }

    /**
     * 現在のステータスを取得
     */
    public static function getStatus(): array
    {
        $activeProvider = self::getActiveProvider();
        $primaryProvider = SecuritySetting::get('captcha_driver', CaptchaProvider::GOOGLE->value);
        $isFailedOver = $activeProvider !== $primaryProvider;

        $status = [
            'primary_provider' => $primaryProvider,
            'active_provider' => $activeProvider,
            'is_failed_over' => $isFailedOver,
            'auto_failover_enabled' => self::isAutoFailoverEnabled(),
            'configured_providers' => [],
            'failover_priority' => self::getFailoverPriority(),
        ];

        foreach (CaptchaProvider::cases() as $provider) {
            $config = self::getProviderConfig($provider->value);
            $failureCount = Cache::get(self::CACHE_KEY_FAILURE_COUNT . ":{$provider->value}", 0);
            $lastFailure = Cache::get(self::CACHE_KEY_LAST_FAILURE . ":{$provider->value}");

            $status['configured_providers'][$provider->value] = [
                'label' => $provider->label(),
                'configured' => !empty($config['site_key']) && !empty($config['secret_key']),
                'enabled' => $config['enabled'],
                'verified' => $config['verified'],
                'failure_count' => $failureCount,
                'last_failure' => $lastFailure,
                'is_active' => $provider->value === $activeProvider,
                'is_primary' => $provider->value === $primaryProvider,
            ];
        }

        return $status;
    }

    /**
     * プロバイダーのプレフィックスを取得
     */
    private static function getProviderPrefix(string $provider): string
    {
        return match ($provider) {
            CaptchaProvider::GOOGLE->value => 'captcha_google',
            CaptchaProvider::GOOGLE_ENTERPRISE->value => 'captcha_google_enterprise',
            CaptchaProvider::TURNSTILE->value => 'captcha_turnstile',
            default => 'captcha_' . $provider,
        };
    }

    /**
     * フェイルオーバー発生時に管理者へ通知
     */
    protected static function notifyAdminOfFailover(string $failedProvider, string $newProvider): void
    {
        try {
            if (class_exists(\App\Services\SystemNotificationService::class)) {
                $failedLabel = CaptchaProvider::tryFrom($failedProvider)?->label() ?? $failedProvider;
                $newLabel = CaptchaProvider::tryFrom($newProvider)?->label() ?? $newProvider;

                \App\Services\SystemNotificationService::send(
                    __('security.captcha_failover_subject'),
                    __('security.captcha_failover_message', [
                        'from' => $failedLabel,
                        'to' => $newLabel,
                        'time' => now()->format('Y-m-d H:i:s'),
                    ]),
                    'warning'
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send CAPTCHA failover notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
