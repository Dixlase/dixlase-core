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
        'base_settings', 'member_settings', 'security_settings',
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

        // コードを解析して実際に使用されている権限を検出
        $detectedPermissions = $this->analyzePluginCode($pluginDir);

        // 比較結果を生成
        $auditResult = $this->comparePermissions($declaredPermissions, $detectedPermissions);

        // 結果をDBに永続化
        $saveData = [
            'has_mismatches' => ! empty($auditResult['mismatches']),
            'mismatches' => $auditResult['mismatches'] ?? [],
            'matches_count' => count($auditResult['matches'] ?? []),
            'total_checked' => $auditResult['total_checked'] ?? 0,
            'risk_level' => $auditResult['risk_level'] ?? null,
            'risk_reasons' => $auditResult['risk_reasons'] ?? [],
        ];

        PluginAuditModel::saveAuditResult($pluginSlug, $saveData);

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
        $studlyName = Str::studly(str_replace('-', '_', $input));
        $path = base_path("plugins/{$studlyName}");
        if (File::isDirectory($path)) {
            return $path;
        }

        $path = base_path("plugins/{$input}");
        if (File::isDirectory($path)) {
            return $path;
        }

        $pluginsDir = base_path('plugins');
        if (File::isDirectory($pluginsDir)) {
            foreach (File::directories($pluginsDir) as $dir) {
                if (Str::kebab(basename($dir)) === $input) {
                    return $dir;
                }
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

        return $data['permissions'] ?? [];
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

        // リスクレベルと理由を計算
        $riskResult = $this->calculateRiskLevel($detectedPerms, $mismatches);

        return [
            'mismatches' => $mismatches,
            'matches' => $matches,
            'total_checked' => count($detectedPerms),
            'risk_level' => $riskResult['level'],
            'risk_reasons' => $riskResult['reasons'],
        ];
    }

    /**
     * リスクレベルを計算
     *
     * @param  array  $detectedPerms  検出された権限
     * @param  array  $mismatches  不一致リスト
     * @return array ['level' => string, 'reasons' => array]
     */
    protected function calculateRiskLevel(array $detectedPerms, array $mismatches): array
    {
        $reasons = [];
        $level = 'low'; // デフォルトは良好

        // 高リスク権限（使用されている場合）
        $highRiskPermissions = [
            'database.core_tables' => 'コアテーブルへのアクセス',
            'members.write' => 'メンバー情報の書き込み',
            'members.delete' => 'メンバーの削除',
            'system.modify_routes' => 'ルートの変更',
        ];

        // 中リスク権限
        $mediumRiskPermissions = [
            'storage.public_uploads' => 'パブリックアップロード',
            'settings.read_core' => 'コア設定の読み取り',
            'mail.bulk_send' => '一括メール送信',
            'system.register_middleware' => 'ミドルウェアの登録',
            'system.register_blade_directives' => 'Blade指令の登録',
        ];

        // 高リスク権限のチェック
        foreach ($highRiskPermissions as $perm => $description) {
            if ($detectedPerms[$perm] ?? false) {
                $level = 'high';
                $reasons[] = $description;
            }
        }

        // 中リスク権限のチェック（まだhighでない場合のみ）
        if ($level !== 'high') {
            foreach ($mediumRiskPermissions as $perm => $description) {
                if ($detectedPerms[$perm] ?? false) {
                    $level = 'medium';
                    $reasons[] = $description;
                }
            }
        }

        // 未宣言の権限使用がある場合はリスクを上げる
        $undeclaredCount = count(array_filter($mismatches, fn ($m) => $m['type'] === 'undeclared_usage'));
        if ($undeclaredCount > 0) {
            if ($level === 'low') {
                $level = 'medium';
            }
            $reasons[] = "未宣言の権限使用: {$undeclaredCount}件";
        }

        return [
            'level' => $level,
            'reasons' => $reasons,
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
