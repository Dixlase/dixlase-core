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

class ThemeUpdateJson extends Command
{
    protected $signature = 'dls:theme:update-json
                            {theme : The theme slug or directory name}
                            {--detect-provides : Auto-detect provides based on existing files}
                            {--add-permissions : Add permissions section if missing}
                            {--add-signing : Add signing section if missing}
                            {--all : Add all missing sections}
                            {--dry-run : Show changes without writing}';

    protected $description = 'Update theme.json with new format sections (permissions, signing)';

    /**
     * デフォルトの権限設定
     */
    protected array $defaultPermissions = [
        'database' => [
            'own_tables' => false,
            'core_tables' => [],
        ],
        'storage' => [
            'own_directory' => false,
            'public_uploads' => false,
            'temp_files' => false,
        ],
        'settings' => [
            'read_core' => false,
            'write_own' => true,
        ],
        'assets' => [
            'custom_css' => true,
            'custom_js' => true,
            'external_resources' => false,
        ],
        'system' => [
            'register_shortcodes' => false,
            'register_middleware' => false,
            'register_commands' => false,
            'register_blade_directives' => false,
            'modify_routes' => false,
        ],
    ];

    /**
     * デフォルトの署名設定
     */
    protected array $defaultSigning = [
        'algo' => 'ed25519',
        'key_id' => null,
    ];

    public function handle(): int
    {
        $themeInput = $this->argument('theme');
        $themeDir = $this->resolveThemeDirectory($themeInput);

        if (!$themeDir) {
            $this->error("Theme not found: {$themeInput}");
            return Command::FAILURE;
        }

        $themeJsonPath = "{$themeDir}/theme.json";

        if (!File::exists($themeJsonPath)) {
            $this->error("theme.json not found in: {$themeDir}");
            return Command::FAILURE;
        }

        $content = File::get($themeJsonPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in theme.json: " . json_last_error_msg());
            return Command::FAILURE;
        }

        $changes = [];
        $addAll = $this->option('all');

        // permissions セクションの追加
        if (($addAll || $this->option('add-permissions')) && !isset($data['permissions'])) {
            // 自動検出を試みる
            $detectedPermissions = $this->detectPermissions($themeDir);
            $data['permissions'] = array_replace_recursive($this->defaultPermissions, $detectedPermissions);
            $changes[] = 'permissions';
        }

        // signing セクションの追加
        if (($addAll || $this->option('add-signing')) && !isset($data['signing'])) {
            $data['signing'] = $this->defaultSigning;
            $changes[] = 'signing';
        }

        // provides の自動検出
        if ($this->option('detect-provides')) {
            $detected = $this->detectProvides($themeDir);
            if (!isset($data['provides'])) {
                $data['provides'] = $detected;
                $changes[] = 'provides (detected)';
            } else {
                // 既存の provides とマージ
                $merged = array_merge($data['provides'], $detected);
                if ($merged !== $data['provides']) {
                    $data['provides'] = $merged;
                    $changes[] = 'provides (updated)';
                }
            }
        }

        if (empty($changes)) {
            $this->info("No changes needed for: " . basename($themeDir));
            return Command::SUCCESS;
        }

        // 変更内容を表示
        $this->info("Changes to be made for: " . basename($themeDir));
        foreach ($changes as $change) {
            $this->line("  + {$change}");
        }

        if ($this->option('dry-run')) {
            $this->warn("Dry run - no changes written");
            $this->line("\nPreview:");
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return Command::SUCCESS;
        }

        // ファイルに書き込み
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        File::put($themeJsonPath, $jsonContent);

        $this->info("✓ theme.json updated successfully!");

        return Command::SUCCESS;
    }

    /**
     * テーマディレクトリを解決
     */
    protected function resolveThemeDirectory(string $input): ?string
    {
        // StudlyCase形式で試す
        $studlyName = Str::studly(str_replace('-', '_', $input));
        $path = base_path("themes/{$studlyName}");
        if (File::isDirectory($path)) {
            return $path;
        }

        // そのまま試す
        $path = base_path("themes/{$input}");
        if (File::isDirectory($path)) {
            return $path;
        }

        // themes ディレクトリ内を検索
        $themesDir = base_path('themes');
        if (File::isDirectory($themesDir)) {
            $directories = File::directories($themesDir);
            foreach ($directories as $dir) {
                $dirName = basename($dir);
                $slug = Str::kebab($dirName);
                if ($slug === $input || $dirName === $input) {
                    return $dir;
                }
            }
        }

        return null;
    }

