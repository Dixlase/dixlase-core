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

namespace App\Presenters\Admin;

use App\Contracts\Plugin\SignatureVerifierInterface;
use App\Contracts\PluginIntegration\DashboardNotificationProviderInterface;
use App\Contracts\PluginIntegration\DashboardWidgetProviderInterface;
use App\DTO\PluginIntegration\DashboardNotificationDTO;
use App\DTO\PluginIntegration\DashboardWidgetDTO;
use App\Enums\AuthenticationMode;
use App\Enums\CspMode;
use App\Enums\ExtensionSecurityPreset;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\PluginHealthStatus;
use App\Helpers\CaptchaHelper;
use App\Helpers\ConfigHelper;
use App\Models\AuditLog;
use App\Models\CoreVersionHistory;
use App\Models\FileIntegrityAudit;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\PluginAudit;
use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use App\Models\Theme;
use App\Services\AuditLogIntegrityService;
use App\Services\Core\DependencyIntegrityService;
use App\Services\Plugin\PluginServiceResolver;
use App\Services\SafeModeService;
use App\Services\TwoFa\TwoFaStatusService;
use App\Support\HttpsEnforcement;
use Illuminate\Support\Carbon;

/**
 * Presenter for dashboard display data
 *
 * Site health, mail status, CAPTCHA status, system information,
 * Generate plugin widgets as an array for the view
 */
class DashboardPresenter
{
    /**
     * Get display class set according to status
     *
     * @return array{border_bg: string, icon_color: string, badge: string, badge_label: string}
     */
    private static function statusClasses(string $status): array
    {
        return match ($status) {
            'critical' => [
                'border_bg' => 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20',
                'icon_color' => 'text-red-600 dark:text-red-400',
                'badge' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'badge_label' => __('admin/dashboard.status_critical'),
            ],
            'warning' => [
                'border_bg' => 'border-yellow-300 dark:border-yellow-600 bg-yellow-50 dark:bg-yellow-900/20',
                'icon_color' => 'text-yellow-600 dark:text-yellow-400',
                'badge' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'badge_label' => __('admin/dashboard.status_warning'),
            ],
            'recommendation' => [
                'border_bg' => 'border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20',
                'icon_color' => 'text-amber-600 dark:text-amber-400',
                'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                'badge_label' => __('admin/dashboard.status_recommendation'),
            ],
            default => [
                'border_bg' => 'border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-900/20',
                'icon_color' => 'text-green-600 dark:text-green-400',
                'badge' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'badge_label' => __('admin/dashboard.status_ok'),
            ],
        };
    }

    /**
     * Assign display classes to site health items
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function decorateSiteHealthItems(array $items, bool $isAdvancedMode): array
    {
        return array_map(function (array $item) use ($isAdvancedMode) {
            $classes = self::statusClasses($item['status'] ?? 'ok');
            $requiresAdvanced = (bool) ($item['requires_advanced_mode'] ?? false);
            $isClickable = ! empty($item['url']) && (! $requiresAdvanced || $isAdvancedMode);

            return array_merge($item, [
                'border_bg_class' => $classes['border_bg'],
                'icon_color_class' => $classes['icon_color'],
                'badge_class' => $classes['badge'],
                'badge_label' => $classes['badge_label'],
                'is_clickable' => $isClickable,
            ]);
        }, $items);
    }

    /**
     * Get site health (various operational status checks)
     *
     * @return array<int, array{key: string, status: string, icon: string, label: string, description: string, url: string|null, requires_advanced_mode: bool}>
     */
    public static function siteHealth(Member $user): array
    {
        $items = [];
        $isProduction = app()->environment('production');

        // Maintenance mode
        $maintenanceActive = ConfigHelper::getMaintenanceMode();
        $items[] = [
            'key' => 'maintenance_mode',
            'status' => $maintenanceActive ? 'warning' : 'ok',
            'icon' => 'fas fa-tools',
            'label' => __('admin/dashboard.maintenance_mode'),
            'description' => $maintenanceActive
                ? __('admin/dashboard.maintenance_mode_active')
                : __('admin/dashboard.maintenance_mode_inactive'),
            'url' => route('admin.settings.base.maintenance'),
            'requires_advanced_mode' => false,
        ];

        // Safe mode
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
            'url' => null,
            'requires_advanced_mode' => false,
        ];

