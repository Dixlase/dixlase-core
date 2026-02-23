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

use App\Helpers\ComposerLocalHelper;
use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\AdminPluginDeleteRequest;
use App\Http\Requests\Admin\Settings\AdminPluginInstallRequest;
use App\Http\Requests\Admin\Settings\AdminPluginUploadRequest;
use App\Models\Plugin;
use App\Models\PluginAudit;
use App\Presenters\Admin\ExtensionCardPresenter;
use App\Services\Csp\CspDiagnosticService;
use App\Services\Csp\CspExtensionLoader;
use App\Services\ExtensionOperationService;
use App\Services\Plugin\PluginPermissionService;
use App\Traits\PluginLoaderTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class AdminPluginsSettingsController extends AdminLoggedInController
{
    use PluginLoaderTrait;

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // インストール済みプラグイン
        $plugins = Plugin::all();

        // 権限サービスとCSP診断サービスを取得
        $permissionService = app(PluginPermissionService::class);
        $cspDiagnosticService = app(CspDiagnosticService::class);
        $cspLoader = app(CspExtensionLoader::class);

        // 各プラグインに設定画面があるかチェック、翻訳された名前と説明を取得
        foreach ($plugins as $plugin) {
            $plugin->has_settings = $this->checkPluginHasSettings($plugin);
            $plugin->translated_name = $this->getPluginName($plugin);
            $plugin->translated_description = $this->getPluginDescription($plugin);

            // 権限サマリーを取得（監査結果を含む）
            $summary = $permissionService->getSummary($plugin->slug);
            $summary['audit'] = $this->getPluginAuditResult($plugin->slug);
            $plugin->permission_summary = $summary;

            // CSP診断結果を取得（ディレクトリ名を使用）
            $pluginPath = base_path('plugins/'.$plugin->directory);
            $plugin->csp_diagnostic = $cspDiagnosticService->diagnosePlugin($pluginPath);

            // CSP互換性情報を取得
            $plugin->csp_compatibility = $cspLoader->getCspCompatibility('plugin', $plugin->slug);
        }

        // アンインストール済みプラグインを検出
        $uninstalledPlugins = $this->getUninstalledPlugins();

        // アンインストール済みプラグインにも権限サマリーと監査結果を追加
        foreach ($uninstalledPlugins as &$plugin) {
            $summary = $permissionService->getSummary($plugin['slug']);
            $summary['audit'] = $this->getPluginAuditResult($plugin['slug']);
            $plugin['permission_summary'] = $summary;

            // CSP診断結果を取得（ディレクトリ名を使用）
            $pluginPath = base_path('plugins/'.$plugin['directory']);
            $plugin['csp_diagnostic'] = $cspDiagnosticService->diagnosePlugin($pluginPath);

            // CSP互換性情報を取得
            $plugin['csp_compatibility'] = $cspLoader->getCspCompatibility('plugin', $plugin['slug']);
        }
        unset($plugin);

        // カードデータを事前計算
        $pluginCards = [];
        foreach ($plugins as $plugin) {
            $pluginCards[] = ExtensionCardPresenter::forPlugin($plugin);
        }
        $uninstalledPluginCards = [];
        foreach ($uninstalledPlugins as $plugin) {
            $uninstalledPluginCards[] = ExtensionCardPresenter::forPlugin($plugin);
        }

        // インストール直後のプラグインカードを特定（フラッシュメッセージのモーダル用）
        $installedPluginId = session('installed_plugin_id');
        $installedPluginCard = null;
        if ($installedPluginId) {
            $installedPluginCard = collect($pluginCards)->firstWhere('id', $installedPluginId);
        }

        $this->viewParams['plugins'] = $plugins;
        $this->viewParams['uninstalledPlugins'] = $uninstalledPlugins;
        $this->viewParams['pluginCards'] = $pluginCards;
        $this->viewParams['uninstalledPluginCards'] = $uninstalledPluginCards;
        $this->viewParams['installedPluginCard'] = $installedPluginCard;
        $this->viewParams['heading'] = __('admin/settings/plugins/index.heading');

        return view('admin::settings.plugins.index', $this->viewParams);
    }

    /**
     * プラグインの監査結果をDBから取得
     */
    protected function getPluginAuditResult(string $pluginSlug): array
    {
        $audit = PluginAudit::getBySlug($pluginSlug);

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
     * プラグインを監査してDBに保存
     */
    protected function runPluginAudit(string $pluginSlug): array
    {
        try {
            Log::info('Plugin audit starting', ['plugin' => $pluginSlug]);

            Artisan::call('dls:plugin:audit', [
                'plugin' => $pluginSlug,
                '--json' => true,
            ]);

            $output = trim(Artisan::output());

            Log::info('Plugin audit output', [
                'plugin' => $pluginSlug,
                'output_length' => strlen($output),
                'output_preview' => substr($output, 0, 500),
            ]);

            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Plugin audit JSON parse error', [
                    'plugin' => $pluginSlug,
                    'error' => json_last_error_msg(),
                    'output' => $output,
                ]);
            }

            if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
                // 署名情報を取得
                $permissionService = app(PluginPermissionService::class);
                $summary = $permissionService->getSummary($pluginSlug);
                $signature = $summary['signature'] ?? [];

                // CSP情報を取得
                $cspLoader = app(\App\Services\Csp\CspExtensionLoader::class);
                $cspCompatibility = $cspLoader->getCspCompatibility('plugin', $pluginSlug);

                $auditData = [
                    'has_mismatches' => ! empty($result['mismatches'] ?? []),
                    'mismatches' => $result['mismatches'] ?? [],
                    'matches_count' => count($result['matches'] ?? []),
                    'total_checked' => $result['total_checked'] ?? 0,
                    'risk_level' => $result['risk_level'] ?? null,
                    'risk_reasons' => $result['risk_reasons'] ?? [],
                    'signature_status' => $signature['status'] ?? 'unsigned',
                    'signature_signer' => $signature['signer'] ?? null,
                    'csp_status' => $cspCompatibility['status'] ?? 'not_checked',
                    'csp_requires_inline_js' => $cspCompatibility['requires_inline_js'] ?? false,
                    'csp_requires_inline_css' => $cspCompatibility['requires_inline_css'] ?? false,
                ];

                Log::info('Plugin audit data', ['plugin' => $pluginSlug, 'data' => $auditData]);

                // DBに保存
                $audit = PluginAudit::saveAuditResult($pluginSlug, $auditData);

                Log::info('Plugin audit saved', ['plugin' => $pluginSlug, 'audit_id' => $audit->id]);

                return $audit->toAuditArray();
            }
        } catch (\Exception $e) {
            Log::error('Plugin audit failed', [
                'plugin' => $pluginSlug,
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
     * プラグインを手動で監査（Ajax）
     */
    public function audit(Request $request)
    {
        $slug = $request->input('slug');

        if (! $slug) {
            return response()->json([
                'success' => false,
                'message' => __('admin/settings/plugins/index.audit.invalid_slug'),
            ], 400);
        }

        $result = $this->runPluginAudit($slug);

        return response()->json([
            'success' => true,
            'message' => __('admin/settings/plugins/index.audit.completed'),
            'audit' => $result,
        ]);
    }

    public function add()
    {
        $uploadMaxBytes = $this->parsePhpSize(ini_get('upload_max_filesize'));
        $this->viewParams['uploadMaxMB'] = number_format($uploadMaxBytes / 1048576, 2);
        $this->viewParams['heading'] = __('admin/settings/plugins/add.heading');

        return view('admin::settings.plugins.add', $this->viewParams);
    }

    /**
     * プラグインのアップロード（ZIPファイルの解凍とファイル配置のみ）
     */
    public function upload(AdminPluginUploadRequest $request)
    {
        // ZIPファイルを一時保存
        $file = $request->file('plugin_file');
        $fileName = $file->getClientOriginalName();
        $tempPath = storage_path('app/temp/plugins/'.$fileName);
        $file->move(storage_path('app/temp/plugins'), $fileName);

        // ZIP展開
        $zip = new ZipArchive();
        if ($zip->open($tempPath) === true) {
            try {
                // プラグインフォルダ名取得（ZIP内の最初のディレクトリ）
                $pluginDir = null;
                $dirs = [];

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if ($entry !== false) {
                        $pathParts = explode('/', $entry);
                        if (! empty($pathParts[0])) {
                            $dirs[] = $pathParts[0];
                        }
                    }
                }

                $dirs = array_unique($dirs);
                $pluginDir = reset($dirs);

                if (! $pluginDir) {
                    $zip->close();
                    File::delete($tempPath);

                    return redirect()->route('admin.settings.plugins.add')
                        ->with('error', __('admin/settings/plugins/add.messages.no_valid_directory'));
                }

                $destinationPath = base_path('plugins/'.$pluginDir);

                // プラグインフォルダが既に存在しているか確認
                if (File::exists($destinationPath)) {
                    $zip->close();
                    File::delete($tempPath);

                    return redirect()->route('admin.settings.plugins.add')
                        ->with('error', __('admin/settings/plugins/add.messages.directory_exists', ['directory' => $pluginDir]));
                }

                // ZIPを解凍
                $zip->extractTo(base_path('plugins'));
                $zip->close();
                File::delete($tempPath);

                // composer.jsonの存在確認
                $composerPath = base_path("plugins/{$pluginDir}/composer.json");
                if (! File::exists($composerPath)) {
                    File::deleteDirectory($destinationPath);

                    return redirect()->route('admin.settings.plugins.add')
                        ->with('error', __('admin/settings/plugins/add.messages.composer_not_found'));
                }

                // .git/info/excludeにプラグインの除外ルールを追加
                GitExcludeHelper::addPluginExclusion($pluginDir);

                // .gitignoreにプラグインの除外ルールを追加
                GitIgnoreHelper::addPluginExclusion($pluginDir);

                // composer.local.jsonを更新
                ComposerLocalHelper::syncAutoload();

                return redirect()->route('admin.settings.plugins.index')
                    ->with('success', __('admin/settings/plugins/add.messages.upload_success'))
                    ->with('uploaded_plugin_directory', $pluginDir);
            } catch (\Exception $e) {
                // 例外発生時にクリーンアップ
                if (isset($destinationPath) && File::exists($destinationPath)) {
                    File::deleteDirectory($destinationPath);
                }
                if (File::exists($tempPath)) {
                    File::delete($tempPath);
                }
                Log::error('Plugin upload failed', [
                    'directory' => $pluginDir ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);

                return redirect()->route('admin.settings.plugins.add')
                    ->with('error', __('admin/settings/plugins/add.messages.upload_failed', ['error' => $e->getMessage()]));
            }
        }

        // ZIP展開失敗
        if (File::exists($tempPath)) {
            File::delete($tempPath);
        }

        return redirect()->route('admin.settings.plugins.add')
            ->with('error', __('admin/settings/plugins/add.messages.zip_extract_failed'));
    }

    /**
     * アンインストール済みプラグインをインストール
     */
    public function install(AdminPluginInstallRequest $request)
    {
        $validated = $request->validated();

        $pluginDir = $validated['directory'];
        $pluginPath = base_path("plugins/{$pluginDir}");

        if (! File::exists($pluginPath)) {
            return redirect()->back()->with('error', __('admin/settings/plugins/index.messages.install_directory_not_found'));
        }

        try {
            // コマンドを使用してインストール
            Artisan::call('dls:plugin:install', [
                'pluginName' => $pluginDir,
            ]);

            // インストールされたプラグインを取得
            $plugin = Plugin::where('directory', $pluginDir)->first();

            // インストール後に監査を実行
            if ($plugin) {
                $this->runPluginAudit($plugin->slug);

                // 拡張機能操作の通知・ログ記録
                $permissionService = app(PluginPermissionService::class);
                $summary = $permissionService->getSummary($plugin->slug);

                app(ExtensionOperationService::class)->recordOperation(
                    ExtensionOperationService::TYPE_PLUGIN,
                    ExtensionOperationService::OPERATION_INSTALLED,
                    [
                        'name' => $plugin->translated_name ?? $plugin->name,
                        'slug' => $plugin->slug,
                        'version' => $plugin->version ?? null,
                        'health_status' => $summary['risk_level'] ?? 'unknown',
                    ]
                );
            }

            // インストール成功メッセージ
            $successMessage = $plugin
                ? __('admin/settings/plugins/index.messages.install_success')
                : __('admin/settings/plugins/index.messages.install_success_no_plugin');

            return redirect()->route('admin.settings.plugins.index')
                ->with('success', $successMessage)
                ->with('installed_plugin_id', $plugin ? $plugin->id : null);
        } catch (\Exception $e) {
            Log::error('Plugin installation failed', [
                'directory' => $pluginDir,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', __('admin/settings/plugins/index.messages.install_failed', ['error' => $e->getMessage()]));
        }
    }

    public function uninstall($id, Request $request)
    {
        $plugin = Plugin::findOrFail($id);

        // 有効化中のプラグインはアンインストールできない
        if ($plugin->isEnabled()) {
            return back()->with('error', __('admin/settings/plugins/index.messages.uninstall_must_disable_first'));
        }

        // 通知用にプラグイン情報を保存
        $pluginData = [
            'name' => $this->getPluginName($plugin),
            'slug' => $plugin->slug,
            'version' => $plugin->version ?? null,
            'health_status' => 'low', // アンインストール時は健全性警告不要
        ];

        try {
            // コマンドを使用してアンインストール
            $options = [
                'pluginName' => $plugin->name,
                '--force' => true,
                '--no-interaction' => true,
            ];

            // DBデータも削除する場合
            if ($request->has('remove_db_data')) {
                $options['--rollback'] = true;
            }

            Artisan::call('dls:plugin:uninstall', $options);

            // 拡張機能操作の通知・ログ記録
            app(ExtensionOperationService::class)->recordOperation(
                ExtensionOperationService::TYPE_PLUGIN,
                ExtensionOperationService::OPERATION_UNINSTALLED,
                $pluginData
            );

            return redirect()->route('admin.settings.plugins.index')
                ->with('success', __('admin/settings/plugins/index.messages.uninstall_success'));
        } catch (\Exception $e) {
            Log::error('Plugin uninstall failed', [
                'plugin' => $plugin->name,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('admin/settings/plugins/index.messages.uninstall_failed', ['error' => $e->getMessage()]));
        }
    }

    public function enable($id)
    {
        $plugin = Plugin::findOrFail($id);
        $translatedName = $this->getPluginName($plugin);

        try {
            // 有効化前に監査を実行（最新の状態を確認）
            $this->runPluginAudit($plugin->slug);

            // コマンドを使用して有効化
            Artisan::call('dls:plugin:enable', [
                'pluginName' => $plugin->name,
            ]);

            // 拡張機能操作の通知・ログ記録
            $permissionService = app(PluginPermissionService::class);
            $summary = $permissionService->getSummary($plugin->slug);

            app(ExtensionOperationService::class)->recordOperation(
                ExtensionOperationService::TYPE_PLUGIN,
                ExtensionOperationService::OPERATION_ENABLED,
                [
                    'name' => $translatedName,
                    'slug' => $plugin->slug,
                    'version' => $plugin->version ?? null,
                    'health_status' => $summary['risk_level'] ?? 'unknown',
                ]
            );

            return redirect()->route('admin.settings.plugins.index')
                ->with('success', str_replace('{name}', $translatedName, __('admin/settings/plugins/index.enabled.success')));
        } catch (\Exception $e) {
            Log::error('Plugin enable failed', [
                'plugin' => $plugin->name,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', str_replace('{name}', $translatedName, __('admin/settings/plugins/index.enabled.failed')).": {$e->getMessage()}");
        }
    }

    public function disable($id)
    {
        $plugin = Plugin::findOrFail($id);
        $translatedName = $this->getPluginName($plugin);

        try {
            // コマンドを使用して無効化
            Artisan::call('dls:plugin:disable', [
                'pluginName' => $plugin->name,
            ]);

            // 拡張機能操作の通知・ログ記録
            app(ExtensionOperationService::class)->recordOperation(
                ExtensionOperationService::TYPE_PLUGIN,
                ExtensionOperationService::OPERATION_DISABLED,
                [
                    'name' => $translatedName,
                    'slug' => $plugin->slug,
                    'version' => $plugin->version ?? null,
                    'health_status' => 'low', // 無効化時は健全性警告不要
                ]
            );

            return redirect()->route('admin.settings.plugins.index')
                ->with('success', __('admin/settings/plugins/index.messages.disable_success'));
        } catch (\Exception $e) {
            Log::error('Plugin disable failed', [
                'plugin' => $plugin->name,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('admin/settings/plugins/index.messages.disable_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * プラグインを完全に削除（ファイル + DBレコード）
     */
    public function delete(AdminPluginDeleteRequest $request)
    {
        $validated = $request->validated();

        $pluginDir = $validated['directory'];

        // DBレコードが存在するか確認
        $plugin = Plugin::where('directory', $pluginDir)->first();

        try {
            // DBレコードが存在する場合は先にアンインストール
            if ($plugin) {
                $exitCode = Artisan::call('dls:plugin:uninstall', [
                    'pluginName' => $plugin->slug,
                    '--force' => true,
                    '--no-interaction' => true,
                ]);

                if ($exitCode !== 0) {
                    $output = Artisan::output();
                    Log::error('Plugin uninstall command failed', [
                        'directory' => $pluginDir,
                        'exit_code' => $exitCode,
                        'output' => $output,
                    ]);

                    return redirect()->back()->with('error', __('admin/settings/plugins/index.messages.delete_uninstall_failed'));
                }
            }

            // プラグインディレクトリを削除
            $exitCode = Artisan::call('dls:plugin:delete', [
                'pluginDirectory' => $pluginDir,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                $output = Artisan::output();
                Log::error('Plugin delete command failed', [
                    'directory' => $pluginDir,
                    'exit_code' => $exitCode,
                    'output' => $output,
                ]);

                return redirect()->back()->with('error', __('admin/settings/plugins/index.messages.delete_file_failed'));
            }

            return redirect()->route('admin.settings.plugins.index')
                ->with('success', __('admin/settings/plugins/index.messages.delete_success'));
        } catch (\Exception $e) {
            Log::error('Plugin deletion failed', [
                'directory' => $pluginDir,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', __('admin/settings/plugins/index.messages.delete_failed', ['error' => $e->getMessage()]));
        }
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
     * config/admin.php の settings_route が定義されていれば設定画面ありと判定
     */
    private function checkPluginHasSettings($plugin): bool
    {
        if (! $plugin->isActivated()) {
            return false;
        }

        $configPath = base_path("plugins/{$plugin->directory}/config/admin.php");

        if (! file_exists($configPath)) {
            return false;
        }

        try {
            $pluginConfig = require $configPath;
            $settingsRoute = $pluginConfig['settings_route'] ?? null;

            // settings_routeが定義されていれば設定画面あり
            return ! empty($settingsRoute);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * プラグインの設定画面URLを取得
     * config/admin.php の settings_route からルート名を取得してURLを生成
     */
    public function getPluginSettingsUrl($plugin): ?string
    {
        $configPath = base_path("plugins/{$plugin->directory}/config/admin.php");

        if (! file_exists($configPath)) {
            return null;
        }

        try {
            $pluginConfig = require $configPath;
            $settingsRoute = $pluginConfig['settings_route'] ?? null;

            if (empty($settingsRoute)) {
                return null;
            }

            // ルートが存在する場合はURLを生成
            if (\Route::has($settingsRoute)) {
                return route($settingsRoute);
            }

            // ルートが存在しない場合はログに警告を出力
            \Log::warning('Plugin settings route not found', [
                'plugin' => $plugin->directory,
                'route' => $settingsRoute,
            ]);

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * プラグインの翻訳された名前を取得
     */
    private function getPluginName($plugin)
    {
        try {
            // プラグインの翻訳ファイルから名前を取得
            $pluginSlug = strtolower(str_replace('Dixlase', 'dixlase-', $plugin->directory));
            $translationKey = $pluginSlug.'::admin.plugin.name';
            $name = __($translationKey);

            // 翻訳キーがそのまま返された場合は翻訳が見つからない
            if ($name === $translationKey) {
                return $plugin->name ?? __('admin/settings/plugins/index.messages.no_plugin_name');
            }

            return $name;
        } catch (\Exception $e) {
            // 翻訳ファイルが存在しない場合はDBの名前またはデフォルト
            return $plugin->name ?? __('admin/settings/plugins/index.messages.no_plugin_name');
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
            $translationKey = $pluginSlug.'::admin.plugin.description';
            $description = __($translationKey);

            // 翻訳キーがそのまま返された場合は翻訳が見つからない
            if ($description === $translationKey) {
                return $plugin->description ?? __('common.no_description');
            }

            return $description;
        } catch (\Exception $e) {
            // 翻訳ファイルが存在しない場合はDBの説明またはデフォルト
            return $plugin->description ?? __('common.no_description');
        }
    }

    /**
     * アンインストール済みプラグインを検出
     */
    private function getUninstalledPlugins()
    {
        $uninstalledPlugins = [];
        $pluginsPath = base_path('plugins');

        if (! File::exists($pluginsPath)) {
            return $uninstalledPlugins;
        }

        // pluginsディレクトリ内のすべてのディレクトリを取得
        $directories = File::directories($pluginsPath);

        // インストール済みプラグインのディレクトリ名を取得
        $installedDirectories = Plugin::pluck('directory')->toArray();

        foreach ($directories as $directory) {
            $dirName = basename($directory);

            // DBに登録されていないプラグインを検出
            if (! in_array($dirName, $installedDirectories)) {
                $pluginInfo = $this->getPluginInfoFromDirectory($dirName);
                if ($pluginInfo) {
                    $uninstalledPlugins[] = $pluginInfo;
                }
            }
        }

        return $uninstalledPlugins;
    }

    /**
     * ディレクトリからプラグイン情報を取得
     * plugin.json優先、composer.jsonをフォールバック
     */
    private function getPluginInfoFromDirectory($dirName)
    {
        $pluginJsonPath = base_path("plugins/{$dirName}/plugin.json");
        $composerPath = base_path("plugins/{$dirName}/composer.json");

        // plugin.jsonが存在する場合は優先的に使用
        if (File::exists($pluginJsonPath)) {
            try {
                $jsonContent = File::get($pluginJsonPath);
                $pluginData = json_decode($jsonContent, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $description = $pluginData['description'] ?? null;
                    if (is_array($description)) {
                        $description = $description['en'] ?? $description['ja'] ?? null;
                    }

                    return [
                        'directory' => $dirName,
                        'name' => $pluginData['name'] ?? $dirName,
                        'description' => $description,
                        'version' => $pluginData['version'] ?? '1.0.0',
                        'author' => $pluginData['author'] ?? null,
                        'email' => $pluginData['email'] ?? null,
                        'url' => $pluginData['url'] ?? $pluginData['homepage'] ?? $pluginData['web'] ?? null,
                        'license' => $pluginData['license'] ?? null,
                        'package_name' => $pluginData['package_name'] ?? null,
                        'slug' => $pluginData['slug'] ?? Str::slug($dirName),
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Failed to read plugin.json', [
                    'directory' => $dirName,
                    'error' => $e->getMessage(),
                ]);
                // plugin.jsonの読み込みに失敗した場合はcomposer.jsonにフォールバック
            }
        }

        // plugin.jsonが存在しない、または読み込みに失敗した場合はcomposer.jsonを使用
        if (! File::exists($composerPath)) {
            return;
        }

        try {
            $jsonContent = File::get($composerPath);
            $composerData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return;
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
                'url' => $firstAuthor['homepage'] ?? null,
                'license' => $composerData['license'] ?? null,
                'package_name' => $composerData['name'] ?? null,
                'slug' => $composerData['extra']['slug'] ?? Str::slug($dirName),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to read composer.json', [
                'directory' => $dirName,
                'error' => $e->getMessage(),
            ]);

            return;
        }
    }
}
