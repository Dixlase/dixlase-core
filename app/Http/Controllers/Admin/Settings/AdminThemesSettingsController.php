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
use Illuminate\Http\Request;
use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class AdminThemesSettingsController extends AdminLoggedInController
{
    //

    // テーマ一覧
    public function index()
    {

        // デフォルトテーマを取得
        $defaultTheme = Theme::where('slug', config('themes.default_theme_slug', 'dixlase-default-theme'))->first();

        // 現在有効なテーマを取得
        $activeThemeId = DB::table('theme_settings')->value('active_theme_id');

        // 他のテーマを取得（デフォルトテーマ以外）
        $themes = Theme::where('slug', '!=', config('themes.default_theme_slug', 'dixlase-default-theme'))->paginate(10); // 1ページあたり10件表示

        // テーマ設定機能の有無をチェック
        $themesWithSettings = $this->checkThemeSettings($themes);
        if ($defaultTheme) {
            $defaultTheme->has_settings = $this->hasThemeSettings($defaultTheme);
        }

        $this->viewParams['defaultTheme'] = $defaultTheme;
        $this->viewParams['themes'] = $themesWithSettings;
        $this->viewParams['activeThemeId'] = $activeThemeId;
        return view('admin::settings.themes.index', $this->viewParams);
    }

    /**
     * テーマに設定機能があるかチェック
     */
    private function hasThemeSettings($theme)
    {
        $themeSlug = $theme->slug;
        $themeDirectory = $theme->directory;
        
        // ルートファイルの存在確認
        $routeFile = base_path("themes/{$themeDirectory}/routes/admin.php");
        
        if (!file_exists($routeFile)) {
            return false;
        }
        
        // ルートファイルの内容を確認
        $routeContent = file_get_contents($routeFile);
        
        // 設定ルートが定義されているかチェック（新しいルート構造に対応）
        // '/settings/themes/settings' ルートと 'settings' メソッドの両方をチェック
        return str_contains($routeContent, '/settings/themes/settings') 
            && str_contains($routeContent, 'settings');
    }

    /**
     * 複数テーマの設定機能チェック
     */
    private function checkThemeSettings($themes)
    {
        foreach ($themes as $theme) {
            $theme->has_settings = $this->hasThemeSettings($theme);
        }
        
        return $themes;
    }

    // テーマインストール
    public function install()
    {
        return view('admin::settings.themes.install', $this->viewParams);
    }



    // テーマのアップロード
    public function upload(Request $request)
    {
        $request->validate([
            'theme' => 'required|mimes:zip',
        ]);

        $zip = new \ZipArchive;
        $uploadedFile = $request->file('theme');

        $themeDirectory = resource_path('views/themes/');

        // ZIPファイル名からディレクトリ名を生成
        $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME); // ZIP名
        $directoryName = Str::slug($originalName); // デフォルトのディレクトリ名（スラッグ化）
        $themePath = $themeDirectory . $directoryName;

        if (is_dir($themePath)) {
            return redirect()->route('admin.settings.themes.install')->with('error', "テーマディレクトリ '{$directoryName}' がすでに存在します。");
        }

        if ($zip->open($uploadedFile->path()) === true) {
            try {
                // ZIP内の最初のディレクトリ名を取得する
                $extractedRootDir = null;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    $filename = $stat['name'];

                    // 最初のディレクトリ名を取得
                    if (strpos($filename, '/') !== false) {
                        $extractedRootDir = explode('/', $filename)[0];
                        break;
                    }
                }

                if (!$extractedRootDir) {
                    return redirect()->route('admin.settings.themes.install')
                        ->with('error', 'ZIPファイルに有効なディレクトリが含まれていません。');
                }

                // ZIPを解凍
                $zip->extractTo($themeDirectory);
                $zip->close();

                // 解凍されたディレクトリのパス
                $extractedDirPath = $themeDirectory . '/' . $extractedRootDir;

                // リネーム後のディレクトリパス
                $renamedDirPath = $themeDirectory . '/' . $directoryName;

                // 解凍されたディレクトリをリネーム
                if (is_dir($extractedDirPath) && basename($extractedDirPath) !== $directoryName) {
                    File::move($extractedDirPath, $renamedDirPath);
                    $themePath = $renamedDirPath;
                }


                // theme.json の読み取り
                $themeJsonPath = $themePath . '/theme.json';
                $themeData = [];
                $slug = $directoryName; // デフォルトスラッグはディレクトリ名

                if (file_exists($themeJsonPath)) {
                    $themeData = json_decode(file_get_contents($themeJsonPath), true);

                    // theme.jsonからスラッグを取得、なければディレクトリ名を使用
                    $slug = isset($themeData['slug']) && !empty($themeData['slug'])
                        ? Str::slug($themeData['slug'])
                        : $directoryName;

                    // スラッグ名の重複チェック
                    if (Theme::where('slug', $slug)->exists()) {
                        File::deleteDirectory($themePath);
                        return redirect()->route('admin.settings.themes.install')->with('error', "同じスラッグ名 '{$slug}' のテーマがすでに存在します。");
                    }

                    // テーマ情報をデータベースに登録
                    Theme::create([
                        'name' => $themeData['name'] ?? $directoryName,
                        'slug' => $slug,            // theme.jsonまたはディレクトリ名から生成したスラッグ
                        'directory' => $directoryName, // ディレクトリ名
                        'version' => $themeData['version'] ?? '1.0',
                    ]);

                    return redirect()->route('admin.settings.themes.index')->with('success', 'テーマがインストールされました！');
                } else {
                    // theme.json が見つからない場合
                    File::deleteDirectory($themePath);
                    return redirect()->route('admin.settings.themes.install')->with('error', 'theme.json が見つかりません。');
                }
            } catch (\Exception $e) {
                // 例外発生時にディレクトリを削除
                File::deleteDirectory($themePath);
                return redirect()->route('admin.settings.themes.install')->with('error', 'データベースへの登録中にエラーが発生しました: ' . $e->getMessage());
            }
        } else {
            return back()->with('error', 'テンプレートの解凍に失敗しました。');
        }
    }

    // テーマの削除
    public function delete($slug)
    {
        $theme = Theme::where('slug', $slug)->first();
        if (!$theme) {
            return redirect()->route('admin.settings.themes.index')->with('error', 'テーマが見つかりません。');
        }

        // デフォルトテンプレートは削除禁止
        if ($theme->slug === config('themes.default_theme_slug', 'dixlase-default-theme')) {
            return redirect()->route('admin.settings.themes.index')->with('error', 'デフォルトテーマは削除できません。');
        }

        // テーマディレクトリの確認
        $themeDirectory = resource_path('views/themes/' . $theme->directory);


        // 安全チェック: 削除対象が `themes` そのものではないことを確認
        if ($theme->directory === '' || realpath($themeDirectory) === realpath(resource_path('views/themes'))) {
            return redirect()->route('admin.settings.themes.index')->with('error', '無効なディレクトリパスです。');
        }

        // ディレクトリ削除とDBからの削除
        try {
            if (is_dir($themeDirectory)) {
                if (!File::deleteDirectory($themeDirectory)) {
                    throw new \Exception('ディレクトリの削除に失敗しました。');
                }
            }
            // DBからテーマ情報を削除
            $theme->delete();
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.themes.index')->with('error', $e->getMessage());
        }

        session()->flash('success', 'テーマが削除されました！');
        session()->save();

        return redirect()->route('admin.settings.themes.index');
    }



    // テーマの切り替え
    public function activate($id)
    {

        $theme = Theme::where('id', $id)->first();

        if (!$theme) {
            return back()->with('error', 'テーマが見つかりません。');
        }

        // アクティブテーマを更新
        DB::table('theme_settings')->update(['active_theme_id' => $theme->id, 'updated_at' => now()]);

        try {
            // シンボリックリンクを更新
            update_theme_symlink($theme->directory);
        } catch (\Exception $e) {
            return back()->with('error', "シンボリックリンクの更新に失敗しました: {$e->getMessage()}");
        }


        return redirect()->route('admin.settings.themes.index')->with('success', "テーマ '{$theme->name}' が有効化されました。");
    }
}
