<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core only. Do not reference from plugins/themes
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

namespace App\Services\Extension;

use App\Models\PluginAudit;
use App\Models\ThemeAudit;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\PluginTableInspector;
use App\Services\Theme\ThemePermissionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * The single, complete extension rescan: permissions + CSP + signature +
 * health + files_hash + owned_tables, persisted to plugin_audits/theme_audits.
 *
 * Extracted verbatim from AdminPluginsSettingsController / AdminThemesSettingsController
 * so both the admin rescan endpoints AND the post-update auto-scan run the
 * identical, full audit. (The CLI dls:{plugin,theme}:audit commands are a
 * PARTIAL audit — they do not compute CSP/signature and, in non-JSON mode,
 * their save wipes those columns; this service invokes them with --json and
 * layers the rest on top before saving, so nothing is degraded.)
 *
 * @internal Core only. Do not reference from plugins/themes
 */
class ExtensionRescanService
{
    /**
     * Audit a plugin and save to DB. Returns the audit array.
     *
     * @return array<string, mixed>
     */
    public function rescanPlugin(string $pluginSlug): array
    {
        try {
            Log::info('Plugin audit starting', ['plugin' => $pluginSlug]);

            Artisan::call('dls:plugin:audit', [
                'plugin' => $pluginSlug,
                '--json' => true,
            ]);

            $output = trim(Artisan::output());

            Log::info('Plugin audit output', [
                'plugin' => $pluginSlug,
                'output_length' => strlen($output),
                'output_preview' => substr($output, 0, 500),
            ]);

            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Plugin audit JSON parse error', [
                    'plugin' => $pluginSlug,
                    'error' => json_last_error_msg(),
                    'output' => $output,
                ]);
            }

            if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
                // Get signature information
                $permissionService = app(PluginPermissionService::class);
                $summary = $permissionService->getSummary($pluginSlug);
                $signature = $summary['signature'] ?? [];

                // Verify CSP compliance status with code scan
                $cspScanner = app(\App\Services\Csp\CspComplianceScanner::class);
                $cspCompatibility = $cspScanner->scanPlugin($pluginSlug);

                // File hash (for rescan detection) and health score
                $healthScorer = app(PluginHealthScorer::class);
                $filesHash = $healthScorer->computeFilesHash($pluginSlug);

                // Extract owned_tables (auto-detected from migrations)
                $pluginName = \App\Models\Plugin::directoryNameFromSlug($pluginSlug);
                $extensionDir = base_path("plugins/{$pluginName}");
                $tableInspection = app(PluginTableInspector::class)->inspect($extensionDir);

                $auditData = [
                    'has_mismatches' => ! empty($result['mismatches'] ?? []),
                    'mismatches' => $result['mismatches'] ?? [],
                    'matches_count' => count($result['matches'] ?? []),
                    'total_checked' => $result['total_checked'] ?? 0,
                    'risk_level' => $result['risk_level'] ?? null,
                    'risk_reasons' => $result['risk_reasons'] ?? [],
                    'signature_status' => $signature['status'] ?? 'unsigned',
                    'signature_signer' => $signature['signer'] ?? null,
                    'csp_status' => $cspCompatibility['status'] ?? 'not_checked',
                    'csp_requires_inline_js' => $cspCompatibility['requires_inline_js'] ?? false,
                    'csp_requires_inline_css' => $cspCompatibility['requires_inline_css'] ?? false,
                    'csp_violations' => $cspCompatibility['violations'] ?? [],
                    'csp_summary' => $cspCompatibility['summary'] ?? [],
                    'files_hash' => $filesHash,
                    'owned_tables' => $tableInspection['tables'],
                ];

                Log::info('Plugin audit data', ['plugin' => $pluginSlug, 'data' => $auditData]);

                // Save to DB (base data before health score calculation)
                $audit = PluginAudit::saveAuditResult($pluginSlug, $auditData);

                // Calculate health score and persist its findings list
                // calculate() references plugin_audits rows, so execute after saveAuditResult
                try {
                    $healthResult = $healthScorer->calculate($pluginSlug);
                    $audit->update([
                        'health_score' => $healthResult->score,
                        'health_status' => $healthResult->status->value,
                        'health_issues' => array_map(fn ($issue) => $issue->jsonSerialize(), $healthResult->issues),
                    ]);
                    $audit->refresh();
                } catch (\Exception $e) {
                    Log::warning('Health score persist failed during audit', [
                        'plugin' => $pluginSlug,
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::info('Plugin audit saved', ['plugin' => $pluginSlug, 'audit_id' => $audit->id]);

                return $this->filterOptionalMismatches($pluginSlug, $audit->toAuditArray());
            }
        } catch (\Exception $e) {
            Log::error('Plugin audit failed', [
                'plugin' => $pluginSlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Return existing DB audit data to stay consistent with health scorer
        return $this->getPluginAuditResult($pluginSlug);
    }

    /**
     * Audit a theme and save to DB. Returns the audit array.
     *
     * @return array<string, mixed>
     */
    public function rescanTheme(string $themeSlug): array
    {
        try {
            Log::info('Theme audit starting', ['theme' => $themeSlug]);

            Artisan::call('dls:theme:audit', [
                'theme' => $themeSlug,
                '--json' => true,
            ]);

            $output = trim(Artisan::output());

            Log::info('Theme audit output', [
                'theme' => $themeSlug,
                'output_length' => strlen($output),
                'output_preview' => substr($output, 0, 500),
            ]);

            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Theme audit JSON parse error', [
                    'theme' => $themeSlug,
                    'error' => json_last_error_msg(),
                    'output' => $output,
                ]);
            }

            if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
                // Limit evidence for mismatches (reduce DB size)
                $mismatches = $result['mismatches'] ?? [];
                foreach ($mismatches as &$mismatch) {
                    if (isset($mismatch['evidence']) && is_array($mismatch['evidence'])) {
                        // Evidence is limited to a maximum of 3 items
                        $mismatch['evidence'] = array_slice($mismatch['evidence'], 0, 3);
                    }
                }
                unset($mismatch);

                // Get signature information
                $permissionService = app(ThemePermissionService::class);
                $summary = $permissionService->getSummary($themeSlug);
                $signature = $summary['signature'] ?? [];

                // Verify CSP compliance with code scan
                $cspScanner = app(\App\Services\Csp\CspComplianceScanner::class);
                $cspCompatibility = $cspScanner->scanTheme($themeSlug);

                // File hash + owned_tables (auto-detected from migrations)
                $healthScorer = app(\App\Services\Theme\ThemeHealthScorer::class);
                $filesHash = $healthScorer->computeFilesHash($themeSlug);
                $extensionDir = base_path("themes/{$themeSlug}");
                $tableInspection = app(\App\Services\Plugin\PluginTableInspector::class)->inspect($extensionDir);

                $auditData = [
                    'has_mismatches' => ! empty($mismatches),
                    'mismatches' => $mismatches,
                    'matches_count' => count($result['matches'] ?? []),
                    'total_checked' => $result['total_checked'] ?? 0,
                    'risk_level' => $result['risk_level'] ?? null,
                    'risk_reasons' => $result['risk_reasons'] ?? [],
                    'signature_status' => $signature['status'] ?? 'unsigned',
                    'signature_signer' => $signature['signer'] ?? null,
                    'csp_status' => $cspCompatibility['status'] ?? 'not_checked',
                    'csp_requires_inline_js' => $cspCompatibility['requires_inline_js'] ?? false,
                    'csp_requires_inline_css' => $cspCompatibility['requires_inline_css'] ?? false,
                    'csp_violations' => $cspCompatibility['violations'] ?? [],
                    'csp_summary' => $cspCompatibility['summary'] ?? [],
                    'files_hash' => $filesHash,
                    'owned_tables' => $tableInspection['tables'],
                ];

                Log::info('Theme audit data prepared', ['theme' => $themeSlug, 'mismatches_count' => count($mismatches)]);

                // Save to DB (base data before health score calculation)
                $audit = ThemeAudit::saveAuditResult($themeSlug, $auditData);

                // Persist health score and issue list afterward
                try {
                    $healthResult = $healthScorer->calculate($themeSlug);
                    $audit->update([
                        'health_score' => $healthResult->score,
                        'health_status' => $healthResult->status->value,
                        'health_issues' => array_map(fn ($issue) => $issue->jsonSerialize(), $healthResult->issues),
                    ]);
                    $audit->refresh();
                } catch (\Exception $e) {
                    Log::warning('Theme health score persist failed during audit', [
                        'theme' => $themeSlug,
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::info('Theme audit saved', ['theme' => $themeSlug, 'audit_id' => $audit->id]);

                return $audit->toAuditArray();
            }
        } catch (\Exception $e) {
            Log::error('Theme audit failed', [
                'theme' => $themeSlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 0,
            'audited_at' => null,
        ];
    }

    /**
     * Existing plugin audit results from DB (fallback / read path).
     *
     * @return array<string, mixed>
     */
    public function getPluginAuditResult(string $pluginSlug): array
    {
        $audit = PluginAudit::getBySlug($pluginSlug);

        if ($audit) {
            return $this->filterOptionalMismatches($pluginSlug, $audit->toAuditArray());
        }

        // Return empty result if no audit results exist
        return [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 0,
            'audited_at' => null,
        ];
    }

    /**
     * Existing theme audit results from DB (fallback / read path).
     *
     * @return array<string, mixed>
     */
    public function getThemeAuditResult(string $themeSlug): array
    {
        $audit = ThemeAudit::getBySlug($themeSlug);

        if ($audit) {
            return $audit->toAuditArray();
        }

        // Return empty result if no audit results exist
        return [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 0,
            'audited_at' => null,
        ];
    }

    /**
     * Drop "unused declaration" mismatches for permissions the plugin marked
     * optional, so an optional-but-unused permission is not flagged.
     *
     * @param  array<string, mixed>  $auditArray
     * @return array<string, mixed>
     */
    public function filterOptionalMismatches(string $pluginSlug, array $auditArray): array
    {
        $optional = app(PluginPermissionService::class)->getOptionalPermissions($pluginSlug);

        if (empty($optional) || empty($auditArray['mismatches'] ?? [])) {
            return $auditArray;
        }

        $auditArray['mismatches'] = array_values(array_filter(
            $auditArray['mismatches'],
            static fn (array $m) => ! (
                ($m['type'] ?? null) === 'unused_declaration'
                && in_array($m['permission'] ?? '', $optional, true)
            ),
        ));
        $auditArray['has_mismatches'] = ! empty($auditArray['mismatches']);

        return $auditArray;
    }
}