        // Environment settings (local / staging / production)
        $siteEnv = app()->environment();
        $envStatus = match ($siteEnv) {
            'local' => 'warning',
            default => 'ok',
        };
        $envDescKey = in_array($siteEnv, ['local', 'staging', 'production'], true)
            ? 'admin/dashboard.environment_'.$siteEnv
            : 'admin/dashboard.environment_other';
        $items[] = [
            'key' => 'environment',
            'status' => $envStatus,
            'icon' => 'fas fa-server',
            'label' => __('admin/dashboard.environment_settings'),
            'description' => __($envDescKey, ['env' => $siteEnv]),
            'url' => route('admin.settings.security.environment'),
            'requires_advanced_mode' => true,
        ];

        // HTTPS (force_ssl is saved in site_settings)
        $forceSsl = (bool) (int) SiteSetting::get('force_ssl', 0);
        $isCurrentSecure = request()->isSecure();
        if ($forceSsl && ! HttpsEnforcement::isForceable((string) config('app.url'))) {
            // The setting is on but deliberately not applied: APP_URL points at
            // a plain-HTTP listener on a non-standard port, so forcing the
            // scheme would make the site unreachable. Say so instead of
            // reporting HTTPS as enforced.
            $httpsStatus = 'warning';
            $httpsDescription = __('admin/dashboard.https_force_ssl_not_applied', [
                'url' => (string) config('app.url'),
            ]);
            $httpsIcon = 'fas fa-unlock';
        } elseif ($forceSsl) {
            $httpsStatus = 'ok';
            $httpsDescription = __('admin/dashboard.https_force_ssl_enabled');
            $httpsIcon = 'fas fa-lock';
        } elseif ($isProduction) {
            $httpsStatus = 'warning';
            $httpsDescription = __('admin/dashboard.https_production_no_force');
            $httpsIcon = 'fas fa-unlock';
        } elseif ($isCurrentSecure) {
            $httpsStatus = 'ok';
            $httpsDescription = __('admin/dashboard.https_current_secure');
            $httpsIcon = 'fas fa-lock';
        } else {
            $httpsStatus = 'recommendation';
            $httpsDescription = __('admin/dashboard.https_disabled');
            $httpsIcon = 'fas fa-unlock';
        }
        $items[] = [
            'key' => 'https',
            'status' => $httpsStatus,
            'icon' => $httpsIcon,
            'label' => __('admin/dashboard.https_status'),
            'description' => $httpsDescription,
            'url' => route('admin.settings.base.admin'),
            'requires_advanced_mode' => false,
        ];

        // CSP mode (disabled=critical, development=warning, standard or higher=ok)
        $cspEnabled = (bool) (int) SecuritySetting::get('csp_enabled', 1);
        $cspModeValue = (int) SecuritySetting::get('csp_mode', CspMode::default()->value);
        $cspMode = CspMode::fromValue($cspModeValue) ?? CspMode::default();
        if (! $cspEnabled) {
            $cspStatus = 'critical';
            $cspIcon = 'fas fa-lock-open';
            $cspDescription = __('admin/dashboard.csp_disabled');
        } elseif ($cspMode === CspMode::Development) {
            $cspStatus = 'warning';
            $cspIcon = 'fas fa-lock';
            $cspDescription = __('admin/dashboard.csp_development_warning');
        } else {
            $cspStatus = 'ok';
            $cspIcon = 'fas fa-lock';
            $cspDescription = __('admin/dashboard.csp_mode_ok');
        }
        $items[] = [
            'key' => 'csp_mode',
            'status' => $cspStatus,
            'icon' => $cspIcon,
            'label' => __('admin/dashboard.csp_mode'),
            'description' => $cspDescription,
            'url' => route('admin.settings.security.csp'),
            'requires_advanced_mode' => true,
        ];

