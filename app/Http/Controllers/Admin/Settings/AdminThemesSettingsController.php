<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use App\Helpers\ComposerLocalHelper;
use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\AdminThemeDeleteRequest;
use App\Http\Requests\Admin\Settings\AdminThemeInstallRequest;
use App\Http\Requests\Admin\Settings\AdminThemeUploadRequest;
use App\Models\Theme;
use App\Models\ThemeAudit;
use App\Presenters\Admin\ExtensionCardPresenter;
use App\Services\Csp\CspDiagnosticService;
use App\Services\Csp\CspExtensionLoader;
use App\Services\ExtensionOperationService;
use App\Services\Theme\ThemeHealthScorer;
use App\Services\Theme\ThemePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $activeThemeId = $themeSetting ? (int) $themeSetting->value : null;

        // 権限サービスとCSP診断サービスを取得
        $permissionService = app(ThemePermissionService::class);
        $cspDiagnosticService = app(CspDiagnosticService::class);
        $cspLoader = app(CspExtensionLoader::class);

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
            $themePath = base_path('themes/'.$theme->slug);
            $theme->csp_diagnostic = $cspDiagnosticService->diagnoseTheme($themePath);

            // CSP互換性情報を取得
            $theme->csp_compatibility = $cspLoader->getCspCompatibility('theme', $theme->slug);
        }

        // アンインストール済みテーマを検出
        $uninstalledThemes = $this->getUninstalledThemes();

        // アンインストール済みテーマにも権限サマリーと監査結果を追加
        foreach ($uninstalledThemes as &$theme) {
            $summary = $permissionService->getSummary($theme['slug']);
            $summary['audit'] = $this->getThemeAuditResult($theme['slug']);
            $theme['permission_summary'] = $summary;

            // CSP診断結果を取得
            $themePath = base_path('themes/'.$theme['slug']);
            $theme['csp_diagnostic'] = $cspDiagnosticService->diagnoseTheme($themePath);

            // CSP互換性情報を取得
            $theme['csp_compatibility'] = $cspLoader->getCspCompatibility('theme', $theme['slug']);
        }

        // カードデータを事前計算
        $themeCards = [];
        foreach ($themes as $theme) {
            $themeCards[] = ExtensionCardPresenter::forTheme($theme, $activeThemeId);
        }
        $uninstalledThemeCards = [];
        foreach ($uninstalledThemes as $theme) {
            $uninstalledThemeCards[] = ExtensionCardPresenter::forTheme($theme, $activeThemeId);
        }

        $this->viewParams['themes'] = $themes;
        $this->viewParams['uninstalledThemes'] = $uninstalledThemes;
        $this->viewParams['activeThemeId'] = $activeThemeId;
        $this->viewParams['themeCards'] = $themeCards;
        $this->viewParams['uninstalledThemeCards'] = $uninstalledThemeCards;

        return view('admin::settings.themes.index', $this->viewParams);
    }

    /**
     * テーマの監査結果をDBから取得
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

                // 署名情報を取得
                $permissionService = app(ThemePermissionService::class);
                $summary = $permissionService->getSummary($themeSlug);
                $signature = $summary['signature'] ?? [];

                // CSP準拠状況をコードスキャンで検証
                $cspScanner = app(\App\Services\Csp\CspComplianceScanner::class);
                $cspCompatibility = $cspScanner->scanTheme($themeSlug);

                $auditData = [
                    'has_mismatches' => ! empty($mismatches),
                    'mismatches' => $mismatches,
                    'matches_count' => count($result['matches'] ?? []),
                    'total_checked' => $result['total_checked'] ?? 0,
                    'risk_level' => $result['risk_level'] ?? null,
                    'risk_reasons' => $result['risk_reasons'] ?? [],
                    'signature_status' => $signature['status'] ?? 'unsigned',
                    'signature_signer' => $signature['signer'] ?? null,
                    'csp_status' => $cspCompatibility['status'] ?? 'not_checked',
                    'csp_requires_inline_js' => $cspCompatibility['requires_inline_js'] ?? false,
                    'csp_requires_inline_css' => $cspCompatibility['requires_inline_css'] ?? false,
                    'csp_violations' => $cspCompatibility['violations'] ?? [],
                    'csp_summary' => $cspCompatibility['summary'] ?? [],
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

        if (! $slug) {
            return response()->json([
                'success' => false,
                'message' => __('admin/settings/themes.audit.invalid_slug'),
            ], 400);
        }

        try {
            $result = $this->runThemeAudit($slug);

            // スキャン結果モーダル用に確認理由を翻訳済みで付与
            $result['formatted_attention_reasons'] = ExtensionCardPresenter::formatAttentionReasons(
                $result['risk_reasons'] ?? [],
                'admin/settings/themes/index'
            );

            // 健全性スコアの計算
            $healthScore = null;
            $healthStatus = null;
            $healthIssues = [];
            try {
                $healthScorer = app(ThemeHealthScorer::class);
                $healthResult = $healthScorer->calculate($slug);
                $healthScore = $healthResult->score;
                $healthStatus = $healthResult->status->value;
                $healthIssues = array_values(array_filter(
                    array_map(fn ($i) => $i->jsonSerialize(), $healthResult->issues),
                    fn ($i) => ($i['deduction'] ?? 0) !== 0,
                ));
            } catch (\Exception $e) {
                Log::error('Theme health score calculation failed after audit', [
                    'theme' => $slug,
                    'error' => $e->getMessage(),
                ]);
            }

            // 権限カテゴリ情報を取得
            $permissionService = app(ThemePermissionService::class);
            $summary = $permissionService->getSummary($slug);
            $categories = $summary['categories'] ?? [];

            return response()->json([
                'success' => true,
                'message' => __('admin/settings/themes.audit.completed'),
                'audit' => $result,
                'healthScore' => $healthScore,
                'healthStatus' => $healthStatus,
                'healthIssues' => $healthIssues,
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            Log::error('Theme audit controller error', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('admin/settings/themes.audit.failed').': '.$e->getMessage(),
            ], 500);
        }
    }

    // テーマ追加
    public function add()
    {
        $uploadMaxBytes = $this->parsePhpSize(ini_get('upload_max_filesize'));
        $this->viewParams['uploadMaxMB'] = number_format($uploadMaxBytes / 1048576, 2);

        return view('admin::settings.themes.add', $this->viewParams);
    }

    /**
     * テーマのアップロード（ZIPファイルの解凍とファイル配置のみ）
     */
    public function upload(AdminThemeUploadRequest $request)
    {
        $zip = new \ZipArchive();
        $uploadedFile = $request->file('theme');
        $themeDirectory = resource_path('views/themes/');

        // ZIPファイル名からディレクトリ名を生成
        $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $directoryName = Str::slug($originalName);
        $themePath = $themeDirectory.$directoryName;

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

                if (! $extractedRootDir) {
                    return redirect()->route('admin.settings.themes.add')
                        ->with('error', 'ZIPファイルに有効なディレクトリが含まれていません。');
                }

                // ZIPを解凍
                $zip->extractTo($themeDirectory);
                $zip->close();

                // 解凍されたディレクトリのパス
                $extractedDirPath = $themeDirectory.'/'.$extractedRootDir;
                $renamedDirPath = $themeDirectory.'/'.$directoryName;

                // 解凍されたディレクトリをリネーム
                if (is_dir($extractedDirPath) && basename($extractedDirPath) !== $directoryName) {
                    File::move($extractedDirPath, $renamedDirPath);
                }

                // theme.jsonの存在確認
                $themeJsonPath = $renamedDirPath.'/theme.json';
                if (! file_exists($themeJsonPath)) {
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
                    'error' => $e->getMessage(),
                ]);

                return redirect()->route('admin.settings.themes.add')
                    ->with('error', 'テーマのアップロードに失敗しました: '.$e->getMessage());
            }
        } else {
            return redirect()->route('admin.settings.themes.add')
                ->with('error', 'ZIPファイルの解凍に失敗しました。');
        }
    }

    /**
     * アンインストール済みテーマをインストール
     */
    public function install(AdminThemeInstallRequest $request)
    {
        $validated = $request->validated();

        $themeDir = $validated['directory'];

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
                    'output' => $output,
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
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'テーマのインストールに失敗しました: '.$e->getMessage());
        }
    }

    /**
     * テーマをアンインストール
     */
    public function uninstall($id, Request $request)
    {
        $theme = Theme::findOrFail($id);

        // デフォルトテーマはアンインストールできない
        $defaultThemeSlug = config('themes.default_theme_slug', 'dixlase-one-page');
        if ($theme->slug === $defaultThemeSlug) {
            return back()->with('error', 'デフォルトテーマはアンインストールできません。');
        }

        // 有効化中のテーマはアンインストールできない
        $themeSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        $activeThemeId = $themeSetting ? (int) $themeSetting->value : null;

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
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'テーマのアンインストールに失敗しました: '.$e->getMessage());
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
                    'output' => $output,
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
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'テーマの切り替えに失敗しました: '.$e->getMessage());
        }
    }

    /**
     * テーマを完全に削除（ファイル + DBレコード）
     */
    public function delete(AdminThemeDeleteRequest $request)
    {
        $validated = $request->validated();

        $themeDir = $validated['directory'];

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
                        'output' => $output,
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
                    'output' => $output,
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
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'テーマの削除に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * PHPのサイズ表記をバイト数に変換
     */
    private function parsePhpSize(string $sizeStr): int
    {
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
                $value = (int) $sizeStr;
                break;
        }

        return $value;
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

        if (! file_exists($routeFile)) {
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

        if (! File::exists($themesPath)) {
            return $uninstalledThemes;
        }

        // themesディレクトリ内のすべてのディレクトリを取得
        $directories = File::directories($themesPath);

        // インストール済みテーマのディレクトリ名を取得（プラグイン管理と同じロジック）
        $installedDirectories = Theme::pluck('directory')->toArray();

        foreach ($directories as $directory) {
            $dirName = basename($directory);

            // DBに登録されていないテーマを検出
            if (! in_array($dirName, $installedDirectories)) {
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
                    'error' => $e->getMessage(),
                ]);
                // theme.jsonの読み込みに失敗した場合はcomposer.jsonにフォールバック
            }
        }

        // theme.jsonが存在しない、または読み込みに失敗した場合はcomposer.jsonを使用
        if (! File::exists($composerPath)) {
            return;
        }

        try {
            $jsonContent = File::get($composerPath);
            $composerData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return;
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
                'error' => $e->getMessage(),
            ]);

            return;
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
                'theme' => $themeDirectory,
            ]);
        } catch (\Exception $e) {
            // シーダーの実行に失敗してもインストールは続行
            Log::warning('Theme seeder execution failed', [
                'theme' => $themeDirectory,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ソースから利用可能なテーマ一覧を返す（JSON API）
     */
    public function availableFromSource(\App\Services\Extension\ExtensionSourceManager $manager): \Illuminate\Http\JsonResponse
    {
        try {
            $available = $manager->listAvailableThemes();

            // インストール済み・ディスク上に存在するテーマを除外
            $installedSlugs = Theme::pluck('slug')->toArray();
            $diskSlugs = collect($this->getUninstalledThemes())->pluck('slug')->toArray();
            $excludeSlugs = array_merge($installedSlugs, $diskSlugs);

            $filtered = array_values(array_filter(
                $available,
                fn (array $theme) => ! in_array($theme['slug'], $excludeSlugs)
            ));

            return response()->json([
                'success' => true,
                'themes' => $filtered,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'themes' => [],
            ]);
        }
    }

    /**
     * ソースからテーマをダウンロードして配置
     */
    public function downloadFromSource(Request $request, \App\Services\Extension\ExtensionSourceManager $manager)
    {
        $request->validate([
            'slug' => 'required|string|max:100',
        ]);

        $slug = $request->input('slug');

        try {
            // ソースから ZIP をダウンロード
            $zipPath = $manager->download($slug, 'theme');

            // ZIP を展開して配置
            $result = $this->extractAndPlaceTheme($zipPath);

            if ($result['success']) {
                return redirect()->route('admin.settings.themes.index')
                    ->with('success', __('admin/settings/themes/add.messages.download_success', ['slug' => $slug]))
                    ->with('uploaded_theme_directory', $result['directory']);
            }

            return redirect()->route('admin.settings.themes.add')
                ->with('error', $result['error']);
        } catch (\Throwable $e) {
            Log::error('Theme download from source failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.settings.themes.add')
                ->with('error', __('admin/settings/themes/add.messages.download_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * ZIP ファイルを展開してテーマディレクトリに配置する共通処理
     *
     * @return array{success: bool, directory?: string, error?: string}
     */
    protected function extractAndPlaceTheme(string $zipPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            File::delete($zipPath);

            return ['success' => false, 'error' => __('admin/settings/themes/add.messages.zip_extract_failed')];
        }

        $themeDirectory = resource_path('views/themes/');

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

            if (! $extractedRootDir) {
                $zip->close();
                File::delete($zipPath);

                return ['success' => false, 'error' => __('admin/settings/themes/add.messages.no_valid_directory')];
            }

            $directoryName = Str::slug($extractedRootDir);
            $destinationPath = $themeDirectory.$directoryName;

            if (File::exists($destinationPath)) {
                $zip->close();
                File::delete($zipPath);

                return ['success' => false, 'error' => __('admin/settings/themes/add.messages.directory_exists', ['directory' => $directoryName])];
            }

            // ZIPを解凍
            $zip->extractTo($themeDirectory);
            $zip->close();
            File::delete($zipPath);

            // 解凍されたディレクトリをリネーム（必要な場合）
            $extractedDirPath = $themeDirectory.$extractedRootDir;
            if (is_dir($extractedDirPath) && basename($extractedDirPath) !== $directoryName) {
                File::move($extractedDirPath, $destinationPath);
            }

            // theme.jsonの存在確認
            if (! File::exists($destinationPath.'/theme.json')) {
                File::deleteDirectory($destinationPath);

                return ['success' => false, 'error' => __('admin/settings/themes/add.messages.theme_json_not_found')];
            }

            // Git除外ルールとcomposer.local.jsonを更新
            GitExcludeHelper::addThemeExclusion($directoryName);
            GitIgnoreHelper::addThemeExclusion($directoryName);
            ComposerLocalHelper::syncAutoload();

            return ['success' => true, 'directory' => $directoryName];
        } catch (\Exception $e) {
            if (isset($destinationPath) && File::exists($destinationPath)) {
                File::deleteDirectory($destinationPath);
            }
            File::delete($zipPath);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * アップデートチェック（AJAX）
     */
    public function checkUpdates(\App\Services\Extension\ExtensionSourceManager $manager): \Illuminate\Http\JsonResponse
    {
        try {
            $result = $manager->checkUpdates();

            return response()->json([
                'success' => true,
                'plugins' => $result['plugins'],
                'themes' => $result['themes'],
                'message' => empty($result['themes'])
                    ? __('admin/settings/themes/index.updates.all_up_to_date')
                    : __('admin/settings/themes/index.updates.updates_found', ['count' => count($result['themes'])]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * テーマをアップデート（新バージョンをダウンロード → 置換）
     */
    public function updateTheme(int $id, \App\Services\Extension\ExtensionSourceManager $manager)
    {
        $theme = Theme::findOrFail($id);

        if (! $theme->hasUpdateAvailable()) {
            return back()->with('error', __('admin/settings/themes/index.updates.no_update'));
        }

        $slug = $theme->slug;
        $newVersion = $theme->available_version;
        $directory = $theme->directory;
        $themePath = resource_path("views/themes/{$directory}");
        $backupPath = resource_path("views/themes/{$directory}.backup");

        try {
            // 新バージョンの ZIP をダウンロード
            $zipPath = $manager->download($slug, 'theme', $newVersion);

            // 現在のディレクトリをバックアップ
            if (File::exists($themePath)) {
                File::move($themePath, $backupPath);
            }

            // ZIP を展開して配置
            $result = $this->extractAndPlaceTheme($zipPath);

            if (! $result['success']) {
                $this->restoreFromBackup($backupPath, $themePath);

                return back()->with('error', $result['error']);
            }

            // DB のバージョン情報を更新
            $theme->update([
                'version' => $newVersion,
                'available_version' => null,
                'last_version_check' => now(),
            ]);

            // バックアップを削除
            if (File::exists($backupPath)) {
                File::deleteDirectory($backupPath);
            }

            return redirect()->route('admin.settings.themes.index')
                ->with('success', __('admin/settings/themes/index.updates.update_success', ['name' => $theme->name, 'version' => $newVersion]));
        } catch (\Throwable $e) {
            $this->restoreFromBackup($backupPath, $themePath);

            Log::error('Theme update failed', [
                'theme' => $slug,
                'version' => $newVersion,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('admin/settings/themes/index.updates.update_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * バックアップからディレクトリを復元
     */
    protected function restoreFromBackup(string $backupPath, string $originalPath): void
    {
        if (File::exists($backupPath)) {
            if (File::exists($originalPath)) {
                File::deleteDirectory($originalPath);
            }
            File::move($backupPath, $originalPath);
        }
    }
}
