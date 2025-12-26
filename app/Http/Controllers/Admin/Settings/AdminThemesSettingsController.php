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
use App\Models\ThemeAudit;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;
use App\Helpers\ComposerLocalHelper;
use App\Services\Theme\ThemePermissionService;
use App\Services\ExtensionOperationService;
use App\Services\Csp\CspDiagnosticService;



class AdminThemesSettingsController extends AdminLoggedInController
{
    //

    // テーマ一覧
    public function index()
    {
        // インストール済みテーマを取得（プラグイン管理と同じロジック）
        $themes = Theme::all();

        // 現在有効なテーマを取得
        $themeSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        $activeThemeId = $themeSetting ? (int)$themeSetting->value : null;

        // 権限サービスとCSP診断サービスを取得
        $permissionService = app(ThemePermissionService::class);
        $cspDiagnosticService = app(CspDiagnosticService::class);

        // テーマ設定機能の有無をチェック
        // データベースのhas_settingsカラムを優先し、nullの場合のみファイルチェック
        foreach ($themes as $theme) {
            if ($theme->has_settings === null) {
                $theme->has_settings = $this->hasThemeSettings($theme);
            }
            
            // 権限サマリーを取得（監査結果を含む）
            $summary = $permissionService->getSummary($theme->slug);
            $summary['audit'] = $this->getThemeAuditResult($theme->slug);
            $theme->permission_summary = $summary;
            
            // CSP診断結果を取得
            $themePath = base_path('themes/' . $theme->slug);
            $theme->csp_diagnostic = $cspDiagnosticService->diagnoseTheme($themePath);
        }

        // アンインストール済みテーマを検出
        $uninstalledThemes = $this->getUninstalledThemes();
        
        // アンインストール済みテーマにも権限サマリーと監査結果を追加
        foreach ($uninstalledThemes as &$theme) {
            $summary = $permissionService->getSummary($theme['slug']);
            $summary['audit'] = $this->getThemeAuditResult($theme['slug']);
            $theme['permission_summary'] = $summary;
            
            // CSP診断結果を取得
            $themePath = base_path('themes/' . $theme['slug']);
            $theme['csp_diagnostic'] = $cspDiagnosticService->diagnoseTheme($themePath);
        }

        $this->viewParams['themes'] = $themes;
        $this->viewParams['uninstalledThemes'] = $uninstalledThemes;
        $this->viewParams['activeThemeId'] = $activeThemeId;
        return view('admin::settings.themes.index', $this->viewParams);
    }
    
    /**
     * テーマの監査結果をDBから取得
     *
     * @param string $themeSlug
     * @return array
     */
    protected function getThemeAuditResult(string $themeSlug): array
    {
        $audit = ThemeAudit::getBySlug($themeSlug);
        
        if ($audit) {
            return $audit->toAuditArray();
        }
        
        // 監査結果がない場合は空の結果を返す
        return [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 0,
            'audited_at' => null,
        ];
    }
    