        // Debug mode (enabled in production=warning, enabled in non-production=ok, disabled=ok)
        $debugEnabled = (bool) config('app.debug');
        if ($debugEnabled && $isProduction) {
            $debugStatus = 'warning';
            $debugDescription = __('admin/dashboard.debug_mode_warning');
        } elseif ($debugEnabled) {
            $debugStatus = 'ok';
            $debugDescription = __('admin/dashboard.debug_mode_dev_ok');
        } else {
            $debugStatus = 'ok';
            $debugDescription = __('admin/dashboard.debug_mode_ok');
        }
        $items[] = [
            'key' => 'debug_mode',
            'status' => $debugStatus,
            'icon' => 'fas fa-bug',
            'label' => __('admin/dashboard.debug_mode'),
            'description' => $debugDescription,
            'url' => route('admin.settings.security.environment'),
            'requires_advanced_mode' => true,
        ];

        // Extension security preset (Development=warning, others=ok)
        $presetValue = (string) SecuritySetting::get('extension_security_preset', ExtensionSecurityPreset::default()->value);
        $preset = ExtensionSecurityPreset::tryFrom($presetValue) ?? ExtensionSecurityPreset::default();
        $presetStatus = $preset === ExtensionSecurityPreset::Development ? 'warning' : 'ok';
        $items[] = [
            'key' => 'extension_mode',
            'status' => $presetStatus,
            'icon' => 'fas fa-toggle-on',
            'label' => __('admin/dashboard.extension_mode'),
            'description' => __('admin/dashboard.extension_mode_'.$preset->value),
            'url' => route('admin.settings.security.extensions'),
            'requires_advanced_mode' => true,
        ];

        // Public key (whether it can be retrieved from key management site)
        $signatureVerifier = app(SignatureVerifierInterface::class);
        $signatureAvailable = $signatureVerifier->isAvailable();
        $items[] = [
            'key' => 'public_key',
            'status' => $signatureAvailable ? 'ok' : 'recommendation',
            'icon' => 'fas fa-key',
            'label' => __('admin/dashboard.public_key_status'),
            'description' => $signatureAvailable
                ? __('admin/dashboard.public_key_available')
                : __('admin/dashboard.public_key_unavailable'),
            'url' => route('admin.settings.security.extensions'),
            'requires_advanced_mode' => true,
        ];

        // Error notification
        $notificationEnabled = (bool) SecuritySetting::get('notification_enabled', false);
        $items[] = [
            'key' => 'error_notification',
            'status' => $notificationEnabled ? 'ok' : 'recommendation',
            'icon' => 'fas fa-bell',
            'label' => __('admin/dashboard.error_notification_status'),
            'description' => $notificationEnabled
                ? __('admin/dashboard.error_notification_enabled')
                : __('admin/dashboard.error_notification_disabled'),
            'url' => route('admin.settings.security.notifications'),
            'requires_advanced_mode' => true,
        ];

        // File integrity
        $latestAudit = FileIntegrityAudit::getLatestCore();
        if ($latestAudit === null) {
            $integrityStatus = 'recommendation';
            $integrityDescription = __('admin/dashboard.file_integrity_no_baseline');
        } else {
            $integrityStatus = match ($latestAudit->status) {
                FileIntegrityAudit::STATUS_OK => 'ok',
                FileIntegrityAudit::STATUS_WARNING => 'warning',
                FileIntegrityAudit::STATUS_CRITICAL => 'critical',
                default => 'recommendation',
            };
            $integrityDescription = match ($latestAudit->status) {
                FileIntegrityAudit::STATUS_OK => __('admin/dashboard.file_integrity_ok'),
                FileIntegrityAudit::STATUS_WARNING => __('admin/dashboard.file_integrity_warning', ['count' => $latestAudit->changed_files_count]),
                FileIntegrityAudit::STATUS_CRITICAL => __('admin/dashboard.file_integrity_critical', ['count' => $latestAudit->suspicious_files_count]),
                default => __('admin/dashboard.file_integrity_no_baseline'),
            };
        }
        $items[] = [
            'key' => 'file_integrity',
            'status' => $integrityStatus,
            'icon' => 'fas fa-fingerprint',
            'label' => __('admin/dashboard.file_integrity_status'),
            'description' => $integrityDescription,
            'url' => route('admin.settings.security.integrity'),
            'requires_advanced_mode' => true,
        ];

