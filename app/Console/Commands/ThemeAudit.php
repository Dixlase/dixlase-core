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

use App\Services\Plugin\Scanning\PatternRegistry;
use App\Services\Theme\ThemePermissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Theme permission audit command
 *
 * Analyze theme code and compare permissions declared in theme.json
 * with features actually used
 */
class ThemeAudit extends Command
{
    protected $signature = 'dls:theme:audit
                            {theme : The theme slug or directory name}
                            {--json : Output as JSON}
                            {--fix : Suggest fixes for theme.json}';

    protected $description = 'Audit theme code and compare with declared permissions in theme.json';

    public function __construct(
        protected ThemePermissionService $permissionService,
        protected ?PatternRegistry $patternRegistry = null,
    ) {
        parent::__construct();
        $this->patternRegistry ??= PatternRegistry::createDefault();
    }

    public function handle(): int
    {
        $themeInput = $this->argument('theme');
        $themeDir = $this->resolveThemeDirectory($themeInput);
        $isJson = $this->option('json');

        if (! $themeDir) {
            if ($isJson) {
                $this->line(json_encode(['error' => "Theme not found: {$themeInput}"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error("Theme not found: {$themeInput}");
            }

            return Command::FAILURE;
        }

        $themeSlug = Str::kebab(basename($themeDir));
        $themeJsonPath = "{$themeDir}/theme.json";

        // Display header only when not in JSON mode
        if (! $isJson) {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('🔍 Auditing theme: '.basename($themeDir));
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
        }

        // Get declared permissions from theme.json
        $declaredPermissions = $this->getDeclaredPermissions($themeJsonPath);

        // Analyze code to detect actually used permissions
        $detectedPermissions = $this->analyzeThemeCode($themeDir);

        // Generate comparison results
        $auditResult = $this->comparePermissions($declaredPermissions, $detectedPermissions, $themeSlug);

        if ($isJson) {
            $this->outputJson($auditResult);
        } else {
            $this->outputReport($auditResult);
        }

        if ($this->option('fix') && ! empty($auditResult['mismatches'])) {
            $this->suggestFixes($themeJsonPath, $auditResult);
        }

        return empty($auditResult['mismatches']) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Resolve theme directory
     */
    protected function resolveThemeDirectory(string $input): ?string
    {
        // 1. Convert with Str::studly and search (case-sensitive strict match)
        $studlyName = Str::studly(str_replace('-', '_', $input));
        $path = $this->findDirectoryCaseSensitive(base_path('themes'), $studlyName);
        if ($path !== null) {
            return $path;
        }

        // 2. Search with input as-is (case-sensitive)
        $path = $this->findDirectoryCaseSensitive(base_path('themes'), $input);
        if ($path !== null) {
            return $path;
        }

        // 3. Scan theme directory for match
        $themesDir = base_path('themes');
        if (File::isDirectory($themesDir)) {
            foreach (File::directories($themesDir) as $dir) {
                // Match with kebab-case
                if (Str::kebab(basename($dir)) === $input) {
                    return $dir;
                }

                // Match with slug from theme.json
                $themeJson = $dir.'/theme.json';
                if (File::exists($themeJson)) {
                    $data = json_decode(File::get($themeJson), true);
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
     * Returns accurate directory name even on macOS case-insensitive filesystem
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
     * Get declared permissions from theme.json
     */
    protected function getDeclaredPermissions(string $themeJsonPath): array
    {
        if (! File::exists($themeJsonPath)) {
            return [];
        }

        $content = File::get($themeJsonPath);
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
     * Analyze theme code (PatternRegistry-based)
     */
    protected function analyzeThemeCode(string $themeDir): array
    {
        return $this->patternRegistry->scan($themeDir, 'theme');
    }

    /**
     * Compare declared permissions with detected permissions
     */
    protected function comparePermissions(array $declared, array $detected, ?string $themeSlug = null): array
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

            // Check if not empty when array
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
                    'recommendation' => "Set '{$permission}' to true",
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

        // Unified calculation of risk level and reason (delegated to service)
        $riskResult = $this->permissionService->calculateUnifiedRiskLevel($declared, $mismatches, $themeSlug);

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
     * Show fix suggestions
     */
    protected function suggestFixes(string $themeJsonPath, array $result): void
    {
        $this->newLine();
        $this->info('📝 Suggested fixes for theme.json:');
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
        $this->info("Run 'php artisan dls:theme:update-json ".basename(dirname($themeJsonPath))." --all' to add missing sections.");
    }
}
