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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Services\PluginMigrator;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use App\Console\Traits\PluginManagementTrait;
use App\Helpers\GitExcludeHelper;
use App\Helpers\ComposerLocalHelper;


class PluginInstall extends Command
{

    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:install {pluginName} {--enable : Enable the plugin after installation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.plugin_install.description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $pluginName = $this->argument('pluginName');
        $pluginPath = base_path('plugins/' . $pluginName);
        $composerPath = $pluginPath . '/composer.json';

        if (!File::exists($pluginPath)) {
            $this->error(__('command.plugin.not_exists'));
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
                $this->error(__('command.make_plugin.installation.composer_parse_error', [
                    'error' => json_last_error_msg()
                ]));
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

        $this->info(__('command.make_plugin.installation.installed', [
            'pluginName' => $pluginName
        ]));

        // マイグレーションを実行
        $this->info(__('command.make_plugin.installation.migrating'));
        $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $slug);
        $migrator->migrate($pluginName);

        // 注意: composer.local.jsonと.git/info/excludeの更新は、
        // プラグイン作成時（make:plugin）に既に行われているため、ここでは不要

        // プラグインの有効化を確認（--enable オプションが指定されていない場合のみ確認）
        if ($this->option('enable') || $this->confirm(__('command.make_plugin.installation.enable_confirm', [
            'pluginName' => $pluginName
        ]), false)) {
            $this->call('plugin:enable', [
                'pluginName' => $pluginName
            ]);
        } else {
            $this->info(__('command.make_plugin.installation.enable_skipped', [
                'pluginName' => $pluginName
            ]));
        }
    }
}
