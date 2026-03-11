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

namespace App\Services\Plugin\Scanning;

use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 検出パターンの一元管理レジストリ
 *
 * 登録されたDetectionPatternインスタンスを使って
 * プラグイン・テーマのコードをスキャンし、権限使用を検出します。
 */
class PatternRegistry
{
    /**
     * 登録済みパターン
     *
     * @var array<string, DetectionPattern>
     */
    protected array $patterns = [];

    /**
     * 全デフォルトパターンを登録したレジストリを生成
     */
    public static function createDefault(): static
    {
        $registry = new static();

        // データベース
        $registry->register(new DatabaseDetectionPattern('own_tables'));
        $registry->register(new DatabaseDetectionPattern('core_tables_read'));
        $registry->register(new DatabaseDetectionPattern('core_tables_write'));

        // ストレージ
        $registry->register(new StorageDetectionPattern('own_directory'));
        $registry->register(new StorageDetectionPattern('public_uploads'));
        $registry->register(new StorageDetectionPattern('temp_files'));

        // 設定
        $registry->register(new SettingsDetectionPattern('read_core'));
        $registry->register(new SettingsDetectionPattern('write_own'));

        // メンバー
        $registry->register(new MemberDetectionPattern('read'));
        $registry->register(new MemberDetectionPattern('write'));
        $registry->register(new MemberDetectionPattern('create'));
        $registry->register(new MemberDetectionPattern('delete'));

        // メール
        $registry->register(new MailDetectionPattern('send'));
        $registry->register(new MailDetectionPattern('bulk_send'));

        // システム
        $registry->register(new SystemDetectionPattern('register_shortcodes'));
        $registry->register(new MiddlewareDetectionPattern());
        $registry->register(new SystemDetectionPattern('register_commands'));
        $registry->register(new SystemDetectionPattern('register_blade_directives'));
        $registry->register(new SystemDetectionPattern('modify_routes'));

        // 危険API
        $registry->register(new DangerousApiPattern('exec'));
        $registry->register(new DangerousApiPattern('env_access'));

        // 外部リソース（プラグイン・テーマ共通）
        $registry->register(new ExternalResourceDetectionPattern());

        // テーマアセット
        $registry->register(new ThemeAssetDetectionPattern('custom_css'));
        $registry->register(new ThemeAssetDetectionPattern('custom_js'));
        $registry->register(new ThemeAssetDetectionPattern('external_resources'));

        return $registry;
    }

    /**
     * パターンを登録
     */
    public function register(DetectionPattern $pattern): void
    {
        $this->patterns[$pattern->permissionKey()] = $pattern;
    }

    /**
     * 指定種別に適用可能なパターン一覧を取得
     *
     * @param  string  $type  'plugin' or 'theme'
     * @return array<string, DetectionPattern>
     */
    public function getPatternsFor(string $type): array
    {
        return array_filter(
            $this->patterns,
            fn (DetectionPattern $p) => $p->applicableTo() === 'both' || $p->applicableTo() === $type
        );
    }

    /**
     * 全パターンを取得
     *
     * @return array<string, DetectionPattern>
     */
    public function all(): array
    {
        return $this->patterns;
    }

    /**
     * 指定ディレクトリをスキャンし、権限使用を検出
     *
     * @param  string  $extensionDir  拡張機能のルートディレクトリ
     * @param  string  $type  'plugin' or 'theme'
     * @return array{permissions: array<string, bool>, evidence: array<string, array>}
     */
    public function scan(string $extensionDir, string $type = 'plugin'): array
    {
        $patterns = $this->getPatternsFor($type);
        $detected = [];
        $evidence = [];

        foreach ($patterns as $permissionKey => $pattern) {
            $found = false;
            $foundEvidence = [];

            // ファイルパターンの存在確認
            foreach ($pattern->filePatterns() as $filePattern) {
                $files = $this->globRecursive("{$extensionDir}/{$filePattern}");
                foreach ($files as $file) {
                    $found = true;
                    $foundEvidence[] = [
                        'type' => 'file_exists',
                        'file' => str_replace($extensionDir.'/', '', $file),
                    ];
                }
            }

            // コードスキャン
            if (! empty($pattern->regexPatterns())) {
                $codeFiles = $this->getCodeFiles($extensionDir, $type);
                foreach ($codeFiles as $file) {
                    $content = File::get($file);
                    $relativePath = str_replace($extensionDir.'/', '', $file);
                    $results = $pattern->scan($content, $relativePath);

                    if (! empty($results)) {
                        $found = true;
                        $foundEvidence = array_merge($foundEvidence, $results);
                    }
                }
            }

            $detected[$permissionKey] = $found;
            if (! empty($foundEvidence)) {
                $evidence[$permissionKey] = $foundEvidence;
            }
        }

        return [
            'permissions' => $detected,
            'evidence' => $evidence,
        ];
    }

    /**
     * コードファイル一覧を取得
     *
     * @return array<string>
     */
    protected function getCodeFiles(string $dir, string $type): array
    {
        $files = [];

        if (! File::isDirectory($dir)) {
            return $files;
        }

        // スキャン対象から除外するディレクトリ
        $excludeDirs = ['tests', 'vendor', 'node_modules'];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            // 除外ディレクトリ内のファイルをスキップ
            $relativePath = str_replace($dir.'/', '', $file->getPathname());
            $topDir = explode('/', $relativePath)[0] ?? '';
            if (in_array($topDir, $excludeDirs, true)) {
                continue;
            }

            $ext = $file->getExtension();
            $filename = $file->getFilename();

            // PHPファイル
            if ($ext === 'php') {
                $files[] = $file->getPathname();

                continue;
            }

            // テーマの場合はJSやBladeも対象
            if ($type === 'theme') {
                if ($ext === 'js' || str_ends_with($filename, '.blade.php')) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * globパターンを再帰的に展開
     *
     * @return array<string>
     */
    protected function globRecursive(string $pattern): array
    {
        $files = glob($pattern);

        $dir = dirname($pattern);
        $filename = basename($pattern);

        if (File::isDirectory($dir)) {
            foreach (File::directories($dir) as $subdir) {
                $files = array_merge($files, $this->globRecursive("{$subdir}/{$filename}"));
            }
        }

        return $files ?: [];
    }
}
