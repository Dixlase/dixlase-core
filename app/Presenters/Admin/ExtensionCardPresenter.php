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

namespace App\Presenters\Admin;

use App\DTO\Plugin\HealthIssue;
use App\DTO\Plugin\HealthScoreResult;
use App\Enums\ExtensionSecurityLevel;
use App\Enums\ExtensionSecurityPreset;
use App\Enums\PluginEnableAction;
use App\Enums\PluginHealthStatus;
use App\Enums\PluginTrustLevel;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\SecuritySettingsRegistry;
use App\Services\Theme\ThemeHealthScorer;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ExtensionCardPresenter
{
    /**
     * Generate display data for theme card
     *
     * @param  \App\Models\Theme|array  $theme
     * @return array<string, mixed>
     */
    public static function forTheme($theme, ?int $activeThemeId): array
    {
        $isModel = is_object($theme);

        $name = $isModel ? $theme->name : ($theme['name'] ?? '');
        $description = $isModel ? $theme->description : ($theme['description'] ?? '');
        $version = $isModel ? $theme->version : ($theme['version'] ?? '1.0.0');
        $license = $isModel ? $theme->license : ($theme['license'] ?? '');
        $directory = $isModel ? $theme->directory : ($theme['directory'] ?? '');
        $slug = $isModel ? $theme->slug : ($theme['slug'] ?? '');
        $id = $isModel ? $theme->id : ($theme['directory'] ?? '');

        $authorRaw = $isModel ? $theme->author : ($theme['author'] ?? null);
        $authorEmail = $isModel ? $theme->email : ($theme['email'] ?? null);
        $authorUrl = $isModel ? $theme->web : ($theme['url'] ?? null);
        $author = self::parseAuthor($authorRaw, $authorEmail, $authorUrl);

        $isInstalled = $isModel;
        $isEnabled = $isModel && $theme->id === $activeThemeId;

        $thumbnailPath = "themes/{$directory}/thumbnail.png";
        $thumbnailUrl = file_exists(base_path($thumbnailPath))
            ? asset("assets/themes/{$directory}/thumbnail.png")
            : asset('assets/images/theme-default.svg');

        $permissionSummary = $isModel
            ? ($theme->permission_summary ?? null)
            : ($theme['permission_summary'] ?? null);

        $cspCompatibility = $isModel ? ($theme->csp_compatibility ?? []) : ($theme['csp_compatibility'] ?? []);
        $cspDiagnostic = $isModel ? ($theme->csp_diagnostic ?? null) : ($theme['csp_diagnostic'] ?? null);

        $auditResult = $permissionSummary['audit'] ?? [];
        $auditedAt = $auditResult['audited_at'] ?? null;

        // Prioritize CSP info from audit results if available
        if (! empty($auditResult['csp_status'])) {
            $cspCompatibility = [
                'status' => $auditResult['csp_status'],
                'requires_inline_js' => $auditResult['csp_requires_inline_js'] ?? false,
                'requires_inline_css' => $auditResult['csp_requires_inline_css'] ?? false,
                'has_csp_config' => $cspCompatibility['has_csp_config'] ?? false,
                'csp_ready' => ! ($auditResult['csp_requires_inline_js'] ?? false),
                'violations' => $auditResult['csp_violations'] ?? [],
                'summary' => $auditResult['csp_summary'] ?? [],
            ];
        }
        $auditedAtFormatted = $auditedAt ? Carbon::parse($auditedAt)->format('Y/m/d H:i') : null;

        $permissionModalId = 'permissionModal-theme-'.($isModel ? $theme->id : $directory);

        // Calculate health score
        $enableAction = PluginEnableAction::Allowed;
        $healthScore = null;
        $healthStatus = null;
        $healthIssues = [];
        try {
            $healthScorer = app(ThemeHealthScorer::class);
            $healthResult = $healthScorer->calculate($slug);
            $healthScore = $healthResult->score;
            $healthStatus = $healthResult->status->value;
            $healthIssues = array_values(array_filter(
                array_map(fn ($i) => $i->jsonSerialize(), $healthResult->issues),
                fn ($i) => ($i['deduction'] ?? 0) !== 0,
            ));
        } catch (\Exception $e) {
            Log::error('ExtensionCardPresenter: theme health calculation failed', [
                'theme' => $slug,
                'error' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);
        }

        $badge = self::computeBadge($permissionSummary, 'admin/settings/themes/index', $healthStatus);

        // Scan data for badge click
        $scanData = self::buildScanDataForBadge(
            $auditResult,
            $badge,
            $cspCompatibility,
            $healthScore,
            $healthStatus,
            $healthIssues,
            $permissionSummary['categories'] ?? [],
            $permissionSummary['risk_reasons'] ?? [],
            'admin/settings/themes/index',
        );

        $enableWarnings = $isModel ? self::computeEnableWarnings($permissionSummary, 'admin/settings/themes/index') : [];
        $installWarnings = ! $isModel ? self::computeInstallWarnings($permissionSummary, 'admin/settings/themes/index') : [];

        $operationStatus = self::computeOperationStatus($cspCompatibility, $healthStatus, $auditedAt, $enableAction);

        return [
            'isModel' => $isModel,
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'version' => $version,
            'license' => $license,
            'directory' => $directory,
            'slug' => $slug,
            'authorName' => $author['name'],
            'isInstalled' => $isInstalled,
            'isEnabled' => $isEnabled,
            'thumbnailUrl' => $thumbnailUrl,
            'permissionSummary' => $permissionSummary,
            'hasPermissions' => $badge['hasPermissions'],
            'riskLevel' => $badge['riskLevel'],
            'signature' => $badge['signature'],
            'signatureStatus' => $badge['signatureStatus'],
            'auditResult' => $auditResult,
            'hasMismatches' => $badge['hasMismatches'],
            'badgeColor' => $badge['badgeColor'],
            'badgeIcon' => $badge['badgeIcon'],
            'badgeLabel' => $badge['badgeLabel'],
            'permissionModalId' => $permissionModalId,
            'cspCompatibility' => $cspCompatibility,
            'cspDiagnostic' => $cspDiagnostic,
            'auditedAt' => $auditedAt,
            'auditedAtFormatted' => $auditedAtFormatted,
            'categories' => $permissionSummary['categories'] ?? [],
            'attentionReasons' => $permissionSummary['risk_reasons'] ?? [],
            'healthStatusColors' => self::getHealthStatusColors(),
            'healthStatusIcons' => self::getHealthStatusIcons(),
            'healthStatusLabelKeys' => self::getHealthStatusLabelKeys(),
            'enableWarnings' => $enableWarnings,
            'hasEnableWarnings' => ! empty($enableWarnings),
            'enableModalId' => $isModel ? 'enableThemeModal-'.$theme->id : null,
            'installWarnings' => $installWarnings,
            'hasInstallWarnings' => ! empty($installWarnings),
            'hasSettings' => $isModel ? ($theme->has_settings ?? false) : false,
            'healthScore' => $healthScore,
            'healthStatus' => $healthStatus,
            'healthIssues' => $healthIssues,
            'scanData' => $scanData,
            'capabilities' => \App\Helpers\PluginHelper::getCapabilitiesForDirectory($directory),
            'cspBarometerItems' => self::buildCspBarometerItems($cspCompatibility, $auditedAt),
            'presetBarometerItems' => self::buildPresetBarometerItems($healthStatus, $auditedAt),
            'operationStatus' => $operationStatus,
            'cspMaxTier' => $cspMaxTier = self::computeMaxCompatibleTier(self::buildCspModeBadges($cspCompatibility), $auditedAt),
            'presetMaxTier' => $presetMaxTier = self::computeMaxCompatibleTier(self::buildPresetCompatibilityBadges($healthStatus), $auditedAt),
            'healthIconColor' => ($healthStatus ?? 'not_verified') === 'healthy' ? 'text-green-500' : 'text-red-500',
            'opIconColor' => match ($operationStatus['status'] ?? 'unknown') {
                'ok' => 'text-green-500',
                'caution' => 'text-yellow-500',
                'blocked' => 'text-red-500',
                default => 'text-gray-400',
            },
            'cspTierIconColor' => self::tierToIconColor($cspMaxTier),
            'presetTierIconColor' => self::tierToIconColor($presetMaxTier),
            // Update availability
            'hasUpdateAvailable' => $isModel && $theme->hasUpdateAvailable(),
            'availableVersion' => $isModel ? $theme->available_version : null,
            // Owned database tables (auto-detected from migrations or declared in theme.json)
            'ownedTablesData' => self::buildOwnedTablesData('theme', $directory, $auditResult),
            // Scan freshness (badge state + thresholds)
            'scanFreshness' => self::buildScanFreshness($auditResult, (bool) ($isModel ? ($theme->files_changed ?? false) : ($theme['files_changed'] ?? false))),
        ];
    }

    /**
     * Generate display data for plugin card
     *
     * @param  \App\Models\Plugin|array  $plugin
     * @return array<string, mixed>
     */
    public static function forPlugin($plugin): array
    {
        $isModel = is_object($plugin);

        $name = $isModel ? $plugin->translated_name : ($plugin['name'] ?? '');
        $description = $isModel ? $plugin->translated_description : ($plugin['description'] ?? '');
        $version = $isModel ? $plugin->version : ($plugin['version'] ?? '1.0.0');
        $license = $isModel ? $plugin->license : ($plugin['license'] ?? '');
        $directory = $isModel ? $plugin->directory : ($plugin['directory'] ?? '');
        $slug = $isModel ? $plugin->slug : ($plugin['slug'] ?? '');
        $id = $isModel ? $plugin->id : ($plugin['directory'] ?? '');

        $authorRaw = $isModel ? $plugin->author : ($plugin['author'] ?? null);
        $authorEmail = $isModel ? $plugin->email : ($plugin['email'] ?? null);
        $authorUrl = $isModel ? $plugin->web : ($plugin['url'] ?? null);
        $author = self::parseAuthor($authorRaw, $authorEmail, $authorUrl);

        $isInstalled = $isModel;
        $isEnabled = $isModel && $plugin->isEnabled();

        $thumbnailPath = "plugins/{$directory}/thumbnail.png";
        $thumbnailUrl = file_exists(base_path($thumbnailPath))
            ? asset("assets/plugins/{$directory}/thumbnail.png")
            : asset('assets/images/plugin-default.svg');

        $permissionSummary = $isModel
            ? ($plugin->permission_summary ?? null)
            : ($plugin['permission_summary'] ?? null);

        $cspCompatibility = $isModel ? ($plugin->csp_compatibility ?? []) : ($plugin['csp_compatibility'] ?? []);
        $cspDiagnostic = $isModel ? ($plugin->csp_diagnostic ?? null) : ($plugin['csp_diagnostic'] ?? null);

        $auditResult = $permissionSummary['audit'] ?? [];
        $auditedAt = $auditResult['audited_at'] ?? null;

        // Prioritize CSP info from audit results if available (reflect actual scan results over static info from plugin.json)
        if (! empty($auditResult['csp_status'])) {
            $cspCompatibility = [
                'status' => $auditResult['csp_status'],
                'requires_inline_js' => $auditResult['csp_requires_inline_js'] ?? false,
                'requires_inline_css' => $auditResult['csp_requires_inline_css'] ?? false,
                'has_csp_config' => $cspCompatibility['has_csp_config'] ?? false,
                'csp_ready' => ! ($auditResult['csp_requires_inline_js'] ?? false),
                'violations' => $auditResult['csp_violations'] ?? [],
                'summary' => $auditResult['csp_summary'] ?? [],
            ];
        }
        $auditedAtFormatted = $auditedAt ? Carbon::parse($auditedAt)->format('Y/m/d H:i') : null;

        $permissionModalId = 'permissionModal-'.($isModel ? $plugin->id : $directory);

        $enableWarnings = $isModel ? self::computePluginEnableWarnings($plugin, $permissionSummary) : [];
        $installWarnings = ! $isModel ? self::computeInstallWarnings($permissionSummary, 'admin/settings/plugins/index') : [];

        // Calculate activation policy and health score (refer only to plugin_audits results in DB.
        // Live recalculation is only done via dls:plugin:audit / rescan button)
        $enableAction = PluginEnableAction::Allowed;
        $trustLevel = null;
        $healthScore = $auditResult['health_score'] ?? null;
        $healthStatus = $auditResult['health_status'] ?? null;
        $healthIssuesRaw = $auditResult['health_issues'] ?? [];
        $healthIssues = array_values(array_filter(
            $healthIssuesRaw,
            fn ($i) => is_array($i) && ($i['deduction'] ?? 0) !== 0,
        ));

        if ($auditedAt !== null) {
            try {
                $healthScorer = app(PluginHealthScorer::class);
                $synthetic = self::buildHealthResultFromAudit($healthScore, $healthStatus, $healthIssuesRaw);
                $enableAction = $healthScorer->determineEnableAction($synthetic);
            } catch (\Exception $e) {
                Log::error('ExtensionCardPresenter: enable action resolution failed', [
                    'plugin' => $slug,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile().':'.$e->getLine(),
                ]);
            }
        }

        $badge = self::computeBadge($permissionSummary, 'admin/settings/plugins/index', $healthStatus);

        if ($isModel) {
            // Calculate TrustLevel from signature info
            $signatureType = $badge['signature']['type'] ?? null;
            $signatureStatus = $badge['signatureStatus'];
            $trustLevel = PluginTrustLevel::fromSignatureVerification($signatureType, $signatureStatus);
        }

        $settingsUrl = null;
        if ($isModel && $isEnabled && ($plugin->has_settings ?? false)) {
            $settingsUrl = app(\App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController::class)->getPluginSettingsUrl($plugin);
        }

        // Scan data for badge click (compatible with JS buildUnifiedScanResultHtml)
        $scanData = self::buildScanDataForBadge(
            $auditResult,
            $badge,
            $cspCompatibility,
            $healthScore,
            $healthStatus,
            $healthIssues,
            $permissionSummary['categories'] ?? [],
            $permissionSummary['risk_reasons'] ?? [],
        );

        return [
            'isModel' => $isModel,
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'version' => $version,
            'license' => $license,
            'directory' => $directory,
            'slug' => $slug,
            'authorName' => $author['name'],
            'isInstalled' => $isInstalled,
            'isEnabled' => $isEnabled,
            'thumbnailUrl' => $thumbnailUrl,
            'permissionSummary' => $permissionSummary,
            'hasPermissions' => $badge['hasPermissions'],
            'riskLevel' => $badge['riskLevel'],
            'signature' => $badge['signature'],
            'signatureStatus' => $badge['signatureStatus'],
            'auditResult' => $auditResult,
            'hasMismatches' => $badge['hasMismatches'],
            'badgeColor' => $badge['badgeColor'],
            'badgeIcon' => $badge['badgeIcon'],
            'badgeLabel' => $badge['badgeLabel'],
            'permissionModalId' => $permissionModalId,
            'cspCompatibility' => $cspCompatibility,
            'cspDiagnostic' => $cspDiagnostic,
            'auditedAt' => $auditedAt,
            'auditedAtFormatted' => $auditedAtFormatted,
            'categories' => $permissionSummary['categories'] ?? [],
            'attentionReasons' => $permissionSummary['risk_reasons'] ?? [],
            'healthStatusColors' => self::getHealthStatusColors(),
            'healthStatusIcons' => self::getHealthStatusIcons(),
            'healthStatusLabelKeys' => self::getHealthStatusLabelKeys(),
            'enableWarnings' => $enableWarnings,
            'hasEnableWarnings' => ! empty($enableWarnings),
            'enableModalId' => $isModel ? 'enableModal-'.$plugin->id : null,
            'disableModalId' => $isModel ? 'disableModal-'.$plugin->id : null,
            'installWarnings' => $installWarnings,
            'hasInstallWarnings' => ! empty($installWarnings),
            'settingsUrl' => $settingsUrl,
            'translatedName' => $isModel ? ($plugin->translated_name ?? $plugin->name) : ($plugin['name'] ?? ''),
            'enableAction' => $enableAction->value,
            'isBlocked' => $enableAction === PluginEnableAction::Blocked,
            'healthScore' => $healthScore,
            'healthStatus' => $healthStatus,
            'healthIssues' => $healthIssues,
            'trustLevel' => $trustLevel?->value,
            'trustLevelLabel' => $trustLevel?->label(),
            'needsScan' => self::computeNeedsScan($slug, $auditedAt),
            'scanData' => $scanData,
            'capabilities' => \App\Helpers\PluginHelper::getCapabilitiesForDirectory($directory),
            'cspModeBadges' => self::buildCspModeBadges($cspCompatibility),
            'presetBadges' => self::buildPresetCompatibilityBadges($healthStatus),
            'cspBarometerItems' => self::buildCspBarometerItems($cspCompatibility, $auditedAt),
            'presetBarometerItems' => self::buildPresetBarometerItems($healthStatus, $auditedAt),
            'operationStatus' => $operationStatus = self::computeOperationStatus($cspCompatibility, $healthStatus, $auditedAt, $enableAction),
            'cspMaxTier' => $cspMaxTier = self::computeMaxCompatibleTier(self::buildCspModeBadges($cspCompatibility), $auditedAt),
            'presetMaxTier' => $presetMaxTier = self::computeMaxCompatibleTier(self::buildPresetCompatibilityBadges($healthStatus), $auditedAt),
            // Icon colors for template (moved from @php block)
            'healthIconColor' => ($healthStatus ?? 'not_verified') === 'healthy' ? 'text-green-500' : 'text-red-500',
            'opIconColor' => match ($operationStatus['status'] ?? 'unknown') {
                'ok' => 'text-green-500',
                'caution' => 'text-yellow-500',
                'blocked' => 'text-red-500',
                default => 'text-gray-400',
            },
            'cspTierIconColor' => self::tierToIconColor($cspMaxTier),
            'presetTierIconColor' => self::tierToIconColor($presetMaxTier),
            // Update availability
            'hasUpdateAvailable' => $isModel && $plugin->hasUpdateAvailable(),
            'availableVersion' => $isModel ? $plugin->available_version : null,
            // Owned database tables (auto-detected from migrations or declared in plugin.json)
            'ownedTablesData' => self::buildOwnedTablesData('plugin', $directory, $auditResult),
            // Scan freshness (badge state + thresholds)
            'scanFreshness' => self::buildScanFreshness($auditResult, (bool) ($isModel ? ($plugin->files_changed ?? false) : ($plugin['files_changed'] ?? false))),
            // Simple mode display data
            ...self::computeSimpleDisplayData($healthStatus, $operationStatus, $auditedAt),
        ];
    }

    /**
     * Return badge color based on health status
     *
     * @return array<string, string>
     */
    private static function getHealthStatusColors(): array
    {
        return [
            'healthy' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'advisory' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'needs_attention' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'not_verified' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        ];
    }

    /**
     * Return badge icon based on health status
     *
     * @return array<string, string>
     */
    private static function getHealthStatusIcons(): array
    {
        return [
            'healthy' => 'fas fa-check-circle',
            'advisory' => 'fas fa-info-circle',
            'needs_attention' => 'fas fa-exclamation-circle',
            'not_verified' => 'fas fa-question-circle',
        ];
    }

    /**
     * Return badge label key based on health status
     *
     * @return array<string, string>
     */
    private static function getHealthStatusLabelKeys(): array
    {
        return [
            'healthy' => 'health_status_healthy',
            'advisory' => 'health_status_advisory',
            'needs_attention' => 'health_status_needs_attention',
            'not_verified' => 'health_status_not_verified',
        ];
    }

    /**
     * Calculate badge info
     *
     * @param  array<string, mixed>|null  $permissionSummary
     * @return array{hasPermissions: bool, riskLevel: string, signature: array<string, mixed>, signatureStatus: string, hasMismatches: bool, badgeColor: string, badgeIcon: string, badgeLabel: string}
     */
    private static function computeBadge(?array $permissionSummary, string $translationPrefix, ?string $healthStatus = null): array
    {
        $hasPermissions = $permissionSummary['has_permissions'] ?? false;
        $riskLevel = $permissionSummary['risk_level'] ?? 'unknown';
        $signature = $permissionSummary['signature'] ?? ['status' => 'unsigned'];
        $signatureStatus = $signature['status'] ?? 'unsigned';
        $signatureType = $signature['type'] ?? null;
        $auditResult = $permissionSummary['audit'] ?? [];
        $hasMismatches = $auditResult['has_mismatches'] ?? false;

        if ($signatureStatus === 'invalid') {
            $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            $badgeIcon = 'fas fa-times-circle';
            $badgeLabel = __($translationPrefix.'.permissions.signature_invalid');
        } elseif ($hasPermissions) {
            // Badge display based on health status (new system)
            $statusColors = self::getHealthStatusColors();
            $statusIcons = self::getHealthStatusIcons();
            $statusLabelKeys = self::getHealthStatusLabelKeys();
            $status = $healthStatus ?? 'not_verified';
            $badgeColor = $statusColors[$status] ?? $statusColors['not_verified'];
            $badgeIcon = $statusIcons[$status] ?? $statusIcons['not_verified'];
            $badgeLabel = __($translationPrefix.'.permissions.'.($statusLabelKeys[$status] ?? 'health_status_not_verified'));
        } else {
            $badgeColor = 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
            $badgeIcon = 'fas fa-exclamation-triangle';
            $badgeLabel = __($translationPrefix.'.permissions.unknown');
        }

        return [
            'hasPermissions' => $hasPermissions,
            'riskLevel' => $riskLevel,
            'signature' => $signature,
            'signatureStatus' => $signatureStatus,
            'hasMismatches' => $hasMismatches,
            'badgeColor' => $badgeColor,
            'badgeIcon' => $badgeIcon,
            'badgeLabel' => $badgeLabel,
        ];
    }

    /**
     * Parse author info
     *
     * @param  mixed  $authorRaw
     * @return array{name: string|null}
     */
    private static function parseAuthor($authorRaw, ?string $email, ?string $url): array
    {
        if (is_array($authorRaw)) {
            return ['name' => $authorRaw['name'] ?? null];
        }

        return ['name' => $authorRaw];
    }

    /**
     * Calculate warnings for theme activation
     *
     * @param  array<string, mixed>|null  $permissionSummary
     * @return array<int, string>
     */
    private static function computeEnableWarnings(?array $permissionSummary, string $translationPrefix): array
    {
        $warnings = [];
        $signatureStatus = $permissionSummary['signature']['status'] ?? 'unsigned';
        $riskLevel = $permissionSummary['risk_level'] ?? 'low';
        $hasPermissions = $permissionSummary['has_permissions'] ?? false;
        $hasMismatches = $permissionSummary['audit']['has_mismatches'] ?? false;
        $auditedAt = $permissionSummary['audit']['audited_at'] ?? null;

        if ($signatureStatus === 'invalid') {
            $warnings[] = __($translationPrefix.'.permissions.enable_warning_invalid_signature');
        }
        if ($signatureStatus === 'unsigned' || $signatureStatus === 'none') {
            $warnings[] = __($translationPrefix.'.permissions.install_warning_unsigned');
        }
        if (! $hasPermissions) {
            $warnings[] = __($translationPrefix.'.permissions.install_warning_undefined');
        }
        if ($riskLevel === 'high') {
            $warnings[] = __($translationPrefix.'.permissions.enable_warning_needs_attention');
        }
        if ($riskLevel === 'medium') {
            $warnings[] = __($translationPrefix.'.permissions.health_warning');
        }
        if ($hasMismatches) {
            $warnings[] = __($translationPrefix.'.permissions.install_warning_mismatch');
        }
        if (! $auditedAt) {
            $warnings[] = __($translationPrefix.'.permissions.warning_not_scanned');
        }

        return $warnings;
    }

    /**
     * Calculate warnings for plugin activation
     *
     * @param  \App\Models\Plugin  $plugin
     * @param  array<string, mixed>|null  $permissionSummary
     * @return array<int, string>
     */
    private static function computePluginEnableWarnings($plugin, ?array $permissionSummary): array
    {
        $warnings = [];
        $prefix = 'admin/settings/plugins/index';
        $signatureStatus = $permissionSummary['signature']['status'] ?? 'unsigned';
        $riskLevel = $permissionSummary['risk_level'] ?? 'low';
        $hasPermissions = $permissionSummary['has_permissions'] ?? false;
        $hasMismatches = $permissionSummary['audit']['has_mismatches'] ?? false;
        $auditedAt = $permissionSummary['audit']['audited_at'] ?? null;

        if ($signatureStatus === 'invalid') {
            $warnings[] = __($prefix.'.permissions.enable_warning_invalid_signature');
        }
        if ($signatureStatus === 'unsigned' || $signatureStatus === 'none') {
            $warnings[] = __($prefix.'.permissions.install_warning_unsigned');
        }
        if (! $hasPermissions) {
            $warnings[] = __($prefix.'.permissions.install_warning_undefined');
        }
        if ($riskLevel === 'high') {
            $warnings[] = __($prefix.'.permissions.enable_warning_high_risk');
        }
        if ($hasMismatches) {
            $warnings[] = __($prefix.'.permissions.install_warning_mismatch');
        }
        if (! $auditedAt) {
            $warnings[] = __($prefix.'.permissions.warning_not_scanned');
        }

        return $warnings;
    }

    /**
     * Calculate warning flags for installation
     *
     * @param  array<string, mixed>|null  $permissionSummary
     * @return array{hasMismatches: bool, isNotScanned: bool, isUnsigned: bool, isUndefined: bool, riskLevel: string, hasWarnings: bool}
     */
    private static function computeInstallWarnings(?array $permissionSummary, string $translationPrefix): array
    {
        $audit = $permissionSummary['audit'] ?? [];
        $hasMismatches = $audit['has_mismatches'] ?? false;
        $auditedAt = $audit['audited_at'] ?? null;
        $isNotScanned = empty($auditedAt);
        $isUnsigned = ($permissionSummary['signature']['status'] ?? 'unsigned') === 'unsigned';
        $isUndefined = ! ($permissionSummary['has_permissions'] ?? false);
        $riskLevel = $permissionSummary['risk_level'] ?? 'unknown';
        $hasWarnings = $hasMismatches || $isUnsigned || $isUndefined || $isNotScanned || in_array($riskLevel, ['medium', 'high']);

        return [
            'hasMismatches' => $hasMismatches,
            'isNotScanned' => $isNotScanned,
            'isUnsigned' => $isUnsigned,
            'isUndefined' => $isUndefined,
            'riskLevel' => $riskLevel,
            'hasWarnings' => $hasWarnings,
        ];
    }

    /**
     * Generate compatibility badge data by CSP mode.
     *
     * Strict mode badge is intentionally always returned as unknown until
     * core itself supports CSP strict mode. The current scanner only checks
     * for inline <script>/<style> tags, which is too narrow a definition —
     * strict mode also requires Alpine CSP build compatibility (no x-data
     * literals, no object literals in directives, no x-init with logic),
     * Trusted Types compliance (no innerHTML / document.write), and SRI on
     * external scripts. None of these are detected yet, so a green "strict"
     * badge here would be a false positive. See
     * .backlog/csp-strict-mode-readiness.md for the full criteria.
     *
     * @param  array<string, mixed>  $cspCompatibility
     * @return array<string, array{compatible: bool|null, checked: bool}>
     */
    private static function buildCspModeBadges(array $cspCompatibility): array
    {
        $status = $cspCompatibility['status'] ?? 'unknown';
        $requiresInlineJs = $cspCompatibility['requires_inline_js'] ?? false;
        $isChecked = in_array($status, ['csp_ready', 'compatible', 'compliant', 'inline_required', 'inline_css_only'], true);

        if (! $isChecked) {
            // Not checked: all modes unknown (gray)
            return [
                'development' => ['compatible' => null, 'checked' => false],
                'standard' => ['compatible' => null, 'checked' => false],
                'strict' => ['compatible' => null, 'checked' => false],
            ];
        }

        // Development mode: always compatible (inline is allowed)
        $devCompatible = true;

        // Standard mode: inline JS is blocked (nonce-less inline)
        $standardCompatible = ! $requiresInlineJs;

        return [
            'development' => ['compatible' => $devCompatible, 'checked' => true],
            'standard' => ['compatible' => $standardCompatible, 'checked' => true],
            // Deliberately not asserting strict-mode compatibility until the
            // scanner can verify Alpine CSP build / Trusted Types / SRI.
            'strict' => ['compatible' => null, 'checked' => false],
        ];
    }

    /**
     * Generate compatibility badge data by security preset
     *
     * @return array<string, array{compatible: bool}>
     */
    private static function buildPresetCompatibilityBadges(?string $healthStatus): array
    {
        if ($healthStatus === null) {
            return [
                'development' => ['compatible' => null],
                'balanced' => ['compatible' => null],
                'strict' => ['compatible' => null],
                'custom' => ['compatible' => null],
            ];
        }

        $status = PluginHealthStatus::from($healthStatus);

        $presets = [
            'development' => ExtensionSecurityPreset::Development,
            'balanced' => ExtensionSecurityPreset::Balanced,
            'strict' => ExtensionSecurityPreset::Strict,
        ];

        $badges = [];
        foreach ($presets as $key => $preset) {
            $settings = $preset->getDefaultSettings();
            $maxLevel = ExtensionSecurityLevel::from($settings['plugin_max_health_level']);
            $badges[$key] = ['compatible' => $status->canActivate($maxLevel)];
        }

        // Custom mode: use actual current settings
        try {
            $customMaxLevel = ExtensionSecurityLevel::from(
                (int) SecuritySettingsRegistry::get('extension_plugin_max_health_level', ExtensionSecurityLevel::Warning->value)
            );
            $badges['custom'] = ['compatible' => $status->canActivate($customMaxLevel)];
        } catch (\Exception $e) {
            $badges['custom'] = ['compatible' => null];
        }

        return $badges;
    }

    /**
     * CSP barometer items for the ui-barometer component
     *
     * @param  array<string, mixed>  $cspCompatibility
     * @return array<int, array{label: string, status: string}>
     */
    public static function buildCspBarometerItems(array $cspCompatibility, ?string $auditedAt): array
    {
        $modes = ['development', 'standard', 'strict'];

        if ($auditedAt === null) {
            return array_map(fn ($mode) => [
                'label' => __('admin/settings/plugins/index.csp_mode.'.$mode),
                'status' => 'unknown',
                'tier' => $mode,
            ], $modes);
        }

        $badges = self::buildCspModeBadges($cspCompatibility);
        $items = [];

        foreach ($modes as $mode) {
            $badge = $badges[$mode];
            if (! $badge['checked']) {
                $status = 'unknown';
            } else {
                $status = $badge['compatible'] ? 'ok' : 'ng';
            }
            $items[] = [
                'label' => __('admin/settings/plugins/index.csp_mode.'.$mode),
                'status' => $status,
                'tier' => $mode,
            ];
        }

        return $items;
    }

    /**
     * Preset barometer items for the ui-barometer component
     *
     * @return array<int, array{label: string, status: string, tier: string}>
     */
    public static function buildPresetBarometerItems(?string $healthStatus, ?string $auditedAt): array
    {
        $tierMap = [
            'development' => 'development',
            'balanced' => 'standard',
            'strict' => 'strict',
        ];
        $presets = ['development', 'balanced', 'strict'];

        if ($auditedAt === null) {
            return array_map(fn ($preset) => [
                'label' => __('admin/settings/plugins/index.preset_badge.'.$preset),
                'status' => 'unknown',
                'tier' => $tierMap[$preset],
            ], $presets);
        }

        $badges = self::buildPresetCompatibilityBadges($healthStatus);
        $items = [];

        foreach ($presets as $preset) {
            $compatible = $badges[$preset]['compatible'];
            if ($compatible === null) {
                $status = 'unknown';
            } else {
                $status = $compatible ? 'ok' : 'ng';
            }
            $items[] = [
                'label' => __('admin/settings/plugins/index.preset_badge.'.$preset),
                'status' => $status,
                'tier' => $tierMap[$preset],
            ];
        }

        return $items;
    }

    /**
     * Compute operation status (traffic light) based on current CSP mode and security preset
     *
     * @param  array<string, mixed>  $cspCompatibility
     * @return array{status: string, label: string}
     */
    private static function computeOperationStatus(
        array $cspCompatibility,
        ?string $healthStatus,
        ?string $auditedAt,
        PluginEnableAction $enableAction
    ): array {
        if ($auditedAt === null) {
            return [
                'status' => 'unknown',
                'label' => __('admin/settings/plugins/index.operation_status.unknown'),
            ];
        }

        // Check CSP compatibility with current mode
        $cspModeMap = [0 => 'development', 1 => 'standard', 2 => 'strict'];
        $currentCspMode = $cspModeMap[(int) SecuritySettingsRegistry::get('csp_mode', 1)] ?? 'standard';
        $cspBadges = self::buildCspModeBadges($cspCompatibility);
        $cspCompatibleWithCurrentMode = ($cspBadges[$currentCspMode]['checked'] ?? false)
            ? $cspBadges[$currentCspMode]['compatible']
            : null;

        // Red: security preset blocks OR CSP incompatible with current mode
        if ($enableAction === PluginEnableAction::Blocked || $cspCompatibleWithCurrentMode === false) {
            return [
                'status' => 'blocked',
                'label' => __('admin/settings/plugins/index.operation_status.blocked'),
            ];
        }

        // Yellow: minor issues (warning or acknowledgement required)
        if ($enableAction === PluginEnableAction::WarningRequired || $enableAction === PluginEnableAction::AcknowledgementRequired) {
            return [
                'status' => 'caution',
                'label' => __('admin/settings/plugins/index.operation_status.caution'),
            ];
        }

        // Green: fully operational
        return [
            'status' => 'ok',
            'label' => __('admin/settings/plugins/index.operation_status.ok'),
        ];
    }

    /**
     * Compute the highest compatible tier from badge data
     *
     * Returns: 'strict' (green), 'standard' (yellow), 'development' (red), or 'unknown' (gray)
     * The tiers are checked from highest to lowest; the first compatible one wins.
     *
     * @param  array<string, array{compatible: bool|null}>  $badges
     */
    private static function computeMaxCompatibleTier(array $badges, ?string $auditedAt): string
    {
        if ($auditedAt === null) {
            return 'unknown';
        }

        $tiers = ['strict', 'standard', 'balanced', 'development'];

        foreach ($tiers as $tier) {
            if (isset($badges[$tier]) && ($badges[$tier]['compatible'] ?? null) === true) {
                return match ($tier) {
                    'balanced' => 'standard',
                    default => $tier,
                };
            }
        }

        return 'none';
    }

    /**
     * Map tier level to icon color class
     */
    private static function tierToIconColor(string $tier): string
    {
        return match ($tier) {
            'strict' => 'text-green-500',
            'standard' => 'text-yellow-500',
            'development' => 'text-red-500',
            default => 'text-gray-400',
        };
    }

    /**
     * Compute simplified display data for simple mode
     *
     * @param  array<string, string>  $operationStatus
     * @return array<string, string>
     */
    private static function computeSimpleDisplayData(?string $healthStatus, array $operationStatus, ?string $auditedAt): array
    {
        $prefix = 'admin/settings/plugins/index.simple.';

        // Not scanned: show "unknown" for both
        if ($auditedAt === null) {
            $unknownStyle = [
                'label' => __($prefix.'unknown'),
                'icon' => 'fas fa-question-circle',
                'iconColor' => 'text-gray-400',
                'badgeColor' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
            ];

            return [
                'simpleHealthLabel' => $unknownStyle['label'],
                'simpleHealthIcon' => $unknownStyle['icon'],
                'simpleHealthIconColor' => $unknownStyle['iconColor'],
                'simpleHealthBadgeColor' => $unknownStyle['badgeColor'],
                'simpleOperationLabel' => $unknownStyle['label'],
                'simpleOperationIcon' => $unknownStyle['icon'],
                'simpleOperationIconColor' => $unknownStyle['iconColor'],
                'simpleOperationBadgeColor' => $unknownStyle['badgeColor'],
            ];
        }

        // Health: healthy=safe(green), advisory=caution(yellow), else=problem(red)
        [$simpleHealthLabel, $simpleHealthIcon, $simpleHealthIconColor, $simpleHealthBadgeColor] = match ($healthStatus) {
            'healthy' => [
                __($prefix.'health_safe'), 'fas fa-check-circle', 'text-green-500',
                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            ],
            'advisory' => [
                __($prefix.'health_caution'), 'fas fa-exclamation-triangle', 'text-yellow-500',
                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            ],
            default => [
                __($prefix.'health_problem'), 'fas fa-times-circle', 'text-red-500',
                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            ],
        };

        // Operation: in simple mode, "problem" health also means unavailable
        $isHealthProblem = ! in_array($healthStatus, ['healthy', 'advisory'], true);
        $opOk = $operationStatus['status'] === 'ok' && ! $isHealthProblem;

        return [
            'simpleHealthLabel' => $simpleHealthLabel,
            'simpleHealthIcon' => $simpleHealthIcon,
            'simpleHealthIconColor' => $simpleHealthIconColor,
            'simpleHealthBadgeColor' => $simpleHealthBadgeColor,
            'simpleOperationLabel' => __($prefix.($opOk ? 'operation_usable' : 'operation_unusable')),
            'simpleOperationIcon' => $opOk ? 'fas fa-check-circle' : 'fas fa-times-circle',
            'simpleOperationIconColor' => $opOk ? 'text-green-500' : 'text-red-500',
            'simpleOperationBadgeColor' => $opOk
                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        ];
    }

    /**
     * Format attentionReason display data
     *
     * @param  array<int, mixed>  $reasons
     * @param  string  $translationPrefix  e.g. 'admin/settings/themes/index'
     * @return array<int, array{text: string, color: string, icon: string, score: int}>
     */
    public static function formatAttentionReasons(array $reasons, string $translationPrefix): array
    {
        $formatted = [];
        foreach ($reasons as $reason) {
            if (is_string($reason)) {
                $formatted[] = [
                    'text' => $reason,
                    'color' => 'text-yellow-600 dark:text-yellow-400',
                    'icon' => 'fas fa-info-circle',
                    'score' => 0,
                ];
            } else {
                $reasonKey = str_replace('.', '_', $reason['key'] ?? '');
                $severity = $reason['severity'] ?? 'medium';
                $details = $reason['details'] ?? [];
                $translationParams = ! empty($details) ? ['domains' => implode(', ', $details)] : [];
                if (isset($reason['count'])) {
                    $translationParams['count'] = $reason['count'];
                }
                $formatted[] = [
                    'text' => __($translationPrefix.'.permissions.attention_reason_'.$reasonKey, $translationParams),
                    'color' => match ($severity) {
                        'high' => 'text-orange-600 dark:text-orange-400',
                        'info' => 'text-blue-600 dark:text-blue-400',
                        default => 'text-yellow-600 dark:text-yellow-400',
                    },
                    'icon' => match ($severity) {
                        'high' => 'fas fa-exclamation-circle',
                        'info' => 'fas fa-check-circle',
                        default => 'fas fa-info-circle',
                    },
                    'score' => $reason['score'] ?? 0,
                ];
            }
        }

        return $formatted;
    }

    /**
     * Build scan data for badge clicks (compatible with JS buildUnifiedScanResultHtml)
     *
     * @param  array<string, mixed>  $auditResult
     * @param  array<string, mixed>  $badge
     * @param  array<string, mixed>  $cspCompatibility
     * @param  array<int, array<string, mixed>>  $healthIssues
     * @param  array<string, array<int, string>>  $categories
     * @param  array<int, mixed>  $attentionReasons
     * @return array<string, mixed>
     */
    private static function buildScanDataForBadge(
        array $auditResult,
        array $badge,
        array $cspCompatibility,
        ?int $healthScore,
        ?string $healthStatus,
        array $healthIssues,
        array $categories,
        array $attentionReasons,
        string $translationPrefix = 'admin/settings/plugins/index',
    ): array {
        // Convert signature information to audit-compatible format
        $signatureStatus = $badge['signatureStatus'] ?? 'unsigned';
        $signatureSigner = $badge['signature']['signed_by'] ?? '';
        $signatureType = $badge['signature']['type'] ?? null;

        // Signature status mapping (presenter → scan-result-builder compatible)
        $sigStatusMap = [
            'valid' => $signatureType ?? 'signed',
            'pending_verification' => $signatureType ?? 'signed',
            'invalid' => 'invalid',
            'unsigned' => 'unsigned',
        ];

        // Format attention reasons (JS compatible)
        $formattedReasons = self::formatAttentionReasons($attentionReasons, $translationPrefix);

        // CSP status mapping
        $cspStatus = $cspCompatibility['status'] ?? 'unknown';
        $cspStatusMap = [
            'csp_ready' => 'compliant',
            'compatible' => 'compliant',
        ];

        return [
            'audit' => [
                'signature_status' => $sigStatusMap[$signatureStatus] ?? 'unsigned',
                'signature_signer' => $signatureSigner,
                'has_mismatches' => $badge['hasMismatches'],
                'mismatches' => $auditResult['mismatches'] ?? [],
                'formatted_attention_reasons' => $formattedReasons,
                'total_checked' => $auditResult['total_checked'] ?? 0,
                'matches_count' => $auditResult['matches_count'] ?? 0,
                'csp_status' => $cspStatusMap[$cspStatus] ?? $cspStatus,
                'csp_requires_inline_js' => $cspCompatibility['requires_inline_js'] ?? false,
                'csp_requires_inline_css' => $cspCompatibility['requires_inline_css'] ?? false,
                'csp_violations' => $cspCompatibility['violations'] ?? [],
                'csp_summary' => $cspCompatibility['summary'] ?? [],
            ],
            'healthScore' => $healthScore,
            'healthStatus' => $healthStatus ?? 'not_verified',
            'healthIssues' => $healthIssues,
            'categories' => $categories,
        ];
    }

    /**
     * Whether the plugin requires scanning (including re-scan)
     */
    private static function computeNeedsScan(string $slug, ?string $auditedAt): bool
    {
        // Once scanned, do not force re-scan for install/enable flow.
        // Users can manually re-scan from the card if files have changed.
        return $auditedAt === null;
    }

    /**
     * Determine scan freshness state based on audit row + caller-supplied flags.
     *
     * Returned state is one of:
     *   - 'unscanned'      audit_at is null
     *   - 'files_changed'  $filesChanged is true (caller decides via mtime/hash)
     *   - 'expired'        audited_at older than max age days
     *   - 'fresh'          recent and unchanged
     *
     * @param  array<string, mixed>  $auditResult  plugin_audits row (toAuditArray)
     * @param  bool  $filesChanged  precomputed flag; controller compares latest mtime vs audited_at
     * @return array{state: string, maxAgeDays: int, ageDays: ?int}
     */
    public static function buildScanFreshness(array $auditResult, bool $filesChanged = false): array
    {
        $maxAgeDays = (int) SecuritySettingsRegistry::get('extension_audit_max_age_days', 30);
        if ($maxAgeDays < 1) {
            $maxAgeDays = 30;
        }

        $auditedAt = $auditResult['audited_at'] ?? null;

        if ($auditedAt === null) {
            return ['state' => 'unscanned', 'maxAgeDays' => $maxAgeDays, 'ageDays' => null];
        }

        if ($filesChanged) {
            return ['state' => 'files_changed', 'maxAgeDays' => $maxAgeDays, 'ageDays' => null];
        }

        try {
            $ageDays = (int) Carbon::parse($auditedAt)->diffInDays(Carbon::now());
        } catch (\Exception $e) {
            $ageDays = null;
        }

        if ($ageDays !== null && $ageDays > $maxAgeDays) {
            return ['state' => 'expired', 'maxAgeDays' => $maxAgeDays, 'ageDays' => $ageDays];
        }

        return ['state' => 'fresh', 'maxAgeDays' => $maxAgeDays, 'ageDays' => $ageDays];
    }

    /**
     * Reconstruct a HealthScoreResult from persisted plugin_audits data.
     *
     * Used to drive determineEnableAction() without re-running live evaluators.
     *
     * @param  array<int, array<string, mixed>>  $issuesRaw
     */
    private static function buildHealthResultFromAudit(?int $score, ?string $statusValue, array $issuesRaw): HealthScoreResult
    {
        $status = $statusValue !== null
            ? (PluginHealthStatus::tryFrom($statusValue) ?? PluginHealthStatus::NotVerified)
            : PluginHealthStatus::NotVerified;

        $issues = [];
        $hasCritical = false;
        foreach ($issuesRaw as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $issue = new HealthIssue(
                type: (string) ($entry['type'] ?? ''),
                severity: (string) ($entry['severity'] ?? 'info'),
                description: (string) ($entry['description'] ?? ''),
                evidence: is_array($entry['evidence'] ?? null) ? $entry['evidence'] : [],
                deduction: (int) ($entry['deduction'] ?? 0),
            );
            $issues[] = $issue;
            if ($issue->isCritical()) {
                $hasCritical = true;
            }
        }

        return new HealthScoreResult(
            score: (int) ($score ?? 0),
            status: $status,
            issues: $issues,
            hasCriticalIssue: $hasCritical,
        );
    }

    /**
     * Build the "owned tables" / "writes to other plugins" display data.
     *
     * Resolution order:
     *   1. plugin.json `permissions.database.owned_table_names` — manual declaration overrides auto-detect
     *   2. plugin_audits.owned_tables                           — auto-detected at scan time and persisted
     *
     * `writes_to_other_plugin_tables` is read from plugin.json only — it cannot
     * be auto-detected (writes via Eloquent / contracts have no static schema marker).
     *
     * Live migration scanning is intentionally *not* performed here so the page
     * stays cache-only. Detection happens during dls:plugin:audit / re-scan.
     *
     * @param  string  $type  'plugin' or 'theme'
     * @param  array<string, mixed>  $auditResult  plugin_audits row (toAuditArray)
     * @return array{
     *     tables: array<int, string>,
     *     tables_source: 'declared'|'detected'|'none',
     *     has_migrations: bool,
     *     writes_to_other_plugin_tables: array<string, array<int, string>>,
     * }
     */
    public static function buildOwnedTablesData(string $type, string $directory, array $auditResult = []): array
    {
        $baseDir = $type === 'theme' ? 'themes' : 'plugins';
        $manifestFile = $type === 'theme' ? 'theme.json' : 'plugin.json';
        $extensionDir = base_path("{$baseDir}/{$directory}");

        $declaredTables = [];
        $writesToOther = [];
        $manifestPath = "{$extensionDir}/{$manifestFile}";
        if (File::exists($manifestPath)) {
            $manifest = json_decode(File::get($manifestPath), true);
            if (is_array($manifest)) {
                $declaredTables = $manifest['permissions']['database']['owned_table_names']
                    ?? $manifest['database']['owned_table_names']
                    ?? [];
                $writesToOther = $manifest['permissions']['database']['writes_to_other_plugin_tables']
                    ?? $manifest['database']['writes_to_other_plugin_tables']
                    ?? [];
                if (! is_array($declaredTables)) {
                    $declaredTables = [];
                }
                if (! is_array($writesToOther)) {
                    $writesToOther = [];
                }
            }
        }

        $detectedTables = $auditResult['owned_tables'] ?? [];
        if (! is_array($detectedTables)) {
            $detectedTables = [];
        }

        if (! empty($declaredTables)) {
            $tables = array_values(array_unique(array_filter(array_map('strval', $declaredTables))));
            $source = 'declared';
        } elseif (! empty($detectedTables)) {
            $tables = array_values(array_filter(array_map('strval', $detectedTables)));
            $source = 'detected';
        } else {
            $tables = [];
            $source = 'none';
        }

        return [
            'tables' => $tables,
            'tables_source' => $source,
            'has_migrations' => ! empty($detectedTables) || ! empty($declaredTables),
            'writes_to_other_plugin_tables' => $writesToOther,
        ];
    }
}
