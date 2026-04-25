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

namespace App\Services\Plugin;

use App\Services\Plugin\Scanning\PatternRegistry;
use Illuminate\Support\Facades\File;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * plugin.json の permissions / declares セクションを実装コードに合わせて自動同期する。
 *
 * - permissions: PatternRegistry の検出結果から true/false を埋める
 * - declares: 実ファイル存在から true/false／配列を埋める
 *
 * 手動編集された `_optional`、`_notes`、`content.read_other_plugins` 等の
 * 「人間が判断して書く」フィールドは温存する。
 */
class PluginManifestSyncService
{
    /**
     * plugin.json の permissions ツリーに書き込む対象キー（dot 記法）。
     *
     * PatternRegistry はスキャナ用の追加カテゴリ（dangerous_api / csp /
     * database.core_tables_read 等）も検出するが、それらは健全性スコア専用で
     * manifest の permissions セクションには載せない（schema が異なる）。
     */
    private const MANIFEST_PERMISSION_KEYS = [
        'database.own_tables',
        'storage.own_directory',
        'storage.public_uploads',
        'storage.temp_files',
        'settings.read_core',
        'settings.write_own',
        'members.read',
        'members.write',
        'members.create',
        'members.delete',
        'mail.send',
        'mail.bulk_send',
        'system.register_shortcodes',
        'system.register_middleware',
        'system.register_commands',
        'system.register_blade_directives',
        'system.modify_routes',
    ];

    public function __construct(
        protected PatternRegistry $patternRegistry,
    ) {}

    /**
     * 同期結果（変更前後の plugin.json と差分情報）を返す。
     *
     * 書き込みは行わない（差分計算のみ）。実ファイル更新は呼び出し側で行う。
     *
     * @return array{
     *     manifest_path: string,
     *     before: array<string, mixed>,
     *     after: array<string, mixed>,
     *     changed: bool,
     *     changes: array<int, array{path: string, before: mixed, after: mixed}>,
     *     evidence: array<string, array>,
     * }
     */
    public function diff(string $pluginDir, string $type = 'plugin'): array
    {
        $manifestFile = $type === 'theme' ? 'theme.json' : 'plugin.json';
        $manifestPath = "{$pluginDir}/{$manifestFile}";

        if (! File::exists($manifestPath)) {
            throw new \RuntimeException("Manifest file not found: {$manifestPath}");
        }

        $before = json_decode(File::get($manifestPath), true);
        if (! is_array($before)) {
            throw new \RuntimeException("Manifest is not valid JSON: {$manifestPath}");
        }

        $scan = $this->patternRegistry->scan($pluginDir, $type);
        $detected = $scan['permissions'];

        $after = $before;
        $after['permissions'] = $this->mergePermissions($before['permissions'] ?? [], $detected);
        $after['declares'] = $this->resolveDeclares($pluginDir, $before['declares'] ?? [], $type);

        $changes = $this->computeChanges($before, $after);

        return [
            'manifest_path' => $manifestPath,
            'before' => $before,
            'after' => $after,
            'changed' => ! empty($changes),
            'changes' => $changes,
            'evidence' => $scan['evidence'],
        ];
    }

