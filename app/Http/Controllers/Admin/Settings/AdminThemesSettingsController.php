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
use App\Helpers\GitExcludeHelper;
use App\Helpers\ComposerLocalHelper;



class AdminThemesSettingsController extends AdminLoggedInController
{
    //

    // テーマ一覧
    public function index()
    {
        // インストール済みテーマを取得
        $themes = Theme::all();

        // 現在有効なテーマを取得
        $activeThemeId = DB::table('theme_settings')->value('enabled_theme_id');

        // テーマ設定機能の有無をチェック
        foreach ($themes as $theme) {
            $theme->has_settings = $this->hasThemeSettings($theme);
        }

        // アンインストール済みテーマを検出
        $uninstalledThemes = $this->getUninstalledThemes();

        $this->viewParams['themes'] = $themes;
        $this->viewParams['uninstalledThemes'] = $uninstalledThemes;
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
     * アンインストール済みテーマを検出
     */
    private function getUninstalledThemes()
    {
        $uninstalledThemes = [];
        $themesPath = base_path('themes');
        
        if (!File::exists($themesPath)) {
            return $uninstalledThemes;
        }
        
        // themesディレクトリ内のすべてのディレクトリを取得
        $directories = File::directories($themesPath);
        
        // インストール済みテーマのディレクトリ名を取得
        $installedDirectories = Theme::pluck('directory')->toArray();
        
        foreach ($directories as $directory) {
            $dirName = basename($directory);
            
            // DBに登録されていないテーマを検出
            if (!in_array($dirName, $installedDirectories)) {
                $themeInfo = $this->getThemeInfoFromDirectory($dirName);
                if ($themeInfo) {
                    $uninstalledThemes[] = $themeInfo;
                }
            }
        }
        
        return $uninstalledThemes;
    }

    /**
     * ディレクトリからテーマ情報を取得
     */
    private function getThemeInfoFromDirectory($dirName)
    {
        $composerPath = base_path("themes/{$dirName}/composer.json");
        
        if (!File::exists($composerPath)) {
            return null;
        }
        
        try {
            $jsonContent = File::get($composerPath);
            $composerData = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            
            $displayName = $composerData['extra']['display-name'] ?? $dirName;
            $authors = $composerData['authors'] ?? [];
            $firstAuthor = $authors[0] ?? [];
            
            return [
                'directory' => $dirName,
                'name' => $displayName,
                'description' => $composerData['description'] ?? null,
                'version' => $composerData['version'] ?? '1.0.0',
                'author' => $firstAuthor['name'] ?? null,
                'email' => $firstAuthor['email'] ?? null,
                'web' => $firstAuthor['homepage'] ?? null,
                'license' => $composerData['license'] ?? null,
                'package_name' => $composerData['name'] ?? null,
                'slug' => $composerData['extra']['slug'] ?? Str::slug($dirName),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to read theme info', [
                'directory' => $dirName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
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

                    // テーマのシーダーを実行（権限設定など）
                    $this->runThemeSeeder($directoryName);

                    // .git/info/excludeにテーマの除外ルールを追加
                    GitExcludeHelper::addThemeExclusion($directoryName);
                    
                    // composer.local.jsonを更新
                    ComposerLocalHelper::syncAutoload();

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
            
            // .git/info/excludeからテーマの除外ルールを削除
            GitExcludeHelper::removeThemeExclusion($theme->directory);
            
            // composer.local.jsonを更新
            ComposerLocalHelper::syncAutoload();
            
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
        $theme = Theme::findOrFail($id);

        // アクティブテーマを更新
        DB::table('theme_settings')->update(['enabled_theme_id' => $theme->id, 'updated_at' => now()]);

        try {
            // シンボリックリンクを更新
            update_theme_symlink($theme->directory);
        } catch (\Exception $e) {
            Log::error('Theme symlink update failed', [
                'theme' => $theme->name,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', "シンボリックリンクの更新に失敗しました: {$e->getMessage()}");
        }

        return redirect()->route('admin.settings.themes.index')
            ->with('success', "テーマ '{$theme->name}' が有効化されました。");
    }

    /**
     * テーマをアンインストール
     */
    public function uninstall($id)
    {
        $theme = Theme::findOrFail($id);
        
        // デフォルトテーマはアンインストールできない
        $defaultThemeSlug = config('themes.default_theme_slug', 'dixlase-default-theme');
        if ($theme->slug === $defaultThemeSlug) {
            return back()->with('error', 'デフォルトテーマはアンインストールできません。');
        }
        
        // 有効化中のテーマはアンインストールできない
        $activeThemeId = DB::table('theme_settings')->value('enabled_theme_id');
        if ($theme->id == $activeThemeId) {
            return back()->with('error', '有効化中のテーマはアンインストールできません。');
        }

        try {
            // データベースから削除（ファイルは削除しない）
            $theme->delete();

            return redirect()->route('admin.settings.themes.index')
                ->with('success', 'テーマをアンインストールしました');
        } catch (\Exception $e) {
            Log::error('Theme uninstall failed', [
                'theme' => $theme->name,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'テーマのアンインストールに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * アンインストール済みテーマをインストール
     */
    public function installFromDirectory(Request $request)
    {
        $request->validate([
            'directory' => 'required|string',
        ]);
        
        $themeDir = $request->input('directory');
        $themePath = base_path("themes/{$themeDir}");
        
        if (!File::exists($themePath)) {
            return redirect()->back()->with('error', 'テーマディレクトリが見つかりません。');
        }
        
        $composerPath = base_path("themes/{$themeDir}/composer.json");
        
        if (!File::exists($composerPath)) {
            return redirect()->back()->with('error', 'composer.json が見つかりません。');
        }
        
        try {
            $jsonContent = File::get($composerPath);
            $composerData = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()->with('error', 'composer.json の解析に失敗しました: ' . json_last_error_msg());
            }
            
            // テーマ情報を取得
            $displayName = $composerData['extra']['display-name'] ?? $themeDir;
            $authors = $composerData['authors'] ?? [];
            $firstAuthor = $authors[0] ?? [];
            $author = $firstAuthor['name'] ?? null;
            $email  = $firstAuthor['email'] ?? null;
            $web    = $firstAuthor['homepage'] ?? null;
            $slug = $composerData['extra']['slug'] ?? Str::slug($themeDir);
            $version = $composerData['version'] ?? '1.0.0';
            $license = $composerData['license'] ?? null;
            $description = $composerData['description'] ?? null;
            $packageName = $composerData['name'] ?? null;
            $namespace = "Themes\\$themeDir";
            
            // DBにテーマ情報を登録
            $theme = Theme::create([
                'name'        => $displayName,
                'package_name' => $packageName,
                'directory'   => $themeDir,
                'slug'        => $slug,
                'namespace'   => $namespace,
                'description' => $description,
                'license'     => $license,
                'author'      => $author,
                'email'       => $email,
                'web'         => $web,
                'version'     => $version,
                'installed_at' => now(),
            ]);
            
            return redirect()->route('admin.settings.themes.index')
                ->with('success', 'テーマが正常にインストールされました。')
                ->with('installed_theme_id', $theme->id);
        } catch (\Exception $e) {
            Log::error('Theme installation failed', [
                'directory' => $themeDir,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'テーマのインストールに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * テーマディレクトリを完全に削除
     */
    public function deleteDirectory(Request $request)
    {
        $request->validate([
            'directory' => 'required|string',
        ]);
        
        $themeDir = $request->input('directory');
        
        // デフォルトテーマのディレクトリ名を取得
        $defaultTheme = Theme::where('slug', config('themes.default_theme_slug', 'dixlase-default-theme'))->first();
        if ($defaultTheme && $defaultTheme->directory === $themeDir) {
            return redirect()->back()->with('error', 'デフォルトテーマは削除できません。');
        }
        
        $themePath = base_path('themes/' . $themeDir);
        
        if (!File::exists($themePath)) {
            return redirect()->back()->with('error', 'テーマディレクトリが見つかりません。');
        }
        
        try {
            // テーマフォルダを完全に削除
            File::deleteDirectory($themePath);
            
            return redirect()->route('admin.settings.themes.index')
                ->with('success', 'テーマが正常に削除されました。');
        } catch (\Exception $e) {
            Log::error('Theme directory deletion failed', [
                'directory' => $themeDir,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'テーマの削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * テーマのシーダーを実行
     */
    protected function runThemeSeeder(string $themeDirectory): void
    {
        try {
            $seederClass = "Themes\\{$themeDirectory}\\Database\\Seeders\\DatabaseSeeder";
            
            // シーダークラスが存在するか確認
            if (class_exists($seederClass)) {
                $seeder = new $seederClass();
                $seeder->run();
                
                \Log::info("Theme seeder executed successfully", [
                    'theme' => $themeDirectory,
                    'seeder' => $seederClass
                ]);
            } else {
                \Log::info("Theme seeder not found (optional)", [
                    'theme' => $themeDirectory,
                    'seeder' => $seederClass
                ]);
            }
        } catch (\Exception $e) {
            // シーダーの実行に失敗してもインストールは続行
            \Log::warning("Theme seeder execution failed", [
                'theme' => $themeDirectory,
                'error' => $e->getMessage()
            ]);
        }
    }
}
