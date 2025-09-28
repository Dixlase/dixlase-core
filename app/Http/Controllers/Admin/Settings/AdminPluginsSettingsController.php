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

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plugin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Traits\PluginLoaderTrait;
use Illuminate\Support\Str;
use ZipArchive;
use App\Services\PluginMigrator;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

class AdminPluginsSettingsController extends AdminLoggedInController
{
    use PluginLoaderTrait;

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $plugins = Plugin::all();
        
        // 各プラグインに設定画面があるかチェック、翻訳された名前と説明を取得
        foreach ($plugins as $plugin) {
            $plugin->has_settings = $this->checkPluginHasSettings($plugin);
            $plugin->translated_name = $this->getPluginName($plugin);
            $plugin->translated_description = $this->getPluginDescription($plugin);
        }
        
        $this->viewParams['plugins'] = $plugins;
        $this->viewParams['heading'] = 'プラグインマスター';
        return view('admin::settings.plugins.index', $this->viewParams);
    }

    public function install()
    {


        $this->viewParams['heading'] = 'プラグインインストール';
        return view('admin::settings.plugins.install', $this->viewParams);
    }

    public function upload(Request $request)
    {


        // 例: ini_get('upload_max_filesize') -> "2M"
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $maxBytes = $this->parsePhpSize($uploadMaxFilesize); // 下記関数で "2M" -> 2097152 に変換

        Log::info('uploadMaxFilesize: ' . $uploadMaxFilesize);
        Log::info('maxBytes: ' . $maxBytes);

        $request->validate([
            'plugin_file' => [
                'required',
                'file',
                'mimes:zip',
                'max:' . floor($maxBytes / 1024), // kB単位に変換 (Laravel の max: ルールがkB単位)
            ],
        ]);

        Log::info('validate完了');


        // ZIPファイルを一時保存
        $file = $request->file('plugin_file');
        $fileName = $file->getClientOriginalName();
        $tempPath = storage_path('app/temp/plugins/' . $fileName);

        Log::info('tempPath: ' . $tempPath);

        $file->move(storage_path('app/temp/plugins'), $fileName);

        Log::info('move完了');

        // ZIP展開
        $zip = new ZipArchive();
        if ($zip->open($tempPath) === true) {


            // プラグインフォルダ名取得 (ZIP内の最初のディレクトリ)
            $pluginDir = null;
            $dirs = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);

                // エントリがディレクトリの中のファイルである場合も含めて考慮
                if ($entry !== false) {
                    $pathParts = explode('/', $entry);

                    // ルートディレクトリの取得
                    if (!empty($pathParts[0])) {
                        $dirs[] = $pathParts[0];
                    }
                }
            }

            // 最も上位のディレクトリ名を取得（重複削除）
            $dirs = array_unique($dirs);
            $pluginDir = reset($dirs); // 配列の最初の要素を取得

            if (!$pluginDir) {
                return redirect()->back()->with('error', 'ZIP 内に有効なプラグインディレクトリが見つかりません。');
            }


            $destinationPath = base_path('plugins/' . $pluginDir);

            // プラグインフォルダが既に存在しているか確認
            if (File::exists($destinationPath)) {
                $zip->close();
                File::delete($tempPath);
                return redirect()->back()->with('error', 'プラグインは既に存在します。');
            }

            // ZIPを解凍
            $zip->extractTo(base_path('plugins'));
            $zip->close();

            // ZIPファイルを削除
            File::delete($tempPath);

            // composer.json のパス
            $composerPath = base_path("plugins/{$pluginDir}/composer.json");

            if (File::exists($composerPath)) {
                // composer.json の内容を取得
                $jsonContent = File::get($composerPath);
                $composerData = json_decode($jsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return redirect()->back()->with('error', 'composer.json の解析に失敗しました: ' . json_last_error_msg());
                }

                // 1. プラグインの“見せたい”名前を display-name から取得
                $displayName = $composerData['extra']['display-name'] ?? $pluginDir;

                // 2. authors[0] から author, email, homepage を取得
                $authors = $composerData['authors'] ?? [];
                $firstAuthor = $authors[0] ?? [];
                $author = $firstAuthor['name'] ?? null;
                $email  = $firstAuthor['email'] ?? null;
                $web    = $firstAuthor['homepage'] ?? null;

                // 3. スラッグやライセンスなど
                $slug = $composerData['extra']['slug'] ?? Str::slug($pluginDir);
                $version = $composerData['version'] ?? '1.0.0';
                $license = $composerData['license'] ?? null;
                $description = $composerData['description'] ?? null;

                // 4. Composerパッケージ名
                $packageName = $composerData['name'] ?? null; // "my-software/plugins-my-plugin"

                // 5. namespace はフォルダ名から生成
                $namespace = "Plugins\\$pluginDir";

                // DBにプラグイン情報を登録
                $plugin = Plugin::create([
                    'name'        => $displayName, // “MyPlugin”
                    'package_name' => $packageName, // "my-software/plugins-my-plugin"
                    'directory'   => $pluginDir,   // 例: "MyPlugin"
                    'slug'        => $slug,
                    'namespace'   => $namespace,
                    'description' => $description,
                    'license'     => $license,
                    'author'      => $author,
                    'email'       => $email,
                    'web'         => $web,
                    'version'     => $version,
                    'status'      => 0, // デフォルトで無効化
                    'installed_at' => now(), // インストール日時をセット
                ]);

                // インストールしたプラグインのID
                $pluginId = $plugin->id;
            } else {
                return redirect()->back()->with('error', 'composer.json が見つかりません。');
            }

            $migrator = new PluginMigrator(
                app(Filesystem::class),
                app(ConnectionResolverInterface::class),
                'plugin_migrations',
                $slug // ここでプラグインのスラッグを渡す
            );

            // プラグインのマイグレーションを実行
            // $migrated には「新しく実行された」マイグレーションファイルが入る
            $migrated = $migrator->migrate($pluginDir, null, ['step' => false]);

            if (!empty($migrated)) {
                // 新しいマイグレーションがあったので、テーブルが新規(または更新)された
                // ここでシーダー実行
                Artisan::call('plugin:seed', [
                    'plugin' => $pluginDir,
                    '--force' => true,
                ]);
            } else {
                // 空 → "No migrations to run" の状態
                // テーブルが既にあるとみなしてシーダーをスキップ
            }

            // artisanコマンドでPSR-4オートロードを更新
            Artisan::call('plugin:autoload:sync', ['--cleanup' => true]);

            return redirect()->route('admin.settings.plugins.index')
                ->with('success', 'プラグインが正常にインストールされました。')
                ->with('installed_plugin_id', $pluginId);
        }

        // エラー処理
        return redirect()->back()->with('error', 'ZIPファイルの展開に失敗しました。');
    }



    public function enable($id)
    {


        $plugin = Plugin::findOrFail($id);

        // シンボリックリンクを作成
        create_plugin_symlink($plugin->directory);

        //プラグイン名を取得
        $pluginName = $plugin->name;

        //プラグインディレクトリ名を取得
        $pluginDirectory = $plugin->directory;

        try {
            // プラグインを有効化
            $plugin->update(['status' => 1]);

            return redirect()->route('admin.settings.plugins.index')->with('success', 'プラグインを有効化しました');
        } catch (\Exception $e) {
            return back()->with('error', "プラグイン有効化中にエラーが発生しました: {$e->getMessage()}");
        }
    }

    public function disable($id)
    {


        $plugin = Plugin::findOrFail($id);
        try {
            // シンボリックリンクを削除
            delete_plugin_symlink($plugin->directory);

            // プラグインを無効化
            $plugin->update(['status' => 0]);

            return redirect()->route('admin.settings.plugins.index')->with('success', 'プラグインを無効化しました');
        } catch (\Exception $e) {
            return back()->with('error', "プラグイン無効化中にエラーが発生しました: {$e->getMessage()}");
        }
    }

    public function uninstall($id, Request $request)
    {

        // プラグインを取得
        $plugin = Plugin::findOrFail($id);
        $pluginDir = $plugin->directory;

        // ★ 1) DBデータも削除か？ → plugin:rollback 実行
        if ($request->has('remove_db_data')) {
            // plugin:rollback コマンドを呼ぶ (実装済みなら)
            Artisan::call('plugin:migrate:rollback', [
                'plugin' => $pluginDir,
                '--force' => true,
                '--step' => 9999, // 全部ロールバック
            ]);
        }

        // プラグインフォルダを削除
        File::deleteDirectory(base_path('plugins/' . $pluginDir));

        // データベースから削除
        $plugin->delete();

        // artisanコマンドでPSR-4オートロードを更新
        Artisan::call('plugin:autoload:sync', ['--cleanup' => true]);


        return redirect()->route('admin.settings.plugins.index')
            ->with('success', 'プラグインをアンインストールしました');
    }

    // ZIPファイルのサイズをバイト数に変換
    private function parsePhpSize($sizeStr)
    {
        // 大文字/小文字両対応
        $sizeStr = trim($sizeStr);
        $unit = strtoupper(substr($sizeStr, -1));
        $value = (int) substr($sizeStr, 0, -1);

        switch ($unit) {
            case 'G':
                $value *= 1024;
                // fall-through
            case 'M':
                $value *= 1024;
                // fall-through
            case 'K':
                $value *= 1024;
                break;
            default:
                // 単位なし
                $value = (int) $sizeStr;
                break;
        }
        return $value;
    }

    /**
     * プラグインが設定画面を持っているかチェック
     */
    private function checkPluginHasSettings($plugin)
    {
        if ($plugin->status !== 1) {
            return false; // 無効なプラグインは設定画面なし
        }

        // プラグインの設定ファイルから設定画面ルート名を取得
        $configPath = base_path("plugins/{$plugin->directory}/config/admin.php");
        
        if (!file_exists($configPath)) {
            return false;
        }

        try {
            $pluginConfig = require $configPath;
            $settingsRoute = $pluginConfig['settings_route'] ?? null;
            
            \Log::info('Plugin settings check', [
                'plugin' => $plugin->directory,
                'configPath' => $configPath,
                'settingsRoute' => $settingsRoute,
                'routeExists' => $settingsRoute ? \Route::has($settingsRoute) : false
            ]);
            
            // settings_routeが定義されており、実際にルートが存在するかチェック
            return !empty($settingsRoute) && \Route::has($settingsRoute);
        } catch (\Exception $e) {
            \Log::error('Plugin settings check failed', [
                'plugin' => $plugin->directory,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * プラグインの設定画面URLを取得
     */
    public function getPluginSettingsUrl($plugin)
    {
        // プラグインの設定ファイルから設定画面ルート名を取得
        $configPath = base_path("plugins/{$plugin->directory}/config/admin.php");
        $routeName = null;
        
        if (file_exists($configPath)) {
            $pluginConfig = require $configPath;
            $routeName = $pluginConfig['settings_route'] ?? null;
        }

        \Log::info('Plugin settings URL generation', [
            'plugin' => $plugin->directory,
            'configPath' => $configPath,
            'configExists' => file_exists($configPath),
            'routeName' => $routeName,
            'routeExists' => $routeName ? \Route::has($routeName) : false
        ]);

        if ($routeName && \Route::has($routeName)) {
            $url = route($routeName);
            \Log::info('Generated URL', ['url' => $url]);
            return $url;
        }

        return null;
    }

    /**
     * プラグインの翻訳された名前を取得
     */
    private function getPluginName($plugin)
    {
        try {
            // プラグインの翻訳ファイルから名前を取得
            $pluginSlug = strtolower(str_replace('Dixlase', 'dixlase-', $plugin->directory));
            $translationKey = $pluginSlug . '::admin.plugin.name';
            $name = __($translationKey);
            
            // 翻訳キーがそのまま返された場合は翻訳が見つからない
            if ($name === $translationKey) {
                return $plugin->name ?? 'プラグイン名なし';
            }
            
            return $name;
        } catch (\Exception $e) {
            // 翻訳ファイルが存在しない場合はDBの名前またはデフォルト
            return $plugin->name ?? 'プラグイン名なし';
        }
    }

    /**
     * プラグインの翻訳された説明を取得
     */
    private function getPluginDescription($plugin)
    {
        try {
            // プラグインの翻訳ファイルから説明を取得
            $pluginSlug = strtolower(str_replace('Dixlase', 'dixlase-', $plugin->directory));
            $translationKey = $pluginSlug . '::admin.plugin.description';
            $description = __($translationKey);
            
            // 翻訳キーがそのまま返された場合は翻訳が見つからない
            if ($description === $translationKey) {
                return $plugin->description ?? '説明がありません';
            }
            
            return $description;
        } catch (\Exception $e) {
            // 翻訳ファイルが存在しない場合はDBの説明またはデフォルト
            return $plugin->description ?? '説明がありません';
        }
    }
}
