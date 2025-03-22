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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Services\PluginMigrator;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use App\Console\Traits\PluginManagementTrait;


class PluginInstall extends Command
{

    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:install {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'プラグインをインストールし、データベースに登録し、マイグレーションを実行し、オートロードを更新します。';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $pluginName = $this->argument('name');
        $pluginPath = base_path('plugins/' . $pluginName);
        $composerPath = $pluginPath . '/composer.json';

        if (!File::exists($pluginPath)) {
            $this->error('指定されたプラグインは存在しません。');
            return;
        }

        // `composer.json` を取得
        $version = '1.0.0'; // デフォルトバージョン
        $description = null;
        $license = null;
        $author = null;
        $email = null;
        $web = null;
        $packageName = null;
        $slug = Str::slug(Str::headline($pluginName), '-'); // デフォルトのスラッグを `kebab-case` に変換

        if (File::exists($composerPath)) {
            $composerData = json_decode(File::get($composerPath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('composer.json の解析に失敗しました: ' . json_last_error_msg());
                return;
            }

            $version = $composerData['version'] ?? '1.0.0';
            $description = $composerData['description'] ?? null;
            $license = $composerData['license'] ?? null;
            $packageName = $composerData['name'] ?? null;

            // 作者情報の取得
            $authors = $composerData['authors'] ?? [];
            $firstAuthor = $authors[0] ?? [];
            $author = $firstAuthor['name'] ?? null;
            $email = $firstAuthor['email'] ?? null;
            $web = $firstAuthor['homepage'] ?? null;
            $slug = $composerData['extra']['slug'] ?? $slug;
        }


        // データベースに登録
        DB::table('plugins')->updateOrInsert(
            ['name' => $pluginName],
            [
                'package_name' => $packageName,
                'namespace' => "Plugins\\$pluginName",
                'directory' => $pluginName,
                'slug' => $slug,
                'description' => $description,
                'license' => $license,
                'author' => $author,
                'email' => $email,
                'web' => $web,
                'version' => $version, // composer.json から取得
                'status' => 0,
                'installed_at' => now()
            ]
        );

        $this->info("プラグイン '{$pluginName}' をインストールしました。");

        // マイグレーションを実行
        $this->info('マイグレーションを実行中...');
        $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $slug);
        $migrator->migrate($pluginName);

        // オートロードを更新
        $this->updateAutoload();

        // プラグインの有効化を確認
        if ($this->confirm("プラグイン '{$pluginName}' を有効化しますか？", true)) {
            DB::table('plugins')->where('name', $pluginName)->update(['status' => 1]);
            $this->info("プラグイン '{$pluginName}' を有効化しました。");
        } else {
            $this->info("プラグイン '{$pluginName}' は無効のままです。有効化するには管理画面またはコマンドを使用してください。");
        }
    }
}
