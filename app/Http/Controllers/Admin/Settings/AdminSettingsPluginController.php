<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plugin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use ZipArchive;




class AdminSettingsPluginController extends AdminController
{
    public function index()
    {

        // 権限を確認
        $this->checkPermission('manager');

        $plugins = Plugin::all();
        $this->viewParams['heading'] = config('admin.settings.plugins.index.heading');
        $this->viewParams['plugins'] = $plugins;
        return view('admin::settings.plugins.index', $this->viewParams);
    }

    public function install()
    {

        // 権限を確認
        $this->checkPermission('super_manager');

        $this->viewParams['heading'] = 'プラグインインストール';
        return view('admin::settings.plugins.install', $this->viewParams);
    }

    public function upload(Request $request)
    {

        // 権限を確認
        $this->checkPermission('super_manager');

        // ファイルアップロード処理
        $request->validate([
            'plugin_file' => 'required|file|mimes:zip|max:2048',
        ]);

        // ZIPファイルを一時保存
        $file = $request->file('plugin_file');
        $fileName = $file->getClientOriginalName();
        $tempPath = storage_path('app/temp/plugins/' . $fileName);

        $file->move(storage_path('app/temp/plugins'), $fileName);

        // ZIP展開
        $zip = new ZipArchive();
        if ($zip->open($tempPath) === true) {
            // プラグインフォルダ名取得 (ZIP内の最初のディレクトリ)
            $pluginDir = trim($zip->getNameIndex(0), '/');
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

            // データベースに登録
            Plugin::create([
                'name' => $pluginDir,
                'namespace' => "Plugins\\$pluginDir",
                'status' => 'disabled',
            ]);

            // プラグインのマイグレーションディレクトリを動的に指定
            $pluginMigrationPath = base_path("plugins/{$pluginDir}/migrations");

            if (is_dir($pluginMigrationPath)) {
                // マイグレーションを実行
                Artisan::call('migrate', [
                    '--path' => "plugins/{$pluginDir}/migrations",
                    '--force' => true, // 実行確認なしで実行
                ]);
            }

            return redirect()->route('admin.settings.plugins.index')->with('success', 'プラグインが正常にインストールされました。');
        }

        // エラー処理
        return redirect()->back()->with('error', 'ZIPファイルの展開に失敗しました。');
    }



    public function enable($id)
    {

        // 権限を確認
        $this->checkPermission('super_manager');

        $plugin = Plugin::findOrFail($id);
        $plugin->update(['status' => 'enabled']);

        return redirect()->route('admin.settings.plugins.index')->with('success', 'プラグインを有効化しました');
    }

    public function disable($id)
    {

        // 権限を確認
        $this->checkPermission('super_manager');

        $plugin = Plugin::findOrFail($id);
        $plugin->update(['status' => 'disabled']);

        return redirect()->route('admin.settings.plugins.index')->with('success', 'プラグインを無効化しました');
    }

    public function uninstall($id)
    {

        // 権限を確認
        $this->checkPermission('super_manager');

        // データベースから削除
        $plugin = Plugin::findOrFail($id);
        $plugin->delete();

        // プラグインフォルダを削除
        $pluginDir = base_path('plugins/' . $plugin->name);
        File::deleteDirectory($pluginDir);

        return redirect()->route('admin.settings.plugins.index')->with('success', 'プラグインをアンインストールしました');
    }
}
