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

namespace App\Console\Commands;

use App\Enums\ExtensionCompatibilityStatus;
use App\Extension\ExtensionApi;
use App\Models\PluginAudit as PluginAuditModel;
use App\Services\Extension\ExtensionCompatibilityChecker;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\Scanning\PatternRegistry;
use App\Support\ExtensionDirectories;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Plugin permission audit command
 *
 * Analyzes plugin code and compares permissions declared in plugin.json with
 * features actually being used
 */
class PluginAudit extends Command
{
    protected $signature = 'dls:plugin:audit
                            {plugin : The plugin slug or directory name}
                            {--json : Output as JSON}
                            {--fix : Suggest fixes for plugin.json}
                            {--calculate-health : Calculate and save health score}';

    protected $description = 'Audit plugin code and compare with declared permissions in plugin.json';

    /**
     * Core table list
     */
    protected array $coreTables = [
        'users', 'members', 'plugins', 'media', 'settings',
        'site_settings', 'member_settings', 'security_settings',
        'password_reset_tokens', 'sessions', 'cache', 'jobs',
        'failed_jobs', 'members_login_attempts',
    ];

    public function __construct(
        protected PluginPermissionService $permissionService,
        protected PluginHealthScorer $healthScorer,
        protected ?PatternRegistry $patternRegistry = null,
    ) {
        parent::__construct();
        $this->patternRegistry ??= PatternRegistry::createDefault();
    }

