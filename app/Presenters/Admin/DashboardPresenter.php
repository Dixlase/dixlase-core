<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Presenters\Admin;

use App\Contracts\PluginIntegration\DashboardNotificationProviderInterface;
use App\Contracts\PluginIntegration\DashboardWidgetProviderInterface;
use App\DTO\Mail\MailConfigDTO;
use App\DTO\PluginIntegration\DashboardNotificationDTO;
use App\DTO\PluginIntegration\DashboardWidgetDTO;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\PluginHealthStatus;
use App\Enums\TwoFaMethod;
use App\Helpers\ConfigHelper;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\PluginAudit;
use App\Models\Theme;
use App\Services\Plugin\PluginServiceResolver;
use App\Services\SafeModeService;
use Illuminate\Support\Carbon;

/**
 * ダッシュボード表示データのPresenter
 *
 * セキュリティ概要・メール状態・CAPTCHA状態・システム情報・
 * プラグインウィジェットをビュー向けの配列として生成します。
 */
class DashboardPresenter
{
    /**
     * セキュリティ概要を取得
     *
     * @return array<int, array{key: string, status: string, icon: string, label: string, description: string}>
     */
    public static function securityOverview(Member $user): array
    {
        $items = [];

        // メンテナンスモード
        $maintenanceActive = ConfigHelper::getMaintenanceMode();
        $items[] = [
            'key' => 'maintenance_mode',
            'status' => $maintenanceActive ? 'warning' : 'ok',
            'icon' => 'fas fa-tools',
            'label' => __('admin/dashboard.maintenance_mode'),
            'description' => $maintenanceActive
                ? __('admin/dashboard.maintenance_mode_active')
                : __('admin/dashboard.maintenance_mode_inactive'),
        ];

        // セーフモード
        $safeModeService = app(SafeModeService::class);
        $safeModeActive = $safeModeService->hasAnyActive();
        $items[] = [
            'key' => 'safe_mode',
            'status' => $safeModeActive ? 'warning' : 'ok',
            'icon' => 'fas fa-shield-alt',
            'label' => __('admin/dashboard.safe_mode'),
            'description' => $safeModeActive
                ? __('admin/dashboard.safe_mode_active')
                : __('admin/dashboard.safe_mode_inactive'),
        ];

        // CSPモード不整合（本番環境 + 開発用CSP）
        $isProduction = app()->environment('production');
        $cspMode = config('csp.mode', 'development');
        $cspMismatch = $isProduction && $cspMode === 'development';
        $items[] = [
            'key' => 'csp_mode',
            'status' => $cspMismatch ? 'warning' : 'ok',
            'icon' => 'fas fa-lock',
            'label' => __('admin/dashboard.csp_mode'),
            'description' => $cspMismatch
                ? __('admin/dashboard.csp_development_warning')
                : __('admin/dashboard.csp_mode_ok'),
        ];

        // デバッグモード（本番環境）
        $debugInProduction = $isProduction && config('app.debug');
        $items[] = [
            'key' => 'debug_mode',
            'status' => $debugInProduction ? 'warning' : 'ok',
            'icon' => 'fas fa-bug',
            'label' => __('admin/dashboard.debug_mode'),
            'description' => $debugInProduction
                ? __('admin/dashboard.debug_mode_warning')
                : __('admin/dashboard.debug_mode_ok'),
        ];

        // 2FA状態
        $twoFaMethod = $user->two_fa_method !== null
            ? TwoFaMethod::from($user->two_fa_method)
            : null;
        $twoFaEnabled = $twoFaMethod !== null;
        $twoFaRecommended = $twoFaMethod?->isRecommended() ?? false;

        $items[] = [
            'key' => 'two_fa',
            'status' => $twoFaEnabled ? ($twoFaRecommended ? 'ok' : 'recommendation') : 'recommendation',
            'icon' => 'fas fa-user-shield',
            'label' => __('admin/dashboard.two_fa_status'),
            'description' => $twoFaEnabled
                ? __('admin/dashboard.two_fa_enabled', ['method' => $twoFaMethod->label()])
                : __('admin/dashboard.two_fa_disabled'),
        ];

        return $items;
    }

    /**
     * メールサーバー状態を取得
     *
     * @return array{status: string, icon: string, label: string, description: string, mailer: string}
     */
    public static function mailServerStatus(): array
    {
        $mailConfig = MailConfigDTO::fromSystemConfig();
        $mailer = $mailConfig->mailer;

        // log / array ドライバーは警告
        if (in_array($mailer, ['log', 'array'], true)) {
            return [
                'status' => 'warning',
                'icon' => 'fas fa-envelope',
                'label' => __('admin/dashboard.mail_status'),
                'description' => __('admin/dashboard.mail_using_log_driver', ['driver' => $mailer]),
                'mailer' => $mailer,
            ];
        }

        // SMTP設定が不完全
        if (! $mailConfig->isValid()) {
            return [
                'status' => 'warning',
                'icon' => 'fas fa-envelope',
                'label' => __('admin/dashboard.mail_status'),
                'description' => __('admin/dashboard.mail_not_configured'),
                'mailer' => $mailer,
            ];
        }

        return [
            'status' => 'ok',
            'icon' => 'fas fa-envelope',
            'label' => __('admin/dashboard.mail_status'),
            'description' => __('admin/dashboard.mail_configured'),
            'mailer' => $mailer,
        ];
    }

