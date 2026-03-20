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

use App\Enums\ExtensionSecurityLevel;
use App\Enums\ExtensionSecurityPreset;
use App\Enums\PluginEnableAction;
use App\Enums\PluginHealthStatus;
use App\Enums\PluginTrustLevel;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\SecuritySettingsRegistry;
use Carbon\Carbon;

class ExtensionCardPresenter
{
    /**
     * テーマカードの表示データを生成
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

        $badge = self::computeBadge($permissionSummary, 'admin/settings/themes/index');

        $cspCompatibility = $isModel ? ($theme->csp_compatibility ?? []) : ($theme['csp_compatibility'] ?? []);
        $cspDiagnostic = $isModel ? ($theme->csp_diagnostic ?? null) : ($theme['csp_diagnostic'] ?? null);

        $auditResult = $permissionSummary['audit'] ?? [];
        $auditedAt = $auditResult['audited_at'] ?? null;
        $auditedAtFormatted = $auditedAt ? Carbon::parse($auditedAt)->format('Y/m/d H:i') : null;

        $permissionModalId = 'permissionModal-theme-'.($isModel ? $theme->id : $directory);

        $enableWarnings = $isModel ? self::computeEnableWarnings($permissionSummary, 'admin/settings/themes/index') : [];
        $installWarnings = ! $isModel ? self::computeInstallWarnings($permissionSummary, 'admin/settings/themes/index') : [];

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
        ];
    }

    /**
     * プラグインカードの表示データを生成
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

        // 監査結果にCSP情報がある場合はそちらを優先（plugin.jsonの静的情報より実際のスキャン結果を反映）
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

        // 有効化ポリシーと健全性スコアを算出（computeBadgeより先に実行）
        $enableAction = PluginEnableAction::Allowed;
        $healthScore = null;
        $healthStatus = null;
        $healthIssues = [];
        $trustLevel = null;
        try {
            $healthScorer = app(PluginHealthScorer::class);
            $healthResult = $healthScorer->calculate($slug);
            $healthScore = $healthResult->score;
            $healthStatus = $healthResult->status->value;
            $healthIssues = array_values(array_filter(
                array_map(fn ($i) => $i->jsonSerialize(), $healthResult->issues),
                fn ($i) => ($i['deduction'] ?? 0) !== 0,
            ));
            if ($isModel) {
                $enableAction = $healthScorer->determineEnableAction($healthResult);
            }
        } catch (\Exception $e) {
            // 算出失敗時はデフォルト値を維持
        }

        $badge = self::computeBadge($permissionSummary, 'admin/settings/plugins/index', $healthStatus);

        if ($isModel) {
            // 署名情報からTrustLevelを算出
            $signatureType = $badge['signature']['type'] ?? null;
            $signatureStatus = $badge['signatureStatus'];
            $trustLevel = PluginTrustLevel::fromSignatureVerification($signatureType, $signatureStatus);
        }

        $settingsUrl = null;
        if ($isModel && $isEnabled && ($plugin->has_settings ?? false)) {
            $settingsUrl = app(\App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController::class)->getPluginSettingsUrl($plugin);
        }

        // バッジクリック用のスキャンデータ（JS buildUnifiedScanResultHtml互換）
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
            'cspModeBadges' => self::buildCspModeBadges($cspCompatibility),
            'presetBadges' => self::buildPresetCompatibilityBadges($healthStatus),
        ];
    }

    /**
     * 健全性ステータスに基づくバッジカラーを返す
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
     * 健全性ステータスに基づくバッジアイコンを返す
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
     * 健全性ステータスに基づくバッジラベルキーを返す
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
     * バッジ情報を計算
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

        if ($signatureStatus === 'valid' || $signatureStatus === 'pending_verification') {
            $badgeColors = [
                'official' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                'verified' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'partner' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            ];
            $badgeIcons = [
                'official' => 'fas fa-crown',
                'verified' => 'fas fa-check-circle',
                'partner' => 'fas fa-handshake',
            ];
            $badgeLabels = [
                'official' => __($translationPrefix.'.permissions.signature_official'),
                'verified' => __($translationPrefix.'.permissions.signature_verified'),
                'partner' => __($translationPrefix.'.permissions.signature_partner'),
            ];
            $badgeColor = $badgeColors[$signatureType] ?? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            $badgeIcon = $badgeIcons[$signatureType] ?? 'fas fa-check-circle';
            $badgeLabel = $badgeLabels[$signatureType] ?? __($translationPrefix.'.permissions.signature_signed');
        } elseif ($signatureStatus === 'invalid') {
            $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            $badgeIcon = 'fas fa-times-circle';
            $badgeLabel = __($translationPrefix.'.permissions.signature_invalid');
        } elseif ($hasPermissions) {
            // 健全性ステータスに基づくバッジ表示（新システム）
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
     * 作者情報をパース
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
     * テーマ有効化時の警告を計算
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
     * プラグイン有効化時の警告を計算
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
     * インストール時の警告フラグを計算
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
     * CSP モード別互換性バッジデータを生成
     *
     * @param  array<string, mixed>  $cspCompatibility
     * @return array<string, array{compatible: bool, label: string, icon: string, color: string}>
     */
    private static function buildCspModeBadges(array $cspCompatibility): array
    {
        $status = $cspCompatibility['status'] ?? 'unknown';
        $requiresInlineJs = $cspCompatibility['requires_inline_js'] ?? false;
        $requiresInlineCss = $cspCompatibility['requires_inline_css'] ?? false;
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

        // Strict mode: all inline (JS and CSS) is blocked
        $strictCompatible = ! $requiresInlineJs && ! $requiresInlineCss;

        return [
            'development' => ['compatible' => $devCompatible, 'checked' => true],
            'standard' => ['compatible' => $standardCompatible, 'checked' => true],
            'strict' => ['compatible' => $strictCompatible, 'checked' => true],
        ];
    }

