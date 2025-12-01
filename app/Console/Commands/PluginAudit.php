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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\Plugin\PluginPermissionService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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
                            {--fix : Suggest fixes for plugin.json}';

    protected $description = 'Audit plugin code and compare with declared permissions in plugin.json';

    /**
     * 検出パターン定義
     */
    protected array $detectionPatterns = [
        'database.own_tables' => [
            'files' => ['database/migrations/*.php'],
            'patterns' => [
                '/Schema::(create|table)\s*\(\s*[\'"](\w+)[\'"]/i',
            ],
        ],
        'database.core_tables' => [
            'patterns' => [
                // コアテーブルへのアクセス（モデル経由）
                '/\\\\App\\\\Models\\\\(User|Member|Plugin|Media|Setting|BaseSetting|MemberSetting|SecuritySetting)/i',
                // 直接テーブル名指定
                '/DB::table\s*\(\s*[\'"](users|members|plugins|media|settings|base_settings|member_settings|security_settings)[\'"]\)/i',
            ],
        ],
        'storage.own_directory' => [
            'patterns' => [
                '/Storage::(put|get|delete|exists|disk)/i',
                '/File::(put|get|delete|exists|copy|move)/i',
            ],
        ],
        'storage.public_uploads' => [
            'patterns' => [
                '/Storage::disk\s*\(\s*[\'"]public[\'"]\)/i',
                '/->store\s*\(\s*[\'"]uploads/i',
                '/public_path\s*\(\s*[\'"]uploads/i',
            ],
        ],
        'storage.temp_files' => [
            'patterns' => [
                '/tempnam\s*\(/i',
                '/sys_get_temp_dir\s*\(/i',
                '/Storage::disk\s*\(\s*[\'"]temp[\'"]\)/i',
            ],
        ],
        'settings.read_core' => [
            'patterns' => [
                '/BaseSetting::(get|find|first|all)/i',
                '/MemberSetting::(get|find|first|all)/i',
                '/SecuritySetting::(get|find|first|all)/i',
                '/config\s*\(\s*[\'"]app\./i',
                '/config\s*\(\s*[\'"]mail\./i',
                '/config\s*\(\s*[\'"]database\./i',
            ],
        ],
        'members.read' => [
            'patterns' => [
                '/\\\\App\\\\Models\\\\Member::(get|find|first|all|where|query)/i',
                '/Member::(get|find|first|all|where|query)/i',
            ],
        ],
        'members.write' => [
            'patterns' => [
                '/\\\\App\\\\Models\\\\Member[^;]*->(save|update)\s*\(/i',
                '/Member::(update|create)\s*\(/i',
            ],
        ],
        'members.create' => [
            'patterns' => [
                '/Member::create\s*\(/i',
                '/new\s+Member\s*\(/i',
                '/Member::(firstOrCreate|updateOrCreate)/i',
            ],
        ],
        'members.delete' => [
            'patterns' => [
                '/Member::(delete|destroy)\s*\(/i',
                '/->delete\s*\(\s*\).*Member/i',
            ],
        ],
        'mail.send' => [
            'patterns' => [
                '/Mail::(send|to|queue|later)/i',
                '/Notification::(send|route)/i',
                '/->notify\s*\(/i',
                '/Mailable/i',
            ],
        ],
        'mail.bulk_send' => [
            'patterns' => [
                '/Mail::queue\s*\(/i',
                '/Mail::later\s*\(/i',
                '/->each\s*\(\s*function.*Mail::/is',
                '/foreach.*Mail::(send|to)/is',
            ],
        ],
        'system.register_shortcodes' => [
            'files' => ['app/Shortcodes/*.php'],
            'patterns' => [
                '/PluginHelper::registerShortcode/i',
                '/app\s*\(\s*[\'"]shortcode[\'"]\s*\)/i',
            ],
        ],
        'system.register_middleware' => [
            'patterns' => [
                '/\$this->app\[.*Router.*\]->pushMiddleware/i',
                '/Route::middleware/i',
                '/->middleware\s*\(/i',
            ],
            'files' => ['app/Http/Middleware/*.php'],
        ],
        'system.register_commands' => [
            'files' => ['app/Console/Commands/*.php', 'app/Console/*.php'],
            'patterns' => [
                '/\$this->commands\s*\(/i',
                '/Artisan::command/i',
            ],
        ],
        'system.register_blade_directives' => [
            'patterns' => [
                '/Blade::directive\s*\(/i',
                '/Blade::if\s*\(/i',
                '/Blade::component\s*\(/i',
            ],
        ],
        'system.modify_routes' => [
            'patterns' => [
                '/Route::macro/i',
                '/Router::macro/i',
            ],
        ],
    ];

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
        protected PluginPermissionService $permissionService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $pluginInput = $this->argument('plugin');
        $pluginDir = $this->resolvePluginDirectory($pluginInput);

        if (!$pluginDir) {
            $this->error("Plugin not found: {$pluginInput}");
            return Command::FAILURE;
        }

        $pluginSlug = Str::kebab(basename($pluginDir));
        $pluginJsonPath = "{$pluginDir}/plugin.json";

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("🔍 Auditing plugin: " . basename($pluginDir));
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // plugin.json から宣言された権限を取得
        $declaredPermissions = $this->getDeclaredPermissions($pluginJsonPath);
        
        // コードを解析して実際に使用されている権限を検出
        $detectedPermissions = $this->analyzePluginCode($pluginDir);

        // 比較結果を生成
        $auditResult = $this->comparePermissions($declaredPermissions, $detectedPermissions);

        if ($this->option('json')) {
            $this->outputJson($auditResult);
        } else {
            $this->outputReport($auditResult);
        }

        if ($this->option('fix') && !empty($auditResult['mismatches'])) {
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
        if (!File::exists($pluginJsonPath)) {
            return [];
        }

        $content = File::get($pluginJsonPath);
        $data = json_decode($content, true);

        return $data['permissions'] ?? [];
    }

    /**
     * プラグインコードを解析
     */
    protected function analyzePluginCode(string $pluginDir): array
    {
        $detected = [];
        $evidence = [];

        foreach ($this->detectionPatterns as $permission => $config) {
            $found = false;
            $foundEvidence = [];

            // 特定ファイルパターンの存在確認
            if (isset($config['files'])) {
                foreach ($config['files'] as $filePattern) {
                    $files = $this->globRecursive("{$pluginDir}/{$filePattern}");
                    if (!empty($files)) {
                        $found = true;
                        foreach ($files as $file) {
                            $foundEvidence[] = [
                                'type' => 'file_exists',
                                'file' => str_replace($pluginDir . '/', '', $file),
                            ];
                        }
                    }
                }
            }

            // パターンマッチング
            if (isset($config['patterns'])) {
                $phpFiles = $this->getPhpFiles($pluginDir);
                foreach ($phpFiles as $file) {
                    $content = File::get($file);
                    foreach ($config['patterns'] as $pattern) {
                        if (preg_match($pattern, $content, $matches)) {
                            $found = true;
                            $lineNumber = $this->findLineNumber($content, $matches[0]);
                            $foundEvidence[] = [
                                'type' => 'pattern_match',
                                'file' => str_replace($pluginDir . '/', '', $file),
                                'line' => $lineNumber,
                                'match' => trim($matches[0]),
                            ];
                        }
                    }
                }
            }

            $detected[$permission] = $found;
            if (!empty($foundEvidence)) {
                $evidence[$permission] = $foundEvidence;
            }
        }

        return [
            'permissions' => $detected,
            'evidence' => $evidence,
        ];
    }

    /**
     * PHPファイル一覧を取得
     */
    protected function getPhpFiles(string $dir): array
    {
        $files = [];
        
        if (!File::isDirectory($dir)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * glob パターンを再帰的に展開
     */
    protected function globRecursive(string $pattern): array
    {
        $files = glob($pattern);
        
        // ワイルドカードディレクトリの処理
        $dir = dirname($pattern);
        $filename = basename($pattern);
        
        if (File::isDirectory($dir)) {
            foreach (File::directories($dir) as $subdir) {
                $files = array_merge($files, $this->globRecursive("{$subdir}/{$filename}"));
            }
        }

        return $files ?: [];
    }

    /**
     * マッチした文字列の行番号を取得
     */
    protected function findLineNumber(string $content, string $match): int
    {
        $pos = strpos($content, $match);
        if ($pos === false) {
            return 0;
        }
        return substr_count(substr($content, 0, $pos), "\n") + 1;
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
                $declaredValue = !empty($declaredValue);
            }

            $isDeclared = (bool) $declaredValue;

            if ($isDetected && !$isDeclared) {
                $mismatches[] = [
                    'permission' => $permission,
                    'declared' => $isDeclared,
                    'detected' => $isDetected,
                    'type' => 'undeclared_usage',
                    'evidence' => $evidence[$permission] ?? [],
                    'recommendation' => "Set '{$permission}' to true",
                ];
            } elseif (!$isDetected && $isDeclared) {
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

        return [
            'mismatches' => $mismatches,
            'matches' => $matches,
            'total_checked' => count($detectedPerms),
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

        $this->warn("⚠️  Permission Mismatches Found:");
        $this->newLine();

        foreach ($mismatches as $mismatch) {
            $this->line("<fg=yellow>[{$mismatch['permission']}]</>");
            $this->line("  Declared: " . ($mismatch['declared'] ? '<fg=green>true</>' : '<fg=red>false</>'));
            $this->line("  Detected: " . ($mismatch['detected'] ? '<fg=green>true</>' : '<fg=red>false</>'));
            
            if (!empty($mismatch['evidence'])) {
                $this->line("  Evidence:");
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

        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("✅ Matching: <fg=green>" . count($matches) . "</>");
        $this->line("⚠️  Mismatches: <fg=yellow>" . count($mismatches) . "</>");
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
        $this->info("📝 Suggested fixes for plugin.json:");
        $this->newLine();

        foreach ($result['mismatches'] as $mismatch) {
            if ($mismatch['type'] === 'undeclared_usage') {
                $parts = explode('.', $mismatch['permission']);
                $this->line("  \"{$parts[0]}\": {");
                $this->line("    \"{$parts[1]}\": <fg=green>true</>  // Currently: false");
                $this->line("  }");
            }
        }

        $this->newLine();
        $this->info("Run 'php artisan dls:plugin:update-json " . basename(dirname($pluginJsonPath)) . " --all' to add missing sections.");
    }
}