    /**
     * テーマを監査してDBに保存
     *
     * @param string $themeSlug
     * @return array
     */
    protected function runThemeAudit(string $themeSlug): array
    {
        try {
            Log::info('Theme audit starting', ['theme' => $themeSlug]);
            
            Artisan::call('dls:theme:audit', [
                'theme' => $themeSlug,
                '--json' => true,
            ]);
            
            $output = trim(Artisan::output());
            
            Log::info('Theme audit output', [
                'theme' => $themeSlug,
                'output_length' => strlen($output),
                'output_preview' => substr($output, 0, 500),
            ]);
            
            $result = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Theme audit JSON parse error', [
                    'theme' => $themeSlug,
                    'error' => json_last_error_msg(),
                    'output' => $output,
                ]);
            }
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
                // mismatchesのevidenceを制限（DBサイズ削減）
                $mismatches = $result['mismatches'] ?? [];
                foreach ($mismatches as &$mismatch) {
                    if (isset($mismatch['evidence']) && is_array($mismatch['evidence'])) {
                        // evidenceは最大3件まで
                        $mismatch['evidence'] = array_slice($mismatch['evidence'], 0, 3);
                    }
                }
                unset($mismatch);
                
                $auditData = [
                    'has_mismatches' => !empty($mismatches),
                    'mismatches' => $mismatches,
                    'matches_count' => count($result['matches'] ?? []),
                    'total_checked' => $result['total_checked'] ?? 0,
                    'risk_level' => $result['risk_level'] ?? null,
                    'risk_reasons' => $result['risk_reasons'] ?? [],
                ];
                
                Log::info('Theme audit data prepared', ['theme' => $themeSlug, 'mismatches_count' => count($mismatches)]);
                
                // DBに保存
                $audit = ThemeAudit::saveAuditResult($themeSlug, $auditData);
                
                Log::info('Theme audit saved', ['theme' => $themeSlug, 'audit_id' => $audit->id]);
                
                return $audit->toAuditArray();
            }
        } catch (\Exception $e) {
            Log::error('Theme audit failed', [
                'theme' => $themeSlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 0,
            'audited_at' => null,
        ];
    }
    
    /**
     * テーマを手動で監査（Ajax）
     */
    public function audit(Request $request)
    {
        // JSONリクエストの場合はjson()で取得
        $slug = $request->json('slug') ?? $request->input('slug');
        
        Log::info('Theme audit request', ['slug' => $slug, 'content_type' => $request->header('Content-Type')]);
        
        if (!$slug) {
            return response()->json([
                'success' => false,
                'message' => __('admin/settings/themes.audit.invalid_slug'),
            ], 400);
        }
        
        try {
            $result = $this->runThemeAudit($slug);
            
            return response()->json([
                'success' => true,
                'message' => __('admin/settings/themes.audit.completed'),
                'audit' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Theme audit controller error', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('admin/settings/themes.audit.failed') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    // テーマ追加
    public function add()
    {
        return view('admin::settings.themes.add', $this->viewParams);
    }

        /**
     * テーマのアップロード（ZIPファイルの解凍とファイル配置のみ）
     */
    public function upload(Request $request)
    {
        $request->validate([
            'theme' => 'required|mimes:zip',
        ]);

        $zip = new \ZipArchive;
        $uploadedFile = $request->file('theme');
        $themeDirectory = resource_path('views/themes/');

        // ZIPファイル名からディレクトリ名を生成
        $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $directoryName = Str::slug($originalName);
        $themePath = $themeDirectory . $directoryName;

        if (is_dir($themePath)) {
            return redirect()->route('admin.settings.themes.add')
                ->with('error', "テーマディレクトリ '{$directoryName}' がすでに存在します。");
        }

        if ($zip->open($uploadedFile->path()) === true) {
            try {
                // ZIP内の最初のディレクトリ名を取得
                $extractedRootDir = null;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    $filename = $stat['name'];

                    if (strpos($filename, '/') !== false) {
                        $extractedRootDir = explode('/', $filename)[0];
                        break;
                    }
                }

                if (!$extractedRootDir) {
                    return redirect()->route('admin.settings.themes.add')
                        ->with('error', 'ZIPファイルに有効なディレクトリが含まれていません。');
                }

                // ZIPを解凍
                $zip->extractTo($themeDirectory);
                $zip->close();

                // 解凍されたディレクトリのパス
                $extractedDirPath = $themeDirectory . '/' . $extractedRootDir;
                $renamedDirPath = $themeDirectory . '/' . $directoryName;

                // 解凍されたディレクトリをリネーム
                if (is_dir($extractedDirPath) && basename($extractedDirPath) !== $directoryName) {
                    File::move($extractedDirPath, $renamedDirPath);
                }

                // theme.jsonの存在確認
                $themeJsonPath = $renamedDirPath . '/theme.json';
                if (!file_exists($themeJsonPath)) {
                    File::deleteDirectory($renamedDirPath);
                    return redirect()->route('admin.settings.themes.add')
                        ->with('error', 'theme.json が見つかりません。');
                }

                // .git/info/excludeにテーマの除外ルールを追加
                GitExcludeHelper::addThemeExclusion($directoryName);

                // .gitignoreにテーマの除外ルールを追加
                GitIgnoreHelper::addThemeExclusion($directoryName);
                
                // composer.local.jsonを更新
                ComposerLocalHelper::syncAutoload();

                return redirect()->route('admin.settings.themes.index')
                    ->with('success', 'テーマのアップロードが完了しました。一覧からインストールしてください。')
                    ->with('uploaded_theme_directory', $directoryName);
                    
            } catch (\Exception $e) {
                // 例外発生時にディレクトリを削除
                if (isset($renamedDirPath) && is_dir($renamedDirPath)) {
                    File::deleteDirectory($renamedDirPath);
                }
                Log::error('Theme upload failed', [
                    'directory' => $directoryName,
                    'error' => $e->getMessage()
                ]);
                return redirect()->route('admin.settings.themes.add')
                    ->with('error', 'テーマのアップロードに失敗しました: ' . $e->getMessage());
            }
        } else {
            return redirect()->route('admin.settings.themes.add')
                ->with('error', 'ZIPファイルの解凍に失敗しました。');
        }
    }

    /**
     * アンインストール済みテーマをインストール
     */
    public function install(Request $request)
    {
        $request->validate([
            'directory' => 'required|string',
        ]);
        
        $themeDir = $request->input('directory');
        
        try {
            // Artisanコマンドを実行してテーマをインストール（--forceオプション付き）
            $exitCode = Artisan::call('dls:theme:install', [
                'themeName' => $themeDir,
                '--force' => true,
                '--no-interaction' => true,
            ]);
            
            if ($exitCode !== 0) {
                $output = Artisan::output();
                Log::error('Theme installation command failed', [
                    'directory' => $themeDir,
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                return redirect()->back()->with('error', 'テーマのインストールに失敗しました。');
            }
            
            // .git/info/excludeと.gitignoreにテーマの除外ルールを追加
            GitExcludeHelper::addThemeExclusion($themeDir);
            GitIgnoreHelper::addThemeExclusion($themeDir);
            
            // インストールされたテーマを取得して監査・通知
            $theme = Theme::where('directory', $themeDir)->first();
            if ($theme) {
                // インストール後に監査を実行
                $this->runThemeAudit($theme->slug);
                
                $permissionService = app(ThemePermissionService::class);
                $summary = $permissionService->getSummary($theme->slug);
                
                app(ExtensionOperationService::class)->recordOperation(
                    ExtensionOperationService::TYPE_THEME,
                    ExtensionOperationService::OPERATION_INSTALLED,
                    [
                        'name' => $theme->name,
                        'slug' => $theme->slug,
                        'version' => $theme->version ?? null,
                        'health_status' => $summary['risk_level'] ?? 'unknown',
                    ]
                );
            }
            
            return redirect()->route('admin.settings.themes.index')
                ->with('success', 'テーマが正常にインストールされました。');
        } catch (\Exception $e) {
            Log::error('Theme installation failed', [
                'directory' => $themeDir,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'テーマのインストールに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * テーマをアンインストール
     */
    public function uninstall($id, Request $request)
    {
        $theme = Theme::findOrFail($id);
        
        // デフォルトテーマはアンインストールできない
        $defaultThemeSlug = config('themes.default_theme_slug', 'dixlase-default-theme');
        if ($theme->slug === $defaultThemeSlug) {
            return back()->with('error', 'デフォルトテーマはアンインストールできません。');
        }
        
        // 有効化中のテーマはアンインストールできない
        $themeSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        $activeThemeId = $themeSetting ? (int)$themeSetting->value : null;
        
        if ($activeThemeId && $theme->id == $activeThemeId) {
            return back()->with('error', '有効化中のテーマはアンインストールできません。');
        }

        // 通知用にテーマ情報を保存
        $themeData = [
            'name' => $theme->name,
            'slug' => $theme->slug,
            'version' => $theme->version ?? null,
            'health_status' => 'low', // アンインストール時は健全性警告不要
        ];

        try {
            // コマンドを使用してアンインストール
            $options = [
                'themeName' => $theme->slug,
                '--force' => true,
                '--no-interaction' => true,
            ];
            
            // DBデータも削除する場合
            if ($request->has('remove_db_data')) {
                $options['--rollback'] = true;
            }
            
            Artisan::call('dls:theme:uninstall', $options);

            // 拡張機能操作の通知・ログ記録
            app(ExtensionOperationService::class)->recordOperation(
                ExtensionOperationService::TYPE_THEME,
                ExtensionOperationService::OPERATION_UNINSTALLED,
                $themeData
            );

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
     * テーマを切り替え（有効化）
     */
    public function switch($id)
    {
        $theme = Theme::findOrFail($id);

        try {
            // 有効化前に監査を実行（最新の状態を確認）
            $this->runThemeAudit($theme->slug);
            
            // Artisanコマンドを使用してテーマを切り替え
            $exitCode = Artisan::call('dls:theme:switch', [
                'themeName' => $theme->slug,
            ]);
            
            if ($exitCode !== 0) {
                $output = Artisan::output();
                Log::error('Theme switch command failed', [
                    'slug' => $theme->slug,
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                return redirect()->back()->with('error', 'テーマの切り替えに失敗しました。');
            }
            
            // .git/info/excludeと.gitignoreにテーマの除外ルールを追加
            GitExcludeHelper::addThemeExclusion($theme->directory);
            GitIgnoreHelper::addThemeExclusion($theme->directory);
            
            // 拡張機能操作の通知・ログ記録
            $permissionService = app(ThemePermissionService::class);
            $summary = $permissionService->getSummary($theme->slug);
            
            app(ExtensionOperationService::class)->recordOperation(
                ExtensionOperationService::TYPE_THEME,
                ExtensionOperationService::OPERATION_ENABLED,
                [
                    'name' => $theme->name,
                    'slug' => $theme->slug,
                    'version' => $theme->version ?? null,
                    'health_status' => $summary['risk_level'] ?? 'unknown',
                ]
            );
            
            return redirect()->route('admin.settings.themes.index')
                ->with('success', 'テーマが切り替えられました。');
        } catch (\Exception $e) {
            Log::error('Theme switch failed', [
                'theme' => $theme->name,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'テーマの切り替えに失敗しました: ' . $e->getMessage());
        }
    }


    /**
     * テーマを完全に削除（ファイル + DBレコード）
     */
    public function delete(Request $request)
    {
        $request->validate([
            'directory' => 'required|string',
        ]);
        
        $themeDir = $request->input('directory');
        
        // DBレコードが存在するか確認
        $theme = Theme::where('directory', $themeDir)->first();
        
        try {
            // DBレコードが存在する場合は先にアンインストール
            if ($theme) {
                $exitCode = Artisan::call('dls:theme:uninstall', [
                    'themeName' => $theme->slug,
                    '--force' => true,
                    '--no-interaction' => true,
                ]);
                
                if ($exitCode !== 0) {
                    $output = Artisan::output();
                    Log::error('Theme uninstall command failed', [
                        'directory' => $themeDir,
                        'exit_code' => $exitCode,
                        'output' => $output
                    ]);
                    return redirect()->back()->with('error', 'テーマのアンインストールに失敗しました。');
                }
            }
            
            // テーマディレクトリを削除
            $exitCode = Artisan::call('dls:theme:delete', [
                'themeDirectory' => $themeDir,
                '--force' => true,
            ]);
            
            if ($exitCode !== 0) {
                $output = Artisan::output();
                Log::error('Theme delete command failed', [
                    'directory' => $themeDir,
                    'exit_code' => $exitCode,
                    'output' => $output
                ]);
                return redirect()->back()->with('error', 'テーマの削除に失敗しました。');
            }
            
            // .git/info/excludeと.gitignoreからテーマの除外ルールを削除
            GitExcludeHelper::removeThemeExclusion($themeDir);
            GitIgnoreHelper::removeThemeExclusion($themeDir);
            
            return redirect()->route('admin.settings.themes.index')
                ->with('success', 'テーマが正常に削除されました。');
        } catch (\Exception $e) {
            Log::error('Theme deletion failed', [
                'directory' => $themeDir,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'テーマの削除に失敗しました: ' . $e->getMessage());
        }
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
            Log::debug('Themes path does not exist', ['path' => $themesPath]);
            return $uninstalledThemes;
        }
        
        // themesディレクトリ内のすべてのディレクトリを取得
        $directories = File::directories($themesPath);
        Log::debug('Found theme directories', [
            'count' => count($directories),
            'directories' => array_map('basename', $directories)
        ]);
        
        // インストール済みテーマのディレクトリ名を取得（プラグイン管理と同じロジック）
        $installedDirectories = Theme::pluck('directory')->toArray();
        Log::debug('Installed theme directories from DB', [
            'count' => count($installedDirectories),
            'directories' => $installedDirectories
        ]);
        
        foreach ($directories as $directory) {
            $dirName = basename($directory);
            
            Log::debug('Checking theme directory', [
                'directory' => $dirName,
                'is_installed' => in_array($dirName, $installedDirectories)
            ]);
            
            // DBに登録されていないテーマを検出
            if (!in_array($dirName, $installedDirectories)) {
                $themeInfo = $this->getThemeInfoFromDirectory($dirName);
                Log::debug('Theme info retrieved', [
                    'directory' => $dirName,
                    'info_is_null' => is_null($themeInfo),
                    'info' => $themeInfo
                ]);
                
                if ($themeInfo) {
                    $uninstalledThemes[] = $themeInfo;
                }
            }
        }
        
        Log::debug('Final uninstalled themes', [
            'count' => count($uninstalledThemes),
            'themes' => collect($uninstalledThemes)->pluck('directory')->toArray()
        ]);
        
        return $uninstalledThemes;
    }

    /**
     * ディレクトリからテーマ情報を取得
     * theme.json優先、composer.jsonをフォールバック
     */
    private function getThemeInfoFromDirectory($dirName)
    {
        $themeJsonPath = base_path("themes/{$dirName}/theme.json");
        $composerPath = base_path("themes/{$dirName}/composer.json");
        
        // theme.jsonが存在する場合は優先的に使用
        if (File::exists($themeJsonPath)) {
            try {
                $jsonContent = File::get($themeJsonPath);
                $themeData = json_decode($jsonContent, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    $description = $themeData['description'] ?? null;
                    if (is_array($description)) {
                        $description = $description['en'] ?? $description['ja'] ?? null;
                    }
                    
                    return [
                        'directory' => $dirName,
                        'name' => $themeData['name'] ?? $dirName,
                        'description' => $description,
                        'version' => $themeData['version'] ?? '1.0.0',
                        'author' => $themeData['author'] ?? null,
                        'email' => $themeData['email'] ?? null,
                        'url' => $themeData['url'] ?? $themeData['homepage'] ?? $themeData['web'] ?? null,
                        'license' => $themeData['license'] ?? null,
                        'package_name' => $themeData['package_name'] ?? null,
                        'slug' => $themeData['slug'] ?? Str::slug($dirName),
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Failed to read theme.json', [
                    'directory' => $dirName,
                    'error' => $e->getMessage()
                ]);
                // theme.jsonの読み込みに失敗した場合はcomposer.jsonにフォールバック
            }
        }
        
        // theme.jsonが存在しない、または読み込みに失敗した場合はcomposer.jsonを使用
        if (!File::exists($composerPath)) {
            return null;
        }
        
        try {
            $jsonContent = File::get($composerPath);
            $composerData = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            
            // display-nameの取得（extra.dixlase.display-name → extra.display-name → ディレクトリ名）
            $displayName = $composerData['extra']['dixlase']['display-name'] 
                ?? $composerData['extra']['display-name'] 
                ?? $dirName;
            
            $authors = $composerData['authors'] ?? [];
            $firstAuthor = $authors[0] ?? [];
            
            // versionの取得（extra.dixlase.version → version → デフォルト）
            $version = $composerData['extra']['dixlase']['version'] 
                ?? $composerData['version'] 
                ?? '1.0.0';
            
            // slugの取得（extra.dixlase.slug → extra.slug → ディレクトリ名からケバブケース）
            $slug = $composerData['extra']['dixlase']['slug'] 
                ?? $composerData['extra']['slug'] 
                ?? Str::slug($dirName);
            
            return [
                'directory' => $dirName,
                'name' => $displayName,
                'description' => $composerData['description'] ?? null,
                'version' => $version,
                'author' => $firstAuthor['name'] ?? null,
                'email' => $firstAuthor['email'] ?? null,
                'url' => $firstAuthor['homepage'] ?? null,
                'license' => $composerData['license'] ?? null,
                'package_name' => $composerData['name'] ?? null,
                'slug' => $slug,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to read composer.json', [
                'directory' => $dirName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * テーマのシーダーを実行
     */
    protected function runThemeSeeder(string $themeDirectory): void
    {
        try {
            // Artisanコマンドを使用してシーダーを実行
            Artisan::call('dls:theme:seed', [
                'themeName' => $themeDirectory,
            ]);
            
            Log::info('Theme seeder executed via command', [
                'theme' => $themeDirectory
            ]);
        } catch (\Exception $e) {
            // シーダーの実行に失敗してもインストールは続行
            Log::warning('Theme seeder execution failed', [
                'theme' => $themeDirectory,
                'error' => $e->getMessage()
            ]);
        }
    }
}
