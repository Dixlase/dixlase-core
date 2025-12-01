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

class PluginUpdateJson extends Command
{
    protected $signature = 'dls:plugin:update-json
                            {plugin : The plugin slug or directory name}
                            {--detect-provides : Auto-detect provides based on existing files}
                            {--add-permissions : Add permissions section if missing}
                            {--add-signing : Add signing section if missing}
                            {--add-providers : Add providers section if missing}
                            {--all : Add all missing sections}
                            {--dry-run : Show changes without writing}';

    protected $description = 'Update plugin.json with new format sections (permissions, signing, providers)';

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
        'members' => [
            'read' => false,
            'write' => false,
            'create' => false,
            'delete' => false,
        ],
        'mail' => [
            'send' => false,
            'bulk_send' => false,
        ],
        'content' => [
            'read_other_plugins' => [],
            'write_other_plugins' => [],
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
        $pluginInput = $this->argument('plugin');
        $pluginDir = $this->resolvePluginDirectory($pluginInput);

        if (!$pluginDir) {
            $this->error("Plugin not found: {$pluginInput}");
            return Command::FAILURE;
        }

        $pluginJsonPath = "{$pluginDir}/plugin.json";

        if (!File::exists($pluginJsonPath)) {
            $this->error("plugin.json not found in: {$pluginDir}");
            return Command::FAILURE;
        }

        $content = File::get($pluginJsonPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in plugin.json: " . json_last_error_msg());
            return Command::FAILURE;
        }

        $changes = [];
        $addAll = $this->option('all');

        // providers セクションの追加
        if (($addAll || $this->option('add-providers')) && !isset($data['providers'])) {
            $pluginDirName = basename($pluginDir);
            $data['providers'] = [
                "Plugins\\{$pluginDirName}\\App\\Providers\\{$pluginDirName}ServiceProvider"
            ];
            $changes[] = 'providers';
        }

        // permissions セクションの追加
        if (($addAll || $this->option('add-permissions')) && !isset($data['permissions'])) {
            $data['permissions'] = $this->defaultPermissions;
            $changes[] = 'permissions';
        }

        // signing セクションの追加
        if (($addAll || $this->option('add-signing')) && !isset($data['signing'])) {
            $data['signing'] = $this->defaultSigning;
            $changes[] = 'signing';
        }

        // provides の自動検出
        if ($this->option('detect-provides')) {
            $detected = $this->detectProvides($pluginDir);
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
            $this->info("No changes needed for: " . basename($pluginDir));
            return Command::SUCCESS;
        }

        // 変更内容を表示
        $this->info("Changes to be made for: " . basename($pluginDir));
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
        File::put($pluginJsonPath, $jsonContent);

        $this->info("✓ plugin.json updated successfully!");

        return Command::SUCCESS;
    }

    /**
     * プラグインディレクトリを解決
     */
    protected function resolvePluginDirectory(string $input): ?string
    {
        // StudlyCase形式で試す
        $studlyName = Str::studly(str_replace('-', '_', $input));
        $path = base_path("plugins/{$studlyName}");
        if (File::isDirectory($path)) {
            return $path;
        }

        // そのまま試す
        $path = base_path("plugins/{$input}");
        if (File::isDirectory($path)) {
            return $path;
        }

        // plugins ディレクトリ内を検索
        $pluginsDir = base_path('plugins');
        if (File::isDirectory($pluginsDir)) {
            $directories = File::directories($pluginsDir);
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
     * provides を自動検出
     */
    protected function detectProvides(string $pluginDir): array
    {
        $provides = [
            'admin_menu' => false,
            'front_routes' => false,
            'api_routes' => false,
            'settings_page' => false,
            'shortcodes' => false,
            'helper_functions' => false,
        ];

        // admin.php ルートの存在確認
        if (File::exists("{$pluginDir}/routes/admin.php")) {
            $content = File::get("{$pluginDir}/routes/admin.php");
            // ルートが定義されているか確認（空でないか）
            if (preg_match('/Route::(get|post|put|patch|delete|resource|group)/i', $content)) {
                $provides['admin_menu'] = true;
            }
        }

        // web.php ルートの存在確認
        if (File::exists("{$pluginDir}/routes/web.php")) {
            $content = File::get("{$pluginDir}/routes/web.php");
            if (preg_match('/Route::(get|post|put|patch|delete|resource|group)/i', $content)) {
                $provides['front_routes'] = true;
            }
        }

        // api.php ルートの存在確認
        if (File::exists("{$pluginDir}/routes/api.php")) {
            $content = File::get("{$pluginDir}/routes/api.php");
            if (preg_match('/Route::(get|post|put|patch|delete|resource|group)/i', $content)) {
                $provides['api_routes'] = true;
            }
        }

        // 設定ページの存在確認（config/admin.php または Settings コントローラ）
        if (File::exists("{$pluginDir}/config/admin.php") ||
            File::exists("{$pluginDir}/app/Http/Controllers/Admin/SettingsController.php")) {
            $provides['settings_page'] = true;
        }

        // ショートコードの存在確認
        $shortcodesDir = "{$pluginDir}/app/Shortcodes";
        if (File::isDirectory($shortcodesDir) && count(File::files($shortcodesDir)) > 0) {
            $provides['shortcodes'] = true;
        }

        // ヘルパー関数の存在確認
        $helpersDir = "{$pluginDir}/app/Helpers";
        if (File::isDirectory($helpersDir) && count(File::files($helpersDir)) > 0) {
            $provides['helper_functions'] = true;
        }

        return $provides;
    }
}
