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

namespace App\Http\Controllers\Admin\Contents;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;
use App\Models\Theme;
use Illuminate\Support\Facades\File;


class AdminContentsThemesController extends AdminController
{
    //

    // テーマ一覧
    public function index()
    {
        $this->viewParams['heading'] = 'admin.features.contents.themes.index.heading';
        $themes = Theme::paginate(10); // 1ページあたり10件表示
        $this->viewParams['themes'] = $themes;
        return view('admin::contents.themes.index', $this->viewParams);
    }


    // テーマのアップロード
    public function uploadTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|mimes:zip',
        ]);

        $zip = new \ZipArchive;
        $zipPath = $request->file('theme')->path();

        if ($zip->open($zipPath) === true) {
            $extractPath = resource_path('views/themes/');
            $zip->extractTo($extractPath);
            $zip->close();

            // テンプレート情報を登録
            Theme::create([
                'name' => $request->file('theme')->getClientOriginalName(),
                'slug' => basename($zipPath, '.zip'),
                'version' => '1.0', // ZIPファイル内に `theme.json` を含めることで動的に取得も可能
            ]);

            return back()->with('success', 'テンプレートがインストールされました！');
        } else {
            return back()->with('error', 'テンプレートの解凍に失敗しました。');
        }
    }

    // テーマの削除
    public function deleteTheme($slug)
    {
        $theme = Theme::where('slug', $slug)->first();
        if (!$theme) {
            return back()->with('error', 'テンプレートが見つかりません。');
        }

        // デフォルトテンプレートは削除禁止
        if ($theme->slug === 'default') {
            return back()->with('error', 'デフォルトテンプレートは削除できません。');
        }

        // ファイル削除
        $themePath = resource_path("views/themes/{$slug}");
        if (is_dir($themePath)) {
            File::deleteDirectory($themePath);
        }

        // DB削除
        $theme->delete();

        return back()->with('success', 'テンプレートが削除されました！');
    }

    // テーマの切り替え
    public function switchTheme($slug)
    {
        Theme::query()->update(['is_active' => false]); // 全テーマを無効化
        Theme::where('slug', $slug)->update(['is_active' => true]); // 指定テーマを有効化

        // 設定を保存
        config(['app.theme' => $slug]);

        return back()->with('success', 'テンプレートが切り替えられました！');
    }
}