    /**
     * 同期を実行して manifest を書き込む。
     *
     * @return array 同 diff() の戻り値
     */
    public function sync(string $pluginDir, string $type = 'plugin'): array
    {
        $result = $this->diff($pluginDir, $type);

        if ($result['changed']) {
            $json = json_encode($result['after'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            File::put($result['manifest_path'], $json."\n");
        }

        return $result;
    }

    /**
     * 検出された権限を既存の permissions ツリーにマージする。
     *
     * - dot 記法の検出結果（`mail.send` 等）をネスト構造に展開
     * - 検出なし（false）でもツリーには残す（未使用を明示）
     * - `_optional` / `_notes` / `content.read_other_plugins` 等の手動入力は温存
     */
    protected function mergePermissions(array $existing, array $detected): array
    {
        $merged = $existing;

        // manifest schema にあるキーのみを対象にする
        foreach (self::MANIFEST_PERMISSION_KEYS as $key) {
            $value = $detected[$key] ?? false;
            $segments = explode('.', $key);
            $this->setNested($merged, $segments, (bool) $value);
        }

        // permissions セクションのテンプレートを保証（false で埋める）
        $merged = $this->ensurePermissionShape($merged);

        return $merged;
    }

    /**
     * permissions セクションが期待する構造（カテゴリ全列挙）になっていることを保証する。
     */
    protected function ensurePermissionShape(array $permissions): array
    {
        $defaults = [
            'database' => [
                'own_tables' => false,
                'core_tables' => $permissions['database']['core_tables'] ?? [],
            ],
            'storage' => [
                'own_directory' => false,
                'public_uploads' => false,
                'temp_files' => false,
            ],
            'settings' => [
                'read_core' => false,
                'write_own' => false,
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
                'read_other_plugins' => $permissions['content']['read_other_plugins'] ?? [],
                'write_other_plugins' => $permissions['content']['write_other_plugins'] ?? [],
            ],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        // 既存 permissions の値で defaults を上書き（カテゴリ単位で再帰マージ）
        foreach ($defaults as $cat => $defaultValues) {
            $permissions[$cat] = array_merge($defaultValues, $permissions[$cat] ?? []);
        }

        // 手動入力フィールド（温存）
        $permissions['_optional'] = $permissions['_optional'] ?? [];
        $permissions['_notes'] = $permissions['_notes'] ?? ['ja' => '', 'en' => ''];

        return $permissions;
    }

    /**
     * declares セクションを実ファイルから解決する。
     */
    protected function resolveDeclares(string $pluginDir, array $existing, string $type): array
    {
        $declares = $existing;

        // configs
        $configs = $existing['configs'] ?? [];
        $configs['roles'] = File::exists("{$pluginDir}/config/admin/roles.php");
        $configs['database_cleanup'] = File::exists("{$pluginDir}/config/admin/database-cleanup.php");
        $configs['navigation'] = File::exists("{$pluginDir}/config/admin/navigation.php");
        $declares['configs'] = $configs;

        // contracts: app/Contracts/PluginIntegration/*.php または app/Contracts/*.php の存在
        $contractsDir = "{$pluginDir}/app/Contracts";
        $contracts = [];
        if (File::isDirectory($contractsDir)) {
            $contracts = $this->collectClassNames($contractsDir);
        }
        // 既存の手動値も含めて重複排除
        $existingContracts = $existing['contracts'] ?? [];
        if (is_array($existingContracts)) {
            $contracts = array_values(array_unique(array_merge($contracts, $existingContracts)));
        }
        $declares['contracts'] = $contracts;

        // migrations: ファイル存在
        $declares['migrations'] = File::isDirectory("{$pluginDir}/database/migrations")
            && count(File::files("{$pluginDir}/database/migrations")) > 0;

        // commands: ファイル存在
        $commandsDir = "{$pluginDir}/app/Console/Commands";
        $declares['commands'] = File::isDirectory($commandsDir)
            && count(File::allFiles($commandsDir)) > 0;

        // middleware: ファイル存在
        $middlewareDir = "{$pluginDir}/app/Http/Middleware";
        $declares['middleware'] = File::isDirectory($middlewareDir)
            && count(File::allFiles($middlewareDir)) > 0;

        // assets セクション（既存の手動入力を温存）
        if (isset($existing['assets'])) {
            $declares['assets'] = $existing['assets'];
        }

        return $declares;
    }

    /**
     * ディレクトリ配下の PHP クラス名を収集する。
     *
     * @return array<int, string>
     */
    protected function collectClassNames(string $dir): array
    {
        $classes = [];
        if (! File::isDirectory($dir)) {
            return $classes;
        }
        foreach (File::allFiles($dir) as $file) {
            if ($file->getExtension() === 'php') {
                $classes[] = $file->getBasename('.php');
            }
        }
        sort($classes);

        return array_values(array_unique($classes));
    }

    /**
     * ネストした配列の指定パスに値をセットする。
     *
     * @param  array<int, string>  $segments
     */
    protected function setNested(array &$target, array $segments, mixed $value): void
    {
        $current = &$target;
        foreach ($segments as $i => $segment) {
            $isLast = $i === count($segments) - 1;
            if ($isLast) {
                $current[$segment] = $value;
            } else {
                if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }
    }

    /**
     * before / after の差分を平坦化して返す（人間表示用）。
     *
     * @return array<int, array{path: string, before: mixed, after: mixed}>
     */
    protected function computeChanges(array $before, array $after, string $prefix = ''): array
    {
        $changes = [];

        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($allKeys as $key) {
            $path = $prefix === '' ? $key : "{$prefix}.{$key}";
            $b = $before[$key] ?? null;
            $a = $after[$key] ?? null;

            if (is_array($b) && is_array($a) && ! $this->isList($b) && ! $this->isList($a)) {
                $changes = array_merge($changes, $this->computeChanges($b, $a, $path));

                continue;
            }

            if ($b !== $a) {
                $changes[] = ['path' => $path, 'before' => $b, 'after' => $a];
            }
        }

        return $changes;
    }

    /**
     * 配列がリスト（数値キー）かどうか。
     */
    protected function isList(array $arr): bool
    {
        if (empty($arr)) {
            return true;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
