<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use App\Console\Traits\PluginManagementTrait;
use App\Services\PluginMigrator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginInstall extends Command
{
    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:install {pluginName : The name of the plugin to install} {--enable : Enable the plugin after installation}';

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
        $pluginPath = base_path('plugins/'.$pluginName);
        $pluginJsonPath = $pluginPath.'/plugin.json';
        $composerPath = $pluginPath.'/composer.json';

        if (! File::exists($pluginPath)) {
            $this->error(__('admin/command.plugin.not_exists'));

            return;
        }

        // プラグイン情報を読み取る（plugin.json → composer.json → デフォルト値の順）
        $version = '1.0.0';
        $description = null;
        $license = null;
        $author = null;
        $email = null;
        $web = null;
        $packageName = null;
        $slug = Str::slug(Str::headline($pluginName), '-');

        // 1. plugin.jsonから読み取り（最優先）
        if (File::exists($pluginJsonPath)) {
            $pluginData = json_decode(File::get($pluginJsonPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $packageName = $pluginData['package_name'] ?? $pluginData['name'] ?? null;
                $description = $pluginData['description'] ?? null;
                if (is_array($description)) {
                    $description = $description['en'] ?? $description['ja'] ?? null;
                }
                $license = $pluginData['license'] ?? null;
                $author = $pluginData['author'] ?? null;
                $email = $pluginData['email'] ?? null;
                $web = $pluginData['url'] ?? $pluginData['homepage'] ?? $pluginData['web'] ?? null;
                $version = $pluginData['version'] ?? '1.0.0';
                $slug = $pluginData['slug'] ?? $slug;
            }
        }

        // 2. composer.jsonからフォールバック
        if (File::exists($composerPath)) {
            $composerData = json_decode(File::get($composerPath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error(__('admin/command.make_plugin.installation.composer_parse_error', [
                    'error' => json_last_error_msg(),
                ]));

                return;
            }

            $packageName = $packageName ?? $composerData['name'] ?? null;
            $description = $description ?? $composerData['description'] ?? null;
            $license = $license ?? $composerData['license'] ?? null;

            if ($version === '1.0.0') {
                $version = $composerData['version'] ?? '1.0.0';
            }

            // 作者情報の取得
            if (! $author) {
                $authors = $composerData['authors'] ?? [];
                $firstAuthor = $authors[0] ?? [];
                $author = $firstAuthor['name'] ?? null;
                $email = $email ?? $firstAuthor['email'] ?? null;
                $web = $web ?? $firstAuthor['homepage'] ?? null;
            }

            $slug = $slug ?? $composerData['extra']['slug'] ?? Str::slug(Str::headline($pluginName), '-');
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
                'url' => $web,
                'version' => $version, // composer.json から取得
                'installed_at' => now(),
            ]
        );

        $this->info(__('admin/command.make_plugin.installation.installed', [
            'pluginName' => $pluginName,
        ]));

        // マイグレーションを実行
        $this->info(__('admin/command.make_plugin.installation.migrating'));
        $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $slug);
        $migrator->migrate($pluginName);

        // シーダーを実行（DatabaseSeederが存在する場合のみ）
        $seederClass = "Plugins\\{$pluginName}\\Database\\Seeders\\DatabaseSeeder";
        if (class_exists($seederClass)) {
            $this->info(__('admin/command.make_plugin.installation.seeding'));
            $this->call('dls:plugin:seed', [
                'plugin' => $pluginName,
                '--force' => true,
            ]);
        }

        // 注意: composer.local.jsonと.git/info/excludeの更新は、
        // プラグイン作成時（make:plugin）に既に行われているため、ここでは不要

        // プラグインの有効化を確認（--enable オプションが指定されていない場合のみ確認）
        // Web経由での実行時は対話的入力ができないため、--enableオプションの有無のみで判断
        if ($this->option('enable')) {
            $this->call('dls:plugin:enable', [
                'pluginName' => $pluginName,
            ]);
        } elseif (app()->runningInConsole() && ! app()->runningUnitTests()) {
            // CLIからの実行時のみ確認プロンプトを表示
            if ($this->confirm(__('admin/command.make_plugin.installation.enable_confirm', [
                'pluginName' => $pluginName,
            ]), false)) {
                $this->call('dls:plugin:enable', [
                    'pluginName' => $pluginName,
                ]);
            } else {
                $this->info(__('admin/command.make_plugin.installation.enable_skipped', [
                    'pluginName' => $pluginName,
                ]));
            }
        } else {
            $this->info(__('admin/command.make_plugin.installation.enable_skipped', [
                'pluginName' => $pluginName,
            ]));
        }
    }
}