    /**
     * CAPTCHA状態を取得
     *
     * @return array{status: string, icon: string, label: string, description: string}
     */
    public static function captchaStatus(): array
    {
        $driver = config('captcha.default', 'google');
        $siteKey = config("captcha.drivers.{$driver}.site_key", '');
        $secretKey = config("captcha.drivers.{$driver}.secret_key", '');

        $isConfigured = ! empty($siteKey) && ! empty($secretKey);

        return [
            'status' => $isConfigured ? 'ok' : 'recommendation',
            'icon' => 'fas fa-robot',
            'label' => __('admin/dashboard.captcha_status'),
            'description' => $isConfigured
                ? __('admin/dashboard.captcha_configured')
                : __('admin/dashboard.captcha_not_configured'),
        ];
    }

    /**
     * システム情報を取得
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function systemInfo(): array
    {
        return [
            [
                'label' => __('admin/dashboard.php_version'),
                'value' => PHP_VERSION,
            ],
            [
                'label' => __('admin/dashboard.laravel_version'),
                'value' => app()->version(),
            ],
            [
                'label' => __('admin/dashboard.dixlase_version'),
                'value' => config('app.version', '1.0.0'),
            ],
        ];
    }

    /**
     * プラグインウィジェットを取得
     *
     * @return DashboardWidgetDTO[]
     */
    public static function pluginWidgets(): array
    {
        $resolver = app(PluginServiceResolver::class);
        $results = $resolver->resolveAll(DashboardWidgetProviderInterface::class);

        $widgets = [];
        foreach ($results as $result) {
            if ($result->isResolved() && $result->instance instanceof DashboardWidgetProviderInterface) {
                foreach ($result->instance->getWidgets() as $widget) {
                    $widgets[] = $widget;
                }
            }
        }

        return $widgets;
    }

    /**
     * プラグイン通知を取得
     *
     * @return DashboardNotificationDTO[]
     */
    public static function pluginNotifications(): array
    {
        $resolver = app(PluginServiceResolver::class);
        $results = $resolver->resolveAll(DashboardNotificationProviderInterface::class);

        $notifications = [];
        foreach ($results as $result) {
            if ($result->isResolved() && $result->instance instanceof DashboardNotificationProviderInterface) {
                foreach ($result->instance->getNotifications() as $notification) {
                    $notifications[] = $notification;
                }
            }
        }

        return $notifications;
    }

    /**
     * 拡張機能（プラグイン/テーマ）の概要を取得
     *
     * @return array{
     *   plugins: array{installed: int, enabled: int},
     *   themes: array{installed: int, enabled: int},
     *   health: array<string, array{count: int, label: string, color: string, icon: string}>
     * }
     */
    public static function extensionOverview(): array
    {
        // プラグイン数
        $pluginsInstalled = Plugin::query()->installed()->count();
        $pluginsEnabled = Plugin::query()->enabled()->count();

        // テーマ数
        $themesInstalled = Theme::query()->installed()->count();
        $themesEnabled = Theme::query()->installed()->get()->filter(fn (Theme $t) => $t->isEnabled())->count();

        // 健全性ステータス別カウント（監査済みプラグインのみ）
        $healthCounts = [];
        foreach (PluginHealthStatus::cases() as $status) {
            $count = PluginAudit::query()
                ->where('health_status', $status->value)
                ->count();

            $healthCounts[$status->value] = [
                'count' => $count,
                'label' => $status->label(),
                'color' => $status->colorName(),
                'icon' => $status->iconClass(),
            ];
        }

        return [
            'plugins' => [
                'installed' => $pluginsInstalled,
                'enabled' => $pluginsEnabled,
            ],
            'themes' => [
                'installed' => $themesInstalled,
                'enabled' => $themesEnabled,
            ],
            'health' => $healthCounts,
        ];
    }

    /**
     * メンバー概要を取得
     *
     * @return array{
     *   total: int,
     *   active: int,
     *   inactive: int,
     *   by_role: array<string, array{count: int, label: string}>,
     *   two_fa_enabled: int,
     *   two_fa_rate: float,
     *   recent_logins: array<int, array{display_name: string, role: string, role_label: string, last_login_at: string}>
     * }
     */
    public static function memberOverview(): array
    {
        $total = Member::query()->count();
        $active = Member::query()->where('status', MemberStatus::Active->value)->count();
        $inactive = Member::query()->where('status', MemberStatus::Inactive->value)->count();

        // ロール分布
        $roleRows = Member::query()
            ->selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role');

        $byRole = [];
        foreach ($roleRows as $roleValue => $count) {
            $roleEnum = MemberRole::tryFrom((int) $roleValue);
            if ($roleEnum) {
                $byRole[$roleEnum->name] = [
                    'count' => $count,
                    'label' => $roleEnum->label(),
                ];
            }
        }

        // 2FA有効者数（two_fa_mode > 0 = 何らかの2FAが有効）
        $twoFaEnabled = Member::query()->where('two_fa_mode', '>', 0)->count();
        $twoFaRate = $total > 0 ? round(($twoFaEnabled / $total) * 100, 1) : 0.0;

        // 最近ログインした5名
        $recentLogins = Member::query()
            ->whereNotNull('last_login_at')
            ->latest('last_login_at')
            ->limit(5)
            ->get(['display_name', 'account_name', 'role', 'last_login_at'])
            ->map(function (Member $member) {
                $roleEnum = $member->role;

                return [
                    'display_name' => $member->display_name ?? $member->account_name,
                    'role' => $roleEnum->name ?? '',
                    'role_label' => $roleEnum->label() ?? '',
                    'last_login_at' => $member->last_login_at
                        ? Carbon::parse($member->last_login_at)->diffForHumans()
                        : '',
                ];
            })
            ->toArray();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'by_role' => $byRole,
            'two_fa_enabled' => $twoFaEnabled,
            'two_fa_rate' => $twoFaRate,
            'recent_logins' => $recentLogins,
        ];
    }
}
