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
use App\Services\Theme\ThemePermissionService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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
        'assets.custom_css' => [
            'files' => ['resources/src/css/*.css', 'resources/src/scss/*.scss', 'resources/assets/css/*.css'],
            'patterns' => [],
        ],
        'assets.custom_js' => [
            'files' => ['resources/src/js/*.js', 'resources/assets/js/*.js'],
            'patterns' => [],
        ],
        'assets.external_resources' => [
            'patterns' => [
                // 外部CDNやリソースの読み込み
                '/https?:\/\/[^\s\'"]+\.(js|css)/i',
                '/<script[^>]+src=[\'"]https?:\/\//i',
                '/<link[^>]+href=[\'"]https?:\/\//i',
                '/import\s+.*from\s+[\'"]https?:\/\//i',
            ],
        ],
        'system.register_shortcodes' => [
            'files' => ['app/Shortcodes/*.php'],
            'patterns' => [
                '/ThemeHelper::registerShortcode/i',
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

    public function __construct(
        protected ThemePermissionService $permissionService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $themeInput = $this->argument('theme');
        $themeDir = $this->resolveThemeDirectory($themeInput);
        $isJson = $this->option('json');

        if (!$themeDir) {
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
        if (!$isJson) {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("🔍 Auditing theme: " . basename($themeDir));
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

        if ($this->option('fix') && !empty($auditResult['mismatches'])) {
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
        if (!File::exists($themeJsonPath)) {
            return [];
        }

        $content = File::get($themeJsonPath);
        $data = json_decode($content, true);

        return $data['permissions'] ?? [];
    }

    /**
     * テーマコードを解析
     */
    protected function analyzeThemeCode(string $themeDir): array
    {
        $detected = [];
        $evidence = [];

        foreach ($this->detectionPatterns as $permission => $config) {
            $found = false;
            $foundEvidence = [];

            // 特定ファイルパターンの存在確認
            if (isset($config['files'])) {
                foreach ($config['files'] as $filePattern) {
                    $files = $this->globRecursive("{$themeDir}/{$filePattern}");
                    if (!empty($files)) {
                        $found = true;
                        foreach ($files as $file) {
                            $foundEvidence[] = [
                                'type' => 'file_exists',
                                'file' => str_replace($themeDir . '/', '', $file),
                            ];
                        }
                    }
                }
            }

            // パターンマッチング
            if (isset($config['patterns']) && !empty($config['patterns'])) {
                $phpFiles = $this->getCodeFiles($themeDir);
                foreach ($phpFiles as $file) {
                    $content = File::get($file);
                    foreach ($config['patterns'] as $pattern) {
                        if (preg_match($pattern, $content, $matches)) {
                            $found = true;
                            $lineNumber = $this->findLineNumber($content, $matches[0]);
                            $foundEvidence[] = [
                                'type' => 'pattern_match',
                                'file' => str_replace($themeDir . '/', '', $file),
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
     * コードファイル一覧を取得（PHP, JS, Blade）
     */
    protected function getCodeFiles(string $dir): array
    {
        $files = [];
        
        if (!File::isDirectory($dir)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $extensions = ['php', 'js', 'blade.php'];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                $ext = $file->getExtension();
                
                // blade.phpファイルの特別処理
                if (str_ends_with($filename, '.blade.php') || in_array($ext, $extensions)) {
                    $files[] = $file->getPathname();
                }
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
    protected function suggestFixes(string $themeJsonPath, array $result): void
    {
        $this->newLine();
        $this->info("📝 Suggested fixes for theme.json:");
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
        $this->info("Run 'php artisan dls:theme:update-json " . basename(dirname($themeJsonPath)) . " --all' to add missing sections.");
    }
}