    public function handle(): int
    {
        $pluginInput = $this->argument('plugin');
        $pluginDir = $this->resolvePluginDirectory($pluginInput);
        $isJson = $this->option('json');

        if (! $pluginDir) {
            if ($isJson) {
                $this->line(json_encode(['error' => "Plugin not found: {$pluginInput}"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error("Plugin not found: {$pluginInput}");
            }

            return Command::FAILURE;
        }

        $pluginSlug = Str::kebab(basename($pluginDir));
        $pluginJsonPath = "{$pluginDir}/plugin.json";

        // Display header only when not in JSON mode
        if (! $isJson) {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('🔍 Auditing plugin: '.basename($pluginDir));
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
        }

        // Get declared permissions from plugin.json
        $declaredPermissions = $this->getDeclaredPermissions($pluginJsonPath);

        // Get declared capabilities from plugin.json (for display purposes)
        $declaredCapabilities = $this->getDeclaredCapabilities($pluginJsonPath);

        // Analyze code to detect actually used permissions
        $detectedPermissions = $this->analyzePluginCode($pluginDir);

        // Generate comparison results
        $auditResult = $this->comparePermissions($declaredPermissions, $detectedPermissions);
        $auditResult['capabilities'] = $declaredCapabilities;
        $auditResult['api_compatibility'] = $this->checkApiCompatibility($pluginJsonPath);

        // Persist results to DB (skip in JSON mode as controller will save)
        if (! $isJson) {
            $saveData = [
                'has_mismatches' => ! empty($auditResult['mismatches']),
                'mismatches' => $auditResult['mismatches'] ?? [],
                'matches_count' => count($auditResult['matches'] ?? []),
                'total_checked' => $auditResult['total_checked'] ?? 0,
                'risk_level' => $auditResult['risk_level'] ?? null,
                'risk_reasons' => $auditResult['risk_reasons'] ?? [],
            ];

            PluginAuditModel::saveAuditResult($pluginSlug, $saveData);
        }

        // Calculate health score (optional)
        if ($this->option('calculate-health')) {
            $healthResult = $this->healthScorer->calculate($pluginSlug);

            PluginAuditModel::where('plugin_slug', $pluginSlug)->update([
                'health_score' => $healthResult->score,
                'health_status' => $healthResult->status->value,
            ]);

            $auditResult['health_score'] = $healthResult->score;
            $auditResult['health_status'] = $healthResult->status->value;
        }

        if ($isJson) {
            $this->outputJson($auditResult);
        } else {
            $this->outputReport($auditResult);

            if (isset($auditResult['health_score'])) {
                $this->newLine();
                $this->info("Health Score: {$auditResult['health_score']}/100 ({$auditResult['health_status']})");
            }
        }

        if ($this->option('fix') && ! empty($auditResult['mismatches'])) {
            $this->suggestFixes($pluginJsonPath, $auditResult);
        }

        return empty($auditResult['mismatches']) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Resolve plugin directory
     */
    protected function resolvePluginDirectory(string $input): ?string
    {
        // 1. Convert with Str::studly and search (case-sensitive exact match)
        $studlyName = Str::studly(str_replace('-', '_', $input));
        $path = $this->findDirectoryCaseSensitive(base_path('plugins'), $studlyName);
        if ($path !== null) {
            return $path;
        }

        // 2. Search with input as-is (case-sensitive)
        $path = $this->findDirectoryCaseSensitive(base_path('plugins'), $input);
        if ($path !== null) {
            return $path;
        }

        // 3. Search from directory column in DB (installed plugins)
        $plugin = \App\Models\Plugin::where('slug', $input)->first();
        if ($plugin && $plugin->directory) {
            $path = $this->findDirectoryCaseSensitive(base_path('plugins'), $plugin->directory);
            if ($path !== null) {
                return $path;
            }
        }

        // 4. Scan plugin directory and match by slug in plugin.json
        $pluginsDir = base_path('plugins');
        if (File::isDirectory($pluginsDir)) {
            foreach (File::directories($pluginsDir) as $dir) {
                // A move-aside copy carries the same manifest slug as the
                // live directory, so without this it would be returned once
                // the live directory is gone -- and --fix would then write
                // into the abandoned copy while reporting success.
                if (! ExtensionDirectories::isInstalledName(basename($dir))) {
                    continue;
                }

                // Match with kebab-case
                if (Str::kebab(basename($dir)) === $input) {
                    return $dir;
                }

                // Match with slug from plugin.json
                $pluginJson = $dir.'/plugin.json';
                if (File::exists($pluginJson)) {
                    $data = json_decode(File::get($pluginJson), true);
                    if (($data['slug'] ?? '') === $input) {
                        return $dir;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Search directory case-sensitively
     *
     * Returns exact directory name even on macOS case-insensitive filesystem
     */
    protected function findDirectoryCaseSensitive(string $parentDir, string $name): ?string
    {
        if (! File::isDirectory($parentDir)) {
            return null;
        }

        foreach (File::directories($parentDir) as $dir) {
            if (basename($dir) === $name) {
                return $dir;
            }
        }

        return null;
    }

    /**
     * Get declared permissions from plugin.json
     */
    protected function getDeclaredPermissions(string $pluginJsonPath): array
    {
        if (! File::exists($pluginJsonPath)) {
            return [];
        }

        $content = File::get($pluginJsonPath);
        $data = json_decode($content, true);

        $permissions = $data['permissions'] ?? [];

        // Normalize legacy core_tables format to core_tables_read/core_tables_write
        if (isset($permissions['database']['core_tables'])) {
            $coreTablesValue = $permissions['database']['core_tables'];

            if (! isset($permissions['database']['core_tables_read'])) {
                $permissions['database']['core_tables_read'] = $coreTablesValue;
            }
            if (! isset($permissions['database']['core_tables_write'])) {
                $permissions['database']['core_tables_write'] = is_array($coreTablesValue)
                    ? $coreTablesValue
                    : false;
            }
            unset($permissions['database']['core_tables']);
        }

        return $permissions;
    }

    /**
     * Get declared capabilities from plugin.json
     *
     * capabilities are declarations of features the plugin provides (e.g., ["seo", "backup"])
     * Information metadata used for feature detection from Core or other plugins
     * Not declaring them does not result in an audit error
     *
     * @return array<int, string>
     */
    protected function getDeclaredCapabilities(string $pluginJsonPath): array
    {
        if (! File::exists($pluginJsonPath)) {
            return [];
        }

        $data = json_decode(File::get($pluginJsonPath), true);
        $capabilities = $data['capabilities'] ?? [];

        if (! is_array($capabilities)) {
            return [];
        }

        return array_values(array_filter($capabilities, 'is_string'));
    }

    /**
     * Analyze plugin code (PatternRegistry-based)
     */
    protected function analyzePluginCode(string $pluginDir): array
    {
        return $this->patternRegistry->scan($pluginDir, 'plugin');
    }

    /**
     * Remediation text for a permission that was detected in code but not
     * declared in the manifest.
     *
     * Most detections are resolved by declaring the permission. A few are
     * policy violations that must never be declared — for those the
     * generic "set it to true" advice points the author in exactly the
     * wrong direction, so they carry their own text.
     */
    protected function undeclaredRecommendation(string $permission): string
    {
        return match ($permission) {
            'migrations.stock_migrator' => 'Remove the loadMigrationsFrom() call from the service provider. Extension migrations are applied by PluginMigrator / ThemeMigrator and recorded in their own ledgers; registering them with the stock migrator breaks a bare `php artisan migrate`.',
            default => "Set '{$permission}' to true",
        };
    }

    /**
     * Compare declared permissions with detected permissions
     */
    protected function comparePermissions(array $declared, array $detected): array
    {
        $mismatches = [];
        $matches = [];
        $detectedPerms = $detected['permissions'] ?? [];
        $evidence = $detected['evidence'] ?? [];

        foreach ($detectedPerms as $permission => $isDetected) {
            // Convert dot notation to array access
            $parts = explode('.', $permission);
            $declaredValue = $declared;
            foreach ($parts as $part) {
                $declaredValue = $declaredValue[$part] ?? false;
            }

            // Check if array is not empty
            if (is_array($declaredValue)) {
                $declaredValue = ! empty($declaredValue);
            }

            $isDeclared = (bool) $declaredValue;

            if ($isDetected && ! $isDeclared) {
                $mismatches[] = [
                    'permission' => $permission,
                    'declared' => $isDeclared,
                    'detected' => $isDetected,
                    'type' => 'undeclared_usage',
                    'evidence' => $evidence[$permission] ?? [],
                    'recommendation' => $this->undeclaredRecommendation($permission),
                ];
            } elseif (! $isDetected && $isDeclared) {
                $mismatches[] = [
                    'permission' => $permission,
                    'declared' => $isDeclared,
                    'detected' => $isDetected,
                    'type' => 'unused_declaration',
                    'evidence' => [],
                    'recommendation' => "Consider setting '{$permission}' to false (not detected in code)",
                ];
            } else {
                $matches[] = $permission;
            }
        }

        // Calculate risk level and reason uniformly (delegated to service)
        $riskResult = $this->permissionService->calculateUnifiedRiskLevel($declared, $mismatches);

        return [
            'mismatches' => $mismatches,
            'matches' => $matches,
            'total_checked' => count($detectedPerms),
            'risk_level' => $riskResult['level'],
            'risk_score' => $riskResult['score'],
            'risk_reasons' => $riskResult['reasons'],
        ];
    }

    /**
     * Output report
     */
    protected function outputReport(array $result): void
    {
        if (isset($result['api_compatibility'])) {
            $this->renderApiCompatibilityLine($result['api_compatibility']);
            $this->newLine();
        }

        $mismatches = $result['mismatches'];
        $matches = $result['matches'];

        if (empty($mismatches)) {
            $this->info("✅ All permissions match! ({$result['total_checked']} checked)");
            $this->newLine();

            return;
        }

        $this->warn('⚠️  Permission Mismatches Found:');
        $this->newLine();

        foreach ($mismatches as $mismatch) {
            $this->line("<fg=yellow>[{$mismatch['permission']}]</>");
            $this->line('  Declared: '.($mismatch['declared'] ? '<fg=green>true</>' : '<fg=red>false</>'));
            $this->line('  Detected: '.($mismatch['detected'] ? '<fg=green>true</>' : '<fg=red>false</>'));

            if (! empty($mismatch['evidence'])) {
                $this->line('  Evidence:');
                foreach (array_slice($mismatch['evidence'], 0, 3) as $ev) {
                    if ($ev['type'] === 'file_exists') {
                        $this->line("    - File: <fg=cyan>{$ev['file']}</>");
                    } else {
                        $this->line("    - <fg=cyan>{$ev['file']}</>:<fg=yellow>{$ev['line']}</> → {$ev['match']}");
                    }
                }
                if (count($mismatch['evidence']) > 3) {
                    $more = count($mismatch['evidence']) - 3;
                    $this->line("    ... and {$more} more");
                }
            }

            $this->line("  <fg=blue>→ {$mismatch['recommendation']}</>");
            $this->newLine();
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('✅ Matching: <fg=green>'.count($matches).'</>');
        $this->line('⚠️  Mismatches: <fg=yellow>'.count($mismatches).'</>');
    }

    /**
     * Output in JSON format
     */
    protected function outputJson(array $result): void
    {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Display fix suggestions
     */
    protected function suggestFixes(string $pluginJsonPath, array $result): void
    {
        $this->newLine();
        $this->info('📝 Suggested fixes for plugin.json:');
        $this->newLine();

        foreach ($result['mismatches'] as $mismatch) {
            if ($mismatch['type'] === 'undeclared_usage') {
                $parts = explode('.', $mismatch['permission']);
                $this->line("  \"{$parts[0]}\": {");
                $this->line("    \"{$parts[1]}\": <fg=green>true</>  // Currently: false");
                $this->line('  }');
            }
        }

        $this->newLine();
        $this->info("Run 'php artisan dls:plugin:update-json ".basename(dirname($pluginJsonPath))." --all' to add missing sections.");
    }

    /**
     * Check the Plugin API contract compatibility for the audited plugin.
     *
     * @return array{status: string, declared: ?string, core_version: string, message: string}
     */
    protected function checkApiCompatibility(string $pluginJsonPath): array
    {
        if (! File::exists($pluginJsonPath)) {
            return [
                'status' => ExtensionCompatibilityStatus::MissingDeclaration->value,
                'declared' => null,
                'core_version' => ExtensionApi::CURRENT_VERSION,
                'message' => 'plugin.json not found',
            ];
        }

        $manifest = json_decode(File::get($pluginJsonPath), true);
        if (! is_array($manifest)) {
            return [
                'status' => ExtensionCompatibilityStatus::MissingDeclaration->value,
                'declared' => null,
                'core_version' => ExtensionApi::CURRENT_VERSION,
                'message' => 'plugin.json could not be parsed',
            ];
        }

        $result = (new ExtensionCompatibilityChecker())->check($manifest);

        return [
            'status' => $result->status->value,
            'declared' => $result->declared,
            'core_version' => $result->coreVersion,
            'message' => $result->message,
        ];
    }

    /**
     * Render the API compatibility status as a single colored line.
     */
    protected function renderApiCompatibilityLine(array $compat): void
    {
        [$icon, $color] = match ($compat['status']) {
            ExtensionCompatibilityStatus::Compatible->value => ['✅', 'green'],
            ExtensionCompatibilityStatus::MissingDeclaration->value => ['⚠️', 'yellow'],
            ExtensionCompatibilityStatus::Incompatible->value => ['❌', 'red'],
            ExtensionCompatibilityStatus::MalformedConstraint->value => ['❌', 'red'],
            default => ['❓', 'gray'],
        };

        $declared = $compat['declared'] !== null
            ? " (declared: <fg={$color}>{$compat['declared']}</>)"
            : '';

        $this->line(sprintf(
            'Plugin API: %s <fg=%s>%s</>%s — core %s',
            $icon,
            $color,
            $compat['status'],
            $declared,
            $compat['core_version'],
        ));

        if ($compat['status'] !== ExtensionCompatibilityStatus::Compatible->value) {
            $this->line("           <fg=gray>{$compat['message']}</>");
        }
    }
}
