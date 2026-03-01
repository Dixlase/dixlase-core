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

namespace App\Console\Commands;

use App\Services\Plugin\Scanning\PatternRegistry;
use App\Services\Theme\ThemePermissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * テーマ権限監査コマンド
 *
 * テーマのコードを解析し、theme.json で宣言された権限と
 * 実際に使用されている機能を照合します。
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

        // JSONモードでない場合のみヘッダーを表示
        if (! $isJson) {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('🔍 Auditing theme: '.basename($themeDir));
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
        }

        // theme.json から宣言された権限を取得
        $declaredPermissions = $this->getDeclaredPermissions($themeJsonPath);

        // コードを解析して実際に使用されている権限を検出
        $detectedPermissions = $this->analyzeThemeCode($themeDir);

        // 比較結果を生成
        $auditResult = $this->comparePermissions($declaredPermissions, $detectedPermissions);

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
     * テーマディレクトリを解決
     */
    protected function resolveThemeDirectory(string $input): ?string
    {
        $studlyName = Str::studly(str_replace('-', '_', $input));
        $path = base_path("themes/{$studlyName}");
        if (File::isDirectory($path)) {
            return $path;
        }

        $path = base_path("themes/{$input}");
        if (File::isDirectory($path)) {
            return $path;
        }

        $themesDir = base_path('themes');
        if (File::isDirectory($themesDir)) {
            foreach (File::directories($themesDir) as $dir) {
                if (Str::kebab(basename($dir)) === $input) {
                    return $dir;
                }
            }
        }

        return null;
    }

    /**
     * theme.json から宣言された権限を取得
     */
    protected function getDeclaredPermissions(string $themeJsonPath): array
    {
        if (! File::exists($themeJsonPath)) {
            return [];
        }

        $content = File::get($themeJsonPath);
        $data = json_decode($content, true);

        return $data['permissions'] ?? [];
    }

    /**
     * テーマコードを解析（PatternRegistryベース）
     */
    protected function analyzeThemeCode(string $themeDir): array
    {
        return $this->patternRegistry->scan($themeDir, 'theme');
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
            'settings.read_core' => 'コア設定の読み取り',
            'system.modify_routes' => 'ルートの変更',
        ];

        // 中リスク権限
        $mediumRiskPermissions = [
            'storage.public_uploads' => 'パブリックアップロード',
            'assets.external_resources' => '外部リソースの読み込み',
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