    /**
     * 権限を自動検出
     */
    protected function detectPermissions(string $themeDir): array
    {
        $permissions = [];

        // データベース: マイグレーションファイルの存在確認
        $migrationsDir = "{$themeDir}/database/migrations";
        if (File::isDirectory($migrationsDir) && count(File::files($migrationsDir)) > 0) {
            $permissions['database']['own_tables'] = true;
        }

        // ストレージ: 専用ディレクトリの使用確認
        if (File::isDirectory("{$themeDir}/resources/assets") || 
            File::isDirectory("{$themeDir}/storage")) {
            $permissions['storage']['own_directory'] = true;
        }

        // 設定: コア設定読み取りの確認
        $phpFiles = $this->getPhpFiles($themeDir);
        foreach ($phpFiles as $file) {
            $content = File::get($file);
            
            // config() でコア設定を読み取っているか
            if (preg_match('/config\s*\(\s*[\'"](?:app|mail|database|admin)\./i', $content)) {
                $permissions['settings']['read_core'] = true;
            }
            
            // 外部リソースの読み込み
            if (preg_match('/https?:\/\/[^\s\'"]+\.(js|css)/i', $content) ||
                preg_match('/<script[^>]+src=[\'"]https?:\/\//i', $content) ||
                preg_match('/<link[^>]+href=[\'"]https?:\/\//i', $content)) {
                $permissions['assets']['external_resources'] = true;
            }
        }

        // アセット: CSS/JSファイルの存在確認
        $cssFiles = glob("{$themeDir}/resources/src/css/*.css") ?: [];
        $scssFiles = glob("{$themeDir}/resources/src/scss/*.scss") ?: [];
        if (!empty($cssFiles) || !empty($scssFiles)) {
            $permissions['assets']['custom_css'] = true;
        }

        $jsFiles = glob("{$themeDir}/resources/src/js/*.js") ?: [];
        if (!empty($jsFiles)) {
            $permissions['assets']['custom_js'] = true;
        }

        // システム: ルート変更の確認
        if (File::exists("{$themeDir}/routes/admin.php")) {
            $content = File::get("{$themeDir}/routes/admin.php");
            if (preg_match('/Route::(get|post|put|patch|delete|resource|group)/i', $content)) {
                $permissions['system']['modify_routes'] = true;
            }
        }

        // ミドルウェアの存在確認
        $middlewareDir = "{$themeDir}/app/Http/Middleware";
        if (File::isDirectory($middlewareDir) && count(File::files($middlewareDir)) > 0) {
            $permissions['system']['register_middleware'] = true;
        }

        // コマンドの存在確認
        $commandsDir = "{$themeDir}/app/Console/Commands";
        if (File::isDirectory($commandsDir) && count(File::files($commandsDir)) > 0) {
            $permissions['system']['register_commands'] = true;
        }

        // ショートコードの存在確認
        $shortcodesDir = "{$themeDir}/app/Shortcodes";
        if (File::isDirectory($shortcodesDir) && count(File::files($shortcodesDir)) > 0) {
            $permissions['system']['register_shortcodes'] = true;
        }

        return $permissions;
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

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * provides を自動検出
     */
    protected function detectProvides(string $themeDir): array
    {
        $provides = [
            'admin_menu' => false,
            'front_routes' => false,
            'settings_page' => false,
            'shortcodes' => false,
        ];

        // admin.php ルートの存在確認
        if (File::exists("{$themeDir}/routes/admin.php")) {
            $content = File::get("{$themeDir}/routes/admin.php");
            if (preg_match('/Route::(get|post|put|patch|delete|resource|group)/i', $content)) {
                $provides['admin_menu'] = true;
            }
        }

        // web.php ルートの存在確認
        if (File::exists("{$themeDir}/routes/web.php")) {
            $content = File::get("{$themeDir}/routes/web.php");
            if (preg_match('/Route::(get|post|put|patch|delete|resource|group)/i', $content)) {
                $provides['front_routes'] = true;
            }
        }

        // 設定ページの存在確認
        if (File::exists("{$themeDir}/config/admin.php") ||
            File::isDirectory("{$themeDir}/app/Http/Controllers/Admin/Settings")) {
            $provides['settings_page'] = true;
        }

        // ショートコードの存在確認
        $shortcodesDir = "{$themeDir}/app/Shortcodes";
        if (File::isDirectory($shortcodesDir) && count(File::files($shortcodesDir)) > 0) {
            $provides['shortcodes'] = true;
        }

        return $provides;
    }
}