        // Audit log integrity (hash chain + daily seals)
        $auditHealth = app(AuditLogIntegrityService::class)->getHealthCached();
        $auditStatus = match ($auditHealth['state']) {
            AuditLogIntegrityService::HEALTH_TAMPERED => 'critical',
            AuditLogIntegrityService::HEALTH_CHAIN_STALLED,
            AuditLogIntegrityService::HEALTH_SEAL_OVERDUE => 'warning',
            AuditLogIntegrityService::HEALTH_VERIFICATION_STALE => 'recommendation',
            default => 'ok',
        };
        $auditDescription = match ($auditHealth['state']) {
            AuditLogIntegrityService::HEALTH_TAMPERED => __('admin/dashboard.audit_integrity_tampered', [
                'records' => $auditHealth['tampered'],
                'seals' => $auditHealth['invalid_seals'],
            ]),
            AuditLogIntegrityService::HEALTH_CHAIN_STALLED => __('admin/dashboard.audit_integrity_chain_stalled', [
                'count' => $auditHealth['stalled'],
                'hours' => AuditLogIntegrityService::CHAIN_STALL_HOURS,
            ]),
            AuditLogIntegrityService::HEALTH_SEAL_OVERDUE => __('admin/dashboard.audit_integrity_seal_overdue', [
                'date' => $auditHealth['unsealed_date'],
            ]),
            AuditLogIntegrityService::HEALTH_VERIFICATION_STALE => __('admin/dashboard.audit_integrity_verify_recommended', [
                'days' => AuditLogIntegrityService::VERIFY_RECOMMENDED_DAYS,
            ]),
            AuditLogIntegrityService::HEALTH_EMPTY => __('admin/dashboard.audit_integrity_empty'),
            default => $auditHealth['last_verified_at'] !== null
                ? __('admin/dashboard.audit_integrity_ok', ['date' => $auditHealth['last_verified_at']->format('Y-m-d')])
                : __('admin/dashboard.audit_integrity_ok_unverified'),
        };
        $items[] = [
            'key' => 'audit_log_integrity',
            'status' => $auditStatus,
            'icon' => 'fas fa-link',
            'label' => __('admin/dashboard.audit_integrity_status'),
            'description' => $auditDescription,
            'url' => route('admin.settings.systems.logs.index'),
            'requires_advanced_mode' => true,
        ];

        // Dependency integrity (vendor/ vs composer.lock)
        //
        // A core update swaps the source tree and vendor/ in separate steps.
        // An interruption between them leaves new source with old
        // dependencies, and every other signal stays green: the front page
        // answers 200, the Laravel log is empty and the panel reports the new
        // version. Seen on the sandbox 2026-09-24. Critical rather than a
        // warning because the same interruption across a framework major
        // leaves a tree that fatals on the next boot.
        $depHealth = app(DependencyIntegrityService::class)->checkCached();
        $items[] = [
            'key' => 'dependency_integrity',
            'status' => match ($depHealth['state']) {
                DependencyIntegrityService::STATE_MISMATCHED => 'critical',
                DependencyIntegrityService::STATE_UNKNOWN => 'warning',
                default => 'ok',
            },
            'icon' => 'fas fa-cubes',
            'label' => __('admin/dashboard.dependency_integrity_status'),
            'description' => match ($depHealth['state']) {
                DependencyIntegrityService::STATE_MISMATCHED => __('admin/dashboard.dependency_integrity_mismatched', [
                    'count' => $depHealth['mismatched'],
                    'packages' => implode(', ', array_map(
                        static fn (array $s): string => $s['name'].' '.($s['installed'] ?? '—').' ≠ '.$s['locked'],
                        $depHealth['samples']
                    )),
                ]),
                DependencyIntegrityService::STATE_UNKNOWN => __('admin/dashboard.dependency_integrity_unknown', [
                    'reason' => (string) $depHealth['reason'],
                ]),
                default => __('admin/dashboard.dependency_integrity_ok', ['count' => $depHealth['checked']]),
            },
            'url' => route('admin.settings.systems.updates.index'),
            'requires_advanced_mode' => true,
        ];

        // 2FA status (actual status considering global settings + profile settings)
        $twoFaStatusService = app(TwoFaStatusService::class);
        $twoFaEnabled = $twoFaStatusService->isTwoFaEnabled($user);
        $actualMode = $twoFaEnabled
            ? AuthenticationMode::from($twoFaStatusService->getActualTwoFaMode($user))
            : null;

