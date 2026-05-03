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

use App\Models\PluginAudit as PluginAuditModel;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\Scanning\PatternRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * プラグイン権限監査コマンド
 *
 * プラグインのコードを解析し、plugin.json で宣言された権限と
 * 実際に使用されている機能を照合します。
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
     * コアテーブル一覧
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

        // JSONモードでない場合のみヘッダーを表示
        if (! $isJson) {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('🔍 Auditing plugin: '.basename($pluginDir));
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
        }

        // plugin.json から宣言された権限を取得
        $declaredPermissions = $this->getDeclaredPermissions($pluginJsonPath);

        // plugin.json から宣言された capabilities を取得（情報表示用）
        $declaredCapabilities = $this->getDeclaredCapabilities($pluginJsonPath);

        // コードを解析して実際に使用されている権限を検出
        $detectedPermissions = $this->analyzePluginCode($pluginDir);

        // 比較結果を生成
        $auditResult = $this->comparePermissions($declaredPermissions, $detectedPermissions);
        $auditResult['capabilities'] = $declaredCapabilities;

        // 結果をDBに永続化（JSONモード時はコントローラーが保存するためスキップ）
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

        // 健全性スコアの計算（オプション）
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
     * プラグインディレクトリを解決
     */
    protected function resolvePluginDirectory(string $input): ?string
    {
        // 1. Str::studly で変換して探す（case-sensitive な厳密マッチ）
        $studlyName = Str::studly(str_replace('-', '_', $input));
        $path = $this->findDirectoryCaseSensitive(base_path('plugins'), $studlyName);
        if ($path !== null) {
            return $path;
        }

        // 2. 入力そのままで探す（case-sensitive）
        $path = $this->findDirectoryCaseSensitive(base_path('plugins'), $input);
        if ($path !== null) {
            return $path;
        }

        // 3. DB の directory カラムから探す（インストール済みプラグイン）
        $plugin = \App\Models\Plugin::where('slug', $input)->first();
        if ($plugin && $plugin->directory) {
            $path = $this->findDirectoryCaseSensitive(base_path('plugins'), $plugin->directory);
            if ($path !== null) {
                return $path;
            }
        }

        // 4. プラグインディレクトリを走査して plugin.json の slug でマッチ
        $pluginsDir = base_path('plugins');
        if (File::isDirectory($pluginsDir)) {
            foreach (File::directories($pluginsDir) as $dir) {
                // kebab-case でのマッチ
                if (Str::kebab(basename($dir)) === $input) {
                    return $dir;
                }

                // plugin.json の slug でのマッチ
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
     * ケースセンシティブにディレクトリを検索する。
     *
     * macOS のケース非依存ファイルシステムでも正確なディレクトリ名を返す。
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
     * plugin.json から宣言された権限を取得
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
     * plugin.json から宣言された capabilities を取得
     *
     * capabilities はプラグインが提供する機能の宣言（例: ["seo", "backup"]）。
     * コアや他プラグインからの機能検出に使われる情報メタデータで、
     * 未宣言でも監査上のエラーにはならない。
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
     * プラグインコードを解析（PatternRegistryベース）
     */
    protected function analyzePluginCode(string $pluginDir): array
    {
        return $this->patternRegistry->scan($pluginDir, 'plugin');
    }

    /**
     * 宣言された権限と検出された権限を比較
     */
    protected function comparePermissions(array $declared, array $detected): array
    {
        $mismatches = [];
        $matches = [];
        $detectedPerms = $detected['permissions'] ?? [];
        $evidence = $detected['evidence'] ?? [];

        foreach ($detectedPerms as $permission => $isDetected) {
            // ドット記法を配列アクセスに変換
            $parts = explode('.', $permission);
            $declaredValue = $declared;
            foreach ($parts as $part) {
                $declaredValue = $declaredValue[$part] ?? false;
            }

            // 配列の場合は空でないかチェック
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

        // リスクレベルと理由を統一計算（サービスに委譲）
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
     * レポートを出力
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
     * JSON形式で出力
     */
    protected function outputJson(array $result): void
    {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 修正提案を表示
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
}
