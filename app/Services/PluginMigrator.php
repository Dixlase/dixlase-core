<?php

/**
 * This file is part of MySoftware.
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

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Log;


class PluginMigrator
{
    protected Migrator $migrator;
    protected Filesystem $files;
    protected MigrationRepositoryInterface $repository;
    protected ?string $pluginSlug;

    /**
     * コンストラクタ
     *
     * @param Filesystem $files
     * @param ConnectionResolverInterface $resolver
     * @param string $migrationTable
     */
    public function __construct(
        Filesystem $files,
        ConnectionResolverInterface $resolver,
        string $migrationTable = 'plugin_migrations',
        ?string $pluginSlug = null // null 許容
    ) {
        $this->files = $files;
        $this->pluginSlug = $pluginSlug;

        // `plugin_migrations` テーブル用のリポジトリを作成
        $this->repository = new PluginMigrationRepository($resolver, $migrationTable, $pluginSlug);

        // Migratorのインスタンスを作成
        $this->migrator = new Migrator(
            $this->repository,
            $resolver,
            $this->files
        );

        // デフォルトのデータベース接続を設定（必要に応じて変更）
        $this->migrator->setConnection($resolver->getDefaultConnection());
    }

    /**
     * 指定プラグインのマイグレーションを実行
     *
     * @param string $plugin プラグイン名
     * @param string|null $path マイグレーションファイルのパス（デフォルトはプラグインディレクトリ内）
     * @param array $options オプション（--force など）
     * @return array 実行されたマイグレーションの詳細
     *
     * @throws \Exception
     */
    public function migrate(string $plugin, ?string $path = null, array $options = []): array
    {
        $migrationPath = $path ?? base_path("plugins/{$plugin}/database/migrations");

        if (!$this->files->isDirectory($migrationPath)) {
            throw new \Exception("Migration path does not exist: {$migrationPath}");
        }

        // 実行前にマイグレーションファイルを取得
        $before = $this->repository->getRan();

        // マイグレーターにマイグレーションパスを設定
        $this->migrator->run($migrationPath, [
            'pretend' => $options['pretend'] ?? false,
            'step' => $options['step'] ?? false,
        ]);

        // 実行後にマイグレーションファイルを取得
        $after = $this->repository->getRan($this->pluginSlug);

        // 新たに実行されたマイグレーションファイルを抽出
        $migrated = array_diff($after, $before);

        return array_values($migrated);
    }

    /**
     * 指定プラグインのマイグレーションをロールバック
     *
     * @param string $plugin プラグイン名
     * @param array $options オプション（--step=1 など）
     * @return array ロールバックされたマイグレーションのノート
     *
     * @throws \Exception
     */
    public function rollback(string $plugin, array $options = []): array
    {
        $migrationPath = base_path("plugins/{$plugin}/database/migrations");

        if (!$this->files->isDirectory($migrationPath)) {
            throw new \Exception("Migration path does not exist: {$migrationPath}");
        }

        // ロールバック前にマイグレーションファイルを取得
        $before = $this->repository->getRan();

        // マイグレーターにマイグレーションパスを設定
        $this->migrator->rollback($migrationPath, [
            'step' => $options['step'] ?? 1,
            'pretend' => $options['pretend'] ?? false,
        ]);

        // ロールバック後にマイグレーションファイルを取得
        $after = $this->repository->getRan();

        // ロールバックされたマイグレーションファイルを抽出
        $rolledBack = array_diff($before, $after);

        return array_values($rolledBack);
    }

    /**
     * 指定プラグインのマイグレーションをリフレッシュ
     *
     * @param string $plugin プラグイン名
     * @param array $options オプション（--step=1 など）
     * @return array リフレッシュ後に実行されたマイグレーションのノート
     *
     * @throws \Exception
     */
    public function refresh(string $plugin, array $options = []): array
    {
        // ロールバック
        $rolledBack = $this->rollback($plugin, $options);

        // 再実行
        $migrated = $this->migrate($plugin, null, $options);

        return array_merge($rolledBack, $migrated);
    }

    /**
     * 指定プラグインのマイグレーションステータスを取得
     *
     * @param string $plugin プラグイン名
     * @return array
     *
     * @throws \Exception
     */
    public function getMigrationStatus(string $plugin): array
    {
        $migrationPath = base_path("plugins/{$plugin}/database/migrations");

        if (!$this->files->isDirectory($migrationPath)) {
            throw new \Exception("Migration path does not exist: {$migrationPath}");
        }

        $migrations = $this->migrator->getMigrationFiles($migrationPath);

        $ran = $this->repository->getRan();
        $ran = array_filter($ran, function ($migration) use ($plugin) {
            // プラグイン名をプレフィックスとして含むマイグレーションのみをフィルタリング
            return Str::startsWith($migration, Str::snake($plugin) . '_');
        });

        $status = [];
        foreach ($migrations as $file => $path) {
            $status[] = [
                'migration' => basename($file, '.php'),
                'ran' => in_array(basename($file, '.php'), $ran),
            ];
        }

        return $status;
    }
}
