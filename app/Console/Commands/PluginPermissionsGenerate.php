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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * プラグイン権限自動生成コマンド
 *
 * プラグインのコードをスキャンし、permissions と declares セクションを
 * 自動生成または更新します。
 */
class PluginPermissionsGenerate extends Command
{
    protected $signature = 'dls:plugin:permissions
                            {plugin : The plugin slug or directory name}
                            {--scan : Scan code to detect actual permissions}
                            {--update : Update plugin.json with generated permissions}
                            {--json : Output as JSON}';

    protected $description = 'Generate or update permissions and declares sections in plugin.json';

    public function __construct(
        protected ?PatternRegistry $patternRegistry = null,
    ) {
        parent::__construct();
        $this->patternRegistry ??= PatternRegistry::createDefault();
    }

    public function handle(): int
    {
        $pluginInput = $this->argument('plugin');
        $pluginDir = $this->resolvePluginDirectory($pluginInput);

        if (! $pluginDir) {
            $this->error("Plugin not found: {$pluginInput}");

            return Command::FAILURE;
        }

        $pluginJsonPath = "{$pluginDir}/plugin.json";
        if (! File::exists($pluginJsonPath)) {
            $this->error("plugin.json not found in: {$pluginDir}");

            return Command::FAILURE;
        }

        $data = json_decode(File::get($pluginJsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid plugin.json: '.json_last_error_msg());

            return Command::FAILURE;
        }

        // declares の生成
        $declares = $this->generateDeclares($pluginDir);

        // permissions の生成（--scan 指定時）
        $permissions = null;
        if ($this->option('scan')) {
            $scanResult = $this->patternRegistry->scan($pluginDir, 'plugin');
            $permissions = $this->buildPermissionsFromScan($scanResult, $data['permissions'] ?? []);
        }

        if ($this->option('json')) {
            $output = ['declares' => $declares];
            if ($permissions !== null) {
                $output['permissions'] = $permissions;
            }
            $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        }

        // レポート表示
        $this->info('Plugin: '.basename($pluginDir));
        $this->newLine();

        $this->outputDeclares($declares, $data['declares'] ?? null);

        if ($permissions !== null) {
            $this->newLine();
            $this->outputPermissions($permissions, $data['permissions'] ?? []);
        }

        // --update 指定時は plugin.json を更新
        if ($this->option('update')) {
            $data['declares'] = $declares;
            if ($permissions !== null) {
                $data['permissions'] = $permissions;
            }

            File::put(
                $pluginJsonPath,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
            );

            $this->newLine();
            $this->info('plugin.json updated successfully.');
        }

        return Command::SUCCESS;
    }

    /**
     * declares セクションを自動生成
     */
    protected function generateDeclares(string $pluginDir): array
    {
        return [
            'configs' => [
                'roles' => File::exists("{$pluginDir}/config/admin/roles.php"),
                'database_cleanup' => File::exists("{$pluginDir}/config/admin/database-cleanup.php"),
                'navigation' => File::exists("{$pluginDir}/config/admin/navigation.php"),
            ],
            'contracts' => $this->detectContracts($pluginDir),
            'migrations' => File::isDirectory("{$pluginDir}/database/migrations")
                && count(File::files("{$pluginDir}/database/migrations")) > 0,
            'commands' => File::isDirectory("{$pluginDir}/app/Console/Commands")
                && count(File::files("{$pluginDir}/app/Console/Commands")) > 0,
            'middleware' => File::isDirectory("{$pluginDir}/app/Http/Middleware")
                && count(File::files("{$pluginDir}/app/Http/Middleware")) > 0,
        ];
    }

    /**
     * 実装しているコントラクトを検出
     *
     * @return array<string>
     */
    protected function detectContracts(string $pluginDir): array
    {
        $contracts = [];
        $phpFiles = $this->getPhpFiles($pluginDir);

        foreach ($phpFiles as $file) {
            $content = File::get($file);

            // implements App\Contracts\... パターンを検出
            if (preg_match_all('/implements\s+.*?\\\\Contracts\\\\([A-Za-z\\\\]+Interface)/m', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    // use文からFQCNを解決
                    if (preg_match_all('/use\s+(App\\\\Contracts\\\\[A-Za-z\\\\]+Interface)\s*;/', $content, $useMatches)) {
                        foreach ($useMatches[1] as $contract) {
                            if (! in_array($contract, $contracts, true)) {
                                $contracts[] = $contract;
                            }
                        }
                    }
                }
            }
        }

        sort($contracts);

        return $contracts;
    }

    /**
     * スキャン結果から permissions を構築
     */
    protected function buildPermissionsFromScan(array $scanResult, array $existingPermissions): array
    {
        $detectedPerms = $scanResult['permissions'] ?? [];
        $defaultStructure = [
            'database' => ['own_tables' => false, 'core_tables' => []],
            'storage' => ['own_directory' => false, 'public_uploads' => false, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => false],
            'members' => ['read' => false, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => [], 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        // 既存の permissions をベースにする
        $permissions = array_replace_recursive($defaultStructure, array_diff_key(
            $existingPermissions,
            ['_optional' => true, '_notes' => true]
        ));

        // スキャン結果を適用
        foreach ($detectedPerms as $key => $detected) {
            $parts = explode('.', $key);
            if (count($parts) === 2 && isset($permissions[$parts[0]])) {
                if ($detected) {
                    $permissions[$parts[0]][$parts[1]] = true;
                }
            }
        }

        // _optional と _notes を保持
        if (isset($existingPermissions['_optional'])) {
            $permissions['_optional'] = $existingPermissions['_optional'];
        } else {
            $permissions['_optional'] = [];
        }

        if (isset($existingPermissions['_notes'])) {
            $permissions['_notes'] = $existingPermissions['_notes'];
        } else {
            $permissions['_notes'] = ['ja' => '', 'en' => ''];
        }

        return $permissions;
    }

    /**
     * declares のレポート出力
     */
    protected function outputDeclares(array $generated, ?array $existing): void
    {
        $this->info('Declares:');

        $rows = [];

        // configs
        foreach ($generated['configs'] as $key => $value) {
            $existingValue = $existing['configs'][$key] ?? null;
            $status = $this->getDiffStatus($value, $existingValue);
            $rows[] = ["configs.{$key}", $value ? 'true' : 'false', $status];
        }

        // その他
        foreach (['migrations', 'commands', 'middleware'] as $key) {
            $existingValue = $existing[$key] ?? null;
            $status = $this->getDiffStatus($generated[$key], $existingValue);
            $rows[] = [$key, $generated[$key] ? 'true' : 'false', $status];
        }

        // contracts
        if (! empty($generated['contracts'])) {
            foreach ($generated['contracts'] as $contract) {
                $existingContracts = $existing['contracts'] ?? [];
                $inExisting = in_array($contract, $existingContracts, true);
                $rows[] = ["contracts: {$contract}", 'true', $inExisting ? 'match' : 'new'];
            }
        }

        $this->table(['Key', 'Detected', 'Status'], $rows);
    }

    /**
     * permissions のレポート出力
     */
    protected function outputPermissions(array $generated, array $existing): void
    {
        $this->info('Permissions (from scan):');

        $rows = [];
        $metadataKeys = ['_optional', '_notes'];

        foreach ($generated as $category => $perms) {
            if (in_array($category, $metadataKeys, true)) {
                continue;
            }
            if (! is_array($perms)) {
                continue;
            }
            foreach ($perms as $key => $value) {
                $existingValue = $existing[$category][$key] ?? null;
                $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : json_encode($value);
                $status = $this->getDiffStatus($value, $existingValue);
                $rows[] = ["{$category}.{$key}", $displayValue, $status];
            }
        }

        $this->table(['Permission', 'Detected', 'Status'], $rows);
    }

    /**
     * 差分ステータスを取得
     */
    protected function getDiffStatus(mixed $generated, mixed $existing): string
    {
        if ($existing === null) {
            return '<fg=yellow>new</>';
        }
        if ($generated === $existing) {
            return '<fg=green>match</>';
        }

        return '<fg=red>changed</>';
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
     * PHPファイル一覧を取得
     *
     * @return array<string>
     */
    protected function getPhpFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && ! str_contains($file->getPathname(), '/vendor/')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