        $items[] = [
            'key' => 'two_fa',
            'status' => $twoFaEnabled ? 'ok' : 'recommendation',
            'icon' => 'fas fa-user-shield',
            'label' => __('admin/dashboard.two_fa_status'),
            'description' => $twoFaEnabled
                ? __('admin/dashboard.two_fa_enabled', ['method' => $actualMode->twoFactorLabel()])
                : __('admin/dashboard.two_fa_disabled'),
            'url' => route('admin.settings.security.two-fa'),
            'requires_advanced_mode' => false,
        ];

        return $items;
    }

    /**
     * Get mail server status
     *
     * @return array{status: string, icon: string, label: string, description: string, mailer: string, url: string|null, requires_advanced_mode: bool}
     */
    public static function mailServerStatus(): array
    {
        $mailer = ConfigHelper::getMailMailer();
        $host = ConfigHelper::getMailHost();
        $port = ConfigHelper::getMailPort();
        $fromAddress = ConfigHelper::getMailFromAddress();
        $url = route('admin.settings.base.mail');

        // log / array / mailpit drivers are warnings (for development)
        if (in_array($mailer, ['log', 'array', 'mailpit'], true)) {
            return [
                'status' => 'warning',
                'icon' => 'fas fa-envelope',
                'label' => __('admin/dashboard.mail_status'),
                'description' => __('admin/dashboard.mail_using_log_driver', ['driver' => $mailer]),
                'mailer' => $mailer,
                'url' => $url,
                'requires_advanced_mode' => false,
            ];
        }

        // Incomplete SMTP settings (host, port, and sender address are minimum requirements)
        $isValid = ! empty($host) && $port > 0 && ! empty($fromAddress);
        if (! $isValid) {
            return [
                'status' => 'warning',
                'icon' => 'fas fa-envelope',
                'label' => __('admin/dashboard.mail_status'),
                'description' => __('admin/dashboard.mail_not_configured'),
                'mailer' => $mailer,
                'url' => $url,
                'requires_advanced_mode' => false,
            ];
        }

        // Check the results of mail connection test, send test, and receive test
        $connectionTested = (bool) SiteSetting::get('mail_connection_tested', false);
        $sendTested = (bool) SiteSetting::get('mail_send_tested', false);
        $receiveTested = (bool) SiteSetting::get('mail_receive_tested', false);

        if (! $connectionTested || ! $sendTested || ! $receiveTested) {
            return [
                'status' => 'recommendation',
                'icon' => 'fas fa-envelope',
                'label' => __('admin/dashboard.mail_status'),
                'description' => __('admin/dashboard.mail_test_not_completed'),
                'mailer' => $mailer,
                'url' => $url,
                'requires_advanced_mode' => false,
            ];
        }

        return [
            'status' => 'ok',
            'icon' => 'fas fa-envelope',
            'label' => __('admin/dashboard.mail_status'),
            'description' => __('admin/dashboard.mail_configured'),
            'mailer' => $mailer,
            'url' => $url,
            'requires_advanced_mode' => false,
        ];
    }

    /**
     * Get CAPTCHA status
     *
     * @return array{status: string, icon: string, label: string, description: string, url: string|null, requires_advanced_mode: bool}
     */
    public static function captchaStatus(): array
    {
        $settings = CaptchaHelper::getSettings();
        $url = route('admin.settings.security.captcha');

        $isConfigured = $settings['enabled']
            && ! empty($settings['site_key'])
            && ! empty($settings['secret_key']);

        // Authentication test not yet completed
        $testPending = $isConfigured && ! $settings['authentication_result'];

        if ($testPending) {
            return [
                'status' => 'recommendation',
                'icon' => 'fas fa-robot',
                'label' => __('admin/dashboard.captcha_status'),
                'description' => __('admin/dashboard.captcha_test_not_completed'),
                'url' => $url,
                'requires_advanced_mode' => false,
            ];
        }

        return [
            'status' => $isConfigured ? 'ok' : 'recommendation',
            'icon' => 'fas fa-robot',
            'label' => __('admin/dashboard.captcha_status'),
            'description' => $isConfigured
                ? __('admin/dashboard.captcha_configured')
                : __('admin/dashboard.captcha_not_configured'),
            'url' => $url,
            'requires_advanced_mode' => false,
        ];
    }

    /**
     * Get system information
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
                // Read from the canonical core_version_history table, which
                // matches what `AdminFooterComposer` already feeds into the
                // admin footer. Using config('app.version') here historically
                // displayed the hard-coded '1.0.0' fallback because no
                // app.version key is defined in config/app.php.
                'value' => CoreVersionHistory::currentVersion(),
            ],
        ];
    }

    /**
     * Get plugin widgets
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
     * Get plugin notifications
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
     * Get overview of extensions (plugin/theme)
     *
     * @return array{
     *   plugins: array{installed: int, enabled: int},
     *   themes: array{installed: int, enabled: int},
     *   health: array<string, array{count: int, label: string, color: string, icon: string}>
     * }
     */
    public static function extensionOverview(): array
    {
        // Number of plugins
        $pluginsInstalled = Plugin::query()->installed()->count();
        $pluginsEnabled = Plugin::query()->enabled()->count();

        // Number of themes
        $themesInstalled = Theme::query()->installed()->count();
        $themesEnabled = Theme::query()->installed()->get()->filter(fn (Theme $t) => $t->isEnabled())->count();

        // Number of available updates (records with available_version set)
        $pluginUpdatesAvailable = Plugin::query()->whereNotNull('available_version')->count();
        $themeUpdatesAvailable = Theme::query()->whereNotNull('available_version')->count();

        // Core update — singleton row, only count when available_version is
        // actually newer than the installed core.
        $coreState = \App\Models\CoreRelease::singleton();
        $coreCurrent = (string) (\App\Models\CoreVersionHistory::currentVersion() ?? config('app.version', '0.0.0'));
        $coreUpdateAvailable = $coreState->available_version !== null
            && version_compare($coreState->available_version, $coreCurrent, '>')
            ? 1
            : 0;

        // Count by health status (audited plugins only)
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

        $hasAudits = collect($healthCounts)->sum('count') > 0;

        return [
            'plugins' => [
                'installed' => $pluginsInstalled,
                'enabled' => $pluginsEnabled,
            ],
            'themes' => [
                'installed' => $themesInstalled,
                'enabled' => $themesEnabled,
            ],
            'updates' => [
                'core' => $coreUpdateAvailable,
                'plugins' => $pluginUpdatesAvailable,
                'themes' => $themeUpdatesAvailable,
                'total' => $coreUpdateAvailable + $pluginUpdatesAvailable + $themeUpdatesAvailable,
            ],
            'health' => $healthCounts,
            'has_audits' => $hasAudits,
        ];
    }

    /**
     * Get recent administrator activity
     *
     * @return array{
     *   entries: array<int, array{action: string, category: string, actor_name: string, outcome: string, outcome_color: string, severity: string, severity_color: string, target_label: string|null, occurred_at: string}>,
     *   summary: array{failed_count: int, warning_count: int}
     * }
     */
    public static function recentActivity(): array
    {
        // Activity in the last 24 hours (latest 10 items)
        $entries = AuditLog::query()
            ->recent(24)
            ->latest('occurred_at')
            ->limit(10)
            ->get()
            ->map(function (AuditLog $log) {
                return [
                    'action' => $log->action,
                    'category' => $log->category,
                    'actor_name' => $log->actor_name ?? __('admin/dashboard.activity_system'),
                    'outcome' => $log->outcome,
                    'outcome_color' => $log->getOutcomeColorClass(),
                    'severity' => $log->severity,
                    'severity_color' => $log->getSeverityColorClass(),
                    'target_label' => $log->target_label,
                    'occurred_at' => $log->occurred_at
                        ? Carbon::parse($log->occurred_at)->diffForHumans()
                        : '',
                ];
            })
            ->toArray();

        // 24-hour security summary
        $failedCount = AuditLog::query()->recent(24)->failed()->count();
        $warningCount = AuditLog::query()->recent(24)->warningOrAbove()->count();

        return [
            'entries' => $entries,
            'summary' => [
                'failed_count' => $failedCount,
                'warning_count' => $warningCount,
            ],
        ];
    }

    /**
     * Get member overview
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

        // Role distribution
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

        // Number of 2FA enabled users (two_fa_mode > 0 = some 2FA is enabled)
        $twoFaEnabled = Member::query()->where('two_fa_mode', '>', 0)->count();
        $twoFaRate = $total > 0 ? round(($twoFaEnabled / $total) * 100, 1) : 0.0;

        // 5 most recent logins
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