    /**
     * セキュリティプリセット別互換性バッジデータを生成
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
     * attentionReason表示データを整形
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
                $formatted[] = [
                    'text' => __($translationPrefix.'.permissions.attention_reason_'.$reasonKey),
                    'color' => ($reason['severity'] ?? 'medium') === 'high'
                        ? 'text-orange-600 dark:text-orange-400'
                        : 'text-yellow-600 dark:text-yellow-400',
                    'icon' => ($reason['severity'] ?? 'medium') === 'high'
                        ? 'fas fa-exclamation-circle'
                        : 'fas fa-info-circle',
                    'score' => $reason['score'] ?? 0,
                ];
            }
        }

        return $formatted;
    }

    /**
     * バッジクリック用のスキャンデータを構築（JS buildUnifiedScanResultHtml互換）
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
    ): array {
        // 署名情報をaudit互換形式に変換
        $signatureStatus = $badge['signatureStatus'] ?? 'unsigned';
        $signatureSigner = $badge['signature']['signed_by'] ?? '';
        $signatureType = $badge['signature']['type'] ?? null;

        // 署名ステータスのマッピング（presenter → scan-result-builder互換）
        $sigStatusMap = [
            'valid' => $signatureType ?? 'signed',
            'pending_verification' => $signatureType ?? 'signed',
            'invalid' => 'invalid',
            'unsigned' => 'unsigned',
        ];

        // attention reasonsをフォーマット（JS互換）
        $formattedReasons = self::formatAttentionReasons($attentionReasons, 'admin/settings/plugins/index');

        // CSPステータスのマッピング
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
     * プラグインがスキャン（再スキャン含む）を必要とするかどうか
     */
    private static function computeNeedsScan(string $slug, ?string $auditedAt): bool
    {
        // 未スキャンの場合は常にtrue
        if ($auditedAt === null) {
            return true;
        }

        // PluginHealthScorerでファイルハッシュ変更を検知
        try {
            $healthScorer = app(PluginHealthScorer::class);

            return $healthScorer->needsRescan($slug);
        } catch (\Exception $e) {
            return true;
        }
    }
}
