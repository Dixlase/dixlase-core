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

use App\Enums\PluginEnableAction;
use App\Facades\Audit;
use App\Helpers\AdminHelper;
use App\Helpers\ComposerLocalHelper;
use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\AdminPluginDeleteRequest;
use App\Http\Requests\Admin\Settings\AdminPluginInstallRequest;
use App\Http\Requests\Admin\Settings\AdminPluginUploadRequest;
use App\Models\AuditLog;
use App\Models\Plugin;
use App\Models\PluginAudit;
use App\Models\PluginVersionHistory;
use App\Presenters\Admin\ExtensionCardPresenter;
use App\Services\Csp\CspDiagnosticService;
use App\Services\Csp\CspExtensionLoader;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\ExtensionOperationService;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\Plugin\PluginPermissionService;
use App\Services\SecuritySettingsRegistry;
use App\Traits\PluginLoaderTrait;
use Illuminate\Http\JsonResponse;
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

        // セキュリティモードに基づくスキャン必須判定
        $scanRequired = self::isScanRequired();

        $this->viewParams['plugins'] = $plugins;
        $this->viewParams['uninstalledPlugins'] = $uninstalledPlugins;
        $this->viewParams['pluginCards'] = $pluginCards;
        $this->viewParams['uninstalledPluginCards'] = $uninstalledPluginCards;
        $this->viewParams['installedPluginCard'] = $installedPluginCard;
        $this->viewParams['scanRequired'] = $scanRequired;
        $this->viewParams['isSimpleMode'] = \App\Helpers\AdminModeHelper::isSimpleMode();
        $this->viewParams['heading'] = __('admin/settings/plugins/index.heading');

        return view('admin::settings.plugins.index', $this->viewParams);
    }

    /**
     * セキュリティモードに基づいてスキャン必須かどうかを判定
     *
     * Strict/Balanced → true
     * Development → false
     * Custom → require_signature or require_permission_definition or permission_mismatch_action=block なら true
     */
    public static function isScanRequired(): bool
    {
        $preset = SecuritySettingsRegistry::get('extension_security_preset', 'balanced');

        return match ($preset) {
            'strict', 'balanced' => true,
            'development' => false,
            'custom' => SecuritySettingsRegistry::get('extension_require_signature', false)
                || SecuritySettingsRegistry::get('extension_require_permission_definition', false)
                || SecuritySettingsRegistry::get('extension_permission_mismatch_action', 'warn') === 'block',
            default => true,
        };
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

                // CSP準拠状況をコードスキャンで検証
                $cspScanner = app(\App\Services\Csp\CspComplianceScanner::class);
                $cspCompatibility = $cspScanner->scanPlugin($pluginSlug);

                // ファイルハッシュを算出（再スキャン判定用）
                $filesHash = app(PluginHealthScorer::class)->computeFilesHash($pluginSlug);

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
                    'csp_violations' => $cspCompatibility['violations'] ?? [],
                    'csp_summary' => $cspCompatibility['summary'] ?? [],
                    'files_hash' => $filesHash,
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

        // Return existing DB audit data to stay consistent with health scorer
        return $this->getPluginAuditResult($pluginSlug);
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

        // スキャン結果モーダル用に確認理由を翻訳済みで付与
        $result['formatted_attention_reasons'] = ExtensionCardPresenter::formatAttentionReasons(
            $result['risk_reasons'] ?? [],
            'admin/settings/plugins/index'
        );

        // 2段階モーダル用: 有効化アクションとインストール許可を算出
        $enableAction = PluginEnableAction::Allowed;
        $installAllowed = true;
        $healthScore = null;
        $healthStatus = null;
        try {
            $healthScorer = app(PluginHealthScorer::class);
            $healthResult = $healthScorer->calculate($slug);
            $enableAction = $healthScorer->determineEnableAction($healthResult);
            $installAllowed = $enableAction !== PluginEnableAction::Blocked;
            $healthScore = $healthResult->score;
            $healthStatus = $healthResult->status->value;

            PluginAudit::where('plugin_slug', $slug)->update([
                'health_score' => $healthScore,
                'health_status' => $healthStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('Health score calculation failed after audit', [
                'plugin' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // 減点項目を取得（0減点の項目は除外）
        $healthIssues = isset($healthResult)
            ? array_values(array_filter(
                array_map(fn ($i) => $i->jsonSerialize(), $healthResult->issues),
                fn ($i) => ($i['deduction'] ?? 0) !== 0,
            ))
            : [];

        // 権限カテゴリ情報を取得
        $permissionService = app(PluginPermissionService::class);
        $summary = $permissionService->getSummary($slug);
        $categories = $summary['categories'] ?? [];

        // CSP モード互換性・拡張機能互換性バロメータを計算
        $cspLoader = app(\App\Services\Csp\CspExtensionLoader::class);
        $cspCompatibility = $cspLoader->getCspCompatibility('plugin', $slug);
        if (! empty($result['csp_status'])) {
            $cspCompatibility = [
                'status' => $result['csp_status'],
                'requires_inline_js' => $result['csp_requires_inline_js'] ?? false,
                'requires_inline_css' => $result['csp_requires_inline_css'] ?? false,
                'has_csp_config' => $cspCompatibility['has_csp_config'] ?? false,
                'csp_ready' => ! ($result['csp_requires_inline_js'] ?? false),
                'violations' => $result['csp_violations'] ?? [],
                'summary' => $result['csp_summary'] ?? [],
            ];
        }

        $auditedAt = $result['audited_at'] ?? null;
        $cspBarometerItems = ExtensionCardPresenter::buildCspBarometerItems($cspCompatibility, $auditedAt);
        $presetBarometerItems = ExtensionCardPresenter::buildPresetBarometerItems($healthStatus, $auditedAt);

        return response()->json([
            'success' => true,
            'message' => __('admin/settings/plugins/index.audit.completed'),
            'audit' => $result,
            'enableAction' => $enableAction->value,
            'enableActionLabel' => $enableAction->label(),
            'installAllowed' => $installAllowed,
            'healthScore' => $healthScore,
            'healthStatus' => $healthStatus,
            'healthIssues' => $healthIssues,
            'categories' => $categories,
            'cspBarometerItems' => $cspBarometerItems,
            'presetBarometerItems' => $presetBarometerItems,
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
     * アクション実行後のリダイレクト先を決定する
     *
     * Referer が詳細ページだった場合は詳細ページに戻り、それ以外は一覧ページにリダイレクトする
     */
    protected function redirectAfterPluginAction(Request $request, ?string $slug = null): \Illuminate\Http\RedirectResponse
    {
        // 詳細ページから来た場合は詳細ページに戻す
        if ($slug) {
            $referer = $request->headers->get('referer', '');
            $showUrl = route('admin.settings.plugins.show', $slug);
            if (str_starts_with($referer, $showUrl) || str_contains($referer, "/settings/plugins/show/{$slug}")) {
                return redirect()->route('admin.settings.plugins.show', $slug);
            }
        }

        return redirect()->route('admin.settings.plugins.index');
    }

    /**
     * インストール済みプラグインの詳細ページ
     */
    public function show(string $slug)
    {
        $plugin = Plugin::where('slug', $slug)->first();

        // インストール済みプラグインが見つからない場合は、未インストールのディレクトリから探す
        if (! $plugin) {
            $uninstalledPlugin = collect($this->getUninstalledPlugins())->firstWhere('slug', $slug);

            if (! $uninstalledPlugin) {
                abort(404);
            }

            // 未インストールプラグイン用の追加データを準備
            $permissionService = app(PluginPermissionService::class);
            $cspDiagnosticService = app(CspDiagnosticService::class);
            $cspLoader = app(CspExtensionLoader::class);

            $summary = $permissionService->getSummary($uninstalledPlugin['slug']);
            $summary['audit'] = $this->getPluginAuditResult($uninstalledPlugin['slug']);
            $uninstalledPlugin['permission_summary'] = $summary;

            $pluginPath = base_path('plugins/'.$uninstalledPlugin['directory']);
            $uninstalledPlugin['csp_diagnostic'] = $cspDiagnosticService->diagnosePlugin($pluginPath);
            $uninstalledPlugin['csp_compatibility'] = $cspLoader->getCspCompatibility('plugin', $uninstalledPlugin['slug']);

            $card = ExtensionCardPresenter::forPlugin($uninstalledPlugin);
            $rawData = $uninstalledPlugin;
            $isInstalled = false;
        } else {
            // インストール済みプラグイン用のデータを準備
            $permissionService = app(PluginPermissionService::class);
            $cspDiagnosticService = app(CspDiagnosticService::class);
            $cspLoader = app(CspExtensionLoader::class);

            $plugin->translated_name = $this->getPluginName($plugin);
            $plugin->translated_description = $this->getPluginDescription($plugin);
            $plugin->has_settings = $this->checkPluginHasSettings($plugin);

            $summary = $permissionService->getSummary($plugin->slug);
            $summary['audit'] = $this->getPluginAuditResult($plugin->slug);
            $plugin->permission_summary = $summary;

            $pluginPath = base_path('plugins/'.$plugin->directory);
            $plugin->csp_diagnostic = $cspDiagnosticService->diagnosePlugin($pluginPath);
            $plugin->csp_compatibility = $cspLoader->getCspCompatibility('plugin', $plugin->slug);

            $card = ExtensionCardPresenter::forPlugin($plugin);
            $rawData = $this->loadPluginJson($plugin->directory);
            $isInstalled = true;
        }

        // CSP ステータスを翻訳キーにマッピング
        $cspStatus = $card['cspCompatibility']['status'] ?? 'not_checked';
        $cspStatusLabelKey = match ($cspStatus) {
            'csp_ready' => 'csp_ready',
            'compatible' => 'csp_compatible',
            'inline_required', 'inline_css_only' => 'csp_inline_required',
            default => 'csp_not_checked',
        };

        $this->viewParams['card'] = $card;
        $this->viewParams['rawData'] = $rawData;
        $this->viewParams['isInstalled'] = $isInstalled;
        $this->viewParams['scanRequired'] = self::isScanRequired();
        $this->viewParams['isSimpleMode'] = \App\Helpers\AdminModeHelper::isSimpleMode();
        $this->viewParams['heading'] = $card['name'] ?? $slug;
        $this->viewParams['settingsUrl'] = $card['settingsUrl'] ?? null;
        $this->viewParams['cspStatusLabelKey'] = $cspStatusLabelKey;

        return view('admin::settings.plugins.show', $this->viewParams);
    }

    /**
     * オンライン（未ダウンロード）プラグインの詳細ページ
     */
    public function showOnline(string $slug, ExtensionSourceManager $manager)
    {
        $details = $manager->getExtensionDetails($slug, 'plugin');

        if ($details === null) {
            abort(404);
        }

        $this->viewParams['details'] = $details;
        $this->viewParams['heading'] = $details['name'] ?? $slug;

        return view('admin::settings.plugins.show-online', $this->viewParams);
    }

    /**
     * plugin.json から生データを読み込む（詳細ページ用）
     *
     * @return array<string, mixed>|null
     */
    protected function loadPluginJson(string $directory): ?array
    {
        $path = base_path("plugins/{$directory}/plugin.json");

        if (! File::exists($path)) {
            return null;
        }

        try {
            $data = json_decode(File::get($path), true);

            return is_array($data) ? $data : null;
        } catch (\Exception) {
            return null;
        }
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

                // アップロード後に自動監査を実行
                $pluginJsonPath = base_path("plugins/{$pluginDir}/plugin.json");
                if (File::exists($pluginJsonPath)) {
                    try {
                        $pluginData = json_decode(File::get($pluginJsonPath), true);
                        $pluginSlug = $pluginData['slug'] ?? Str::slug($pluginDir);
                        $this->runPluginAudit($pluginSlug);
                    } catch (\Exception $e) {
                        Log::warning('Auto-audit after upload failed', [
                            'directory' => $pluginDir,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

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

        // サーバーサイド防御: スキャン必須モードでの事前チェック
        if (self::isScanRequired()) {
            $pluginJsonPath = base_path("plugins/{$pluginDir}/plugin.json");
            $slug = null;

            if (File::exists($pluginJsonPath)) {
                try {
                    $pluginData = json_decode(File::get($pluginJsonPath), true);
                    $slug = $pluginData['slug'] ?? null;
                } catch (\Exception $e) {
                    // plugin.json読み込み失敗時はslug取得をスキップ
                }
            }

            if ($slug) {
                $latestAudit = PluginAudit::where('plugin_slug', $slug)
                    ->latest('audited_at')
                    ->first();

                // 未スキャンの場合はインストールを拒否
                if (! $latestAudit) {
                    return redirect()->back()->with('error', __('admin/settings/plugins/index.two_stage.install_blocked'));
                }

                // スキャン済みでもブロック状態の場合はインストールを拒否
                try {
                    $healthScorer = app(PluginHealthScorer::class);
                    $healthResult = $healthScorer->calculate($slug);
                    $enableAction = $healthScorer->determineEnableAction($healthResult);

                    if ($enableAction === PluginEnableAction::Blocked) {
                        return redirect()->back()->with('error', __('admin/settings/plugins/index.two_stage.install_blocked'));
                    }
                } catch (\Exception $e) {
                    Log::warning('Pre-install health check failed', [
                        'directory' => $pluginDir,
                        'slug' => $slug,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
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
                // plugin.json からサプライチェーン防御用メタデータを取得して保存
                $this->persistSupplyChainMetadata($plugin, 'upload');

                // バージョン履歴を記録（初回インストール）
                $this->recordVersionHistory(
                    plugin: $plugin,
                    oldVersion: null,
                    oldSigningKeyId: null,
                    oldAuthorId: null,
                    installationMethod: PluginVersionHistory::METHOD_INSTALL,
                );

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

            return $this->redirectAfterPluginAction($request, $plugin?->slug)
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

            return $this->redirectAfterPluginAction($request, $plugin->slug)
                ->with('success', __('admin/settings/plugins/index.messages.uninstall_success'));
        } catch (\Exception $e) {
            Log::error('Plugin uninstall failed', [
                'plugin' => $plugin->name,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('admin/settings/plugins/index.messages.uninstall_failed', ['error' => $e->getMessage()]));
        }
    }

    public function enable($id, Request $request)
    {
        $plugin = Plugin::findOrFail($id);
        $translatedName = $this->getPluginName($plugin);

        try {
            // 有効化前に監査を実行（最新の状態を確認）
            $this->runPluginAudit($plugin->slug);

            // 再スキャンが必要な場合は自動再監査
            $healthScorer = app(PluginHealthScorer::class);
            if ($healthScorer->needsRescan($plugin->slug)) {
                Log::info('Plugin files changed, re-scanning', ['plugin' => $plugin->slug]);
                $this->runPluginAudit($plugin->slug);
            }

            // 健全性スコアを算出し、有効化ポリシーを判定
            $healthResult = $healthScorer->calculate($plugin->slug);
            $enableAction = $healthScorer->determineEnableAction($healthResult);

            // Blockedの場合は有効化を拒否
            if ($enableAction === PluginEnableAction::Blocked) {
                return back()->with('error', __('admin/settings/plugins/index.enable_action.blocked_message'));
            }

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

            return $this->redirectAfterPluginAction($request, $plugin->slug)
                ->with('success', str_replace('{name}', $translatedName, __('admin/settings/plugins/index.enabled.success')));
        } catch (\Exception $e) {
            Log::error('Plugin enable failed', [
                'plugin' => $plugin->name,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', str_replace('{name}', $translatedName, __('admin/settings/plugins/index.enabled.failed')).": {$e->getMessage()}");
        }
    }

    public function disable($id, Request $request)
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

            return $this->redirectAfterPluginAction($request, $plugin->slug)
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
        // plugin.json の name フィールド（人間向け名称）を優先
        $directory = $plugin->directory ?? $plugin->name ?? null;
        if ($directory) {
            $pluginJsonPath = base_path("plugins/{$directory}/plugin.json");
            if (File::exists($pluginJsonPath)) {
                try {
                    $data = json_decode(File::get($pluginJsonPath), true);
                    if (is_array($data) && ! empty($data['name'])) {
                        return $data['name'];
                    }
                } catch (\Exception) {
                    // フォールバック
                }
            }
        }

        return $plugin->name ?? __('admin/settings/plugins/index.messages.no_plugin_name');
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
            if ($description !== $translationKey) {
                return $description;
            }

            // plugin.json の多言語 description を参照
            return $this->getLocalizedDescriptionFromPluginJson($plugin->directory)
                ?? $plugin->description
                ?? __('common.no_description');
        } catch (\Exception $e) {
            return $plugin->description ?? __('common.no_description');
        }
    }

    /**
     * plugin.json から現在のロケールに合わせた description を取得
     */
    private function getLocalizedDescriptionFromPluginJson(string $directory): ?string
    {
        $pluginJsonPath = base_path("plugins/{$directory}/plugin.json");
        if (! File::exists($pluginJsonPath)) {
            return null;
        }

        try {
            $data = json_decode(File::get($pluginJsonPath), true);
            $description = $data['description'] ?? null;

            if (is_array($description)) {
                $locale = app()->getLocale();

                return $description[$locale] ?? $description['en'] ?? $description['ja'] ?? null;
            }

            return is_string($description) ? $description : null;
        } catch (\Exception $e) {
            return null;
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
                        $locale = app()->getLocale();
                        $description = $description[$locale] ?? $description['en'] ?? $description['ja'] ?? null;
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

    /**
     * ソースから利用可能なプラグイン一覧を返す（JSON API）
     */
    public function availableFromSource(ExtensionSourceManager $manager): JsonResponse
    {
        try {
            $available = $manager->listAvailablePlugins();

            // インストール済み・ディスク上に存在するプラグインを除外
            $installedSlugs = Plugin::pluck('slug')->toArray();
            $diskSlugs = collect($this->getUninstalledPlugins())->pluck('slug')->toArray();
            $excludeSlugs = array_merge($installedSlugs, $diskSlugs);

            // slug が文字列でないエントリは壊れたマニフェストとして除外する（フロント側の [object Object] 問題の根本対策）
            $filtered = array_values(array_filter(
                $available,
                function (array $plugin) use ($excludeSlugs) {
                    if (! isset($plugin['slug']) || ! is_string($plugin['slug']) || $plugin['slug'] === '') {
                        Log::warning('Online plugin entry dropped due to invalid slug', ['entry' => $plugin]);

                        return false;
                    }

                    return ! in_array($plugin['slug'], $excludeSlugs);
                }
            ));

            // 各エントリの文字列フィールドを明示的に再正規化（JS 側で [object Object] になる防御の最終砦）
            $normalized = array_map(
                fn (array $plugin) => $this->normalizeExtensionEntry($plugin),
                $filtered
            );

            return response()->json([
                'success' => true,
                'plugins' => $normalized,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'plugins' => [],
            ]);
        }
    }

    /**
     * API レスポンス用に文字列フィールドを明示的に正規化する（多言語オブジェクト等の漏れを防ぐ）
     *
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    protected function normalizeExtensionEntry(array $entry): array
    {
        $locale = app()->getLocale();
        $stringFields = ['slug', 'version', 'author', 'email', 'url', 'license', 'package_name', 'namespace', 'thumbnail_url', 'source_name', 'repository_url', 'updated_at'];
        foreach ($stringFields as $field) {
            if (isset($entry[$field]) && ! is_string($entry[$field])) {
                $entry[$field] = null;
            }
        }
        // name と description は多言語オブジェクトを現在ロケールで解決
        foreach (['name', 'description'] as $field) {
            if (isset($entry[$field]) && is_array($entry[$field])) {
                $entry[$field] = $entry[$field][$locale] ?? $entry[$field]['en'] ?? $entry[$field]['ja'] ?? null;
                if (! is_string($entry[$field])) {
                    $entry[$field] = null;
                }
            } elseif (isset($entry[$field]) && ! is_string($entry[$field])) {
                $entry[$field] = null;
            }
        }

        return $entry;
    }

    /**
     * ソースからプラグインをダウンロードして配置
     */
    public function downloadFromSource(Request $request, ExtensionSourceManager $manager)
    {
        $request->validate([
            'slug' => ['required', 'string', 'max:100'],
        ]);

        $slug = trim((string) $request->input('slug'));

        try {
            // ソースから ZIP をダウンロード
            $zipPath = $manager->download($slug, 'plugin');

            // ZIP を展開して配置
            $result = $this->extractAndPlacePlugin($zipPath);

            if ($result['success']) {
                $displayName = $result['name'] ?? $slug;

                return redirect()->route('admin.settings.plugins.index')
                    ->with('success', __('admin/settings/plugins/add.messages.download_success', ['name' => $displayName]))
                    ->with('uploaded_plugin_directory', $result['directory']);
            }

            return redirect()->route('admin.settings.plugins.add')
                ->with('error', $result['error']);
        } catch (\Throwable $e) {
            Log::error('Plugin download from source failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.settings.plugins.add')
                ->with('error', __('admin/settings/plugins/add.messages.download_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * ZIP ファイルを展開してプラグインディレクトリに配置する共通処理
     *
     * @return array{success: bool, directory?: string, error?: string}
     */
    protected function extractAndPlacePlugin(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            File::delete($zipPath);

            return ['success' => false, 'error' => __('admin/settings/plugins/add.messages.zip_extract_failed')];
        }

        try {
            // プラグインフォルダ名取得（ZIP内の最初のディレクトリ）
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
                File::delete($zipPath);

                return ['success' => false, 'error' => __('admin/settings/plugins/add.messages.no_valid_directory')];
            }

            $destinationPath = base_path('plugins/'.$pluginDir);

            if (File::exists($destinationPath)) {
                $zip->close();
                File::delete($zipPath);

                return ['success' => false, 'error' => __('admin/settings/plugins/add.messages.directory_exists', ['directory' => $pluginDir])];
            }

            // ZIPを解凍
            $zip->extractTo(base_path('plugins'));
            $zip->close();
            File::delete($zipPath);

            // plugin.json から正しいディレクトリ名を取得してリネーム
            $pluginJsonPath = base_path("plugins/{$pluginDir}/plugin.json");
            if (File::exists($pluginJsonPath)) {
                try {
                    $pluginData = json_decode(File::get($pluginJsonPath), true);
                    $correctDir = $this->resolvePluginDirectoryName($pluginData);

                    if ($correctDir && $correctDir !== $pluginDir) {
                        $correctPath = base_path("plugins/{$correctDir}");

                        if (File::exists($correctPath)) {
                            File::deleteDirectory($destinationPath);

                            return ['success' => false, 'error' => __('admin/settings/plugins/add.messages.directory_exists', ['directory' => $correctDir])];
                        }

                        File::move($destinationPath, $correctPath);
                        $pluginDir = $correctDir;
                        $destinationPath = $correctPath;
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to resolve plugin directory name', [
                        'directory' => $pluginDir,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // composer.jsonの存在確認
            if (! File::exists(base_path("plugins/{$pluginDir}/composer.json"))) {
                File::deleteDirectory($destinationPath);

                return ['success' => false, 'error' => __('admin/settings/plugins/add.messages.composer_not_found')];
            }

            // Git除外ルールとcomposer.local.jsonを更新
            GitExcludeHelper::addPluginExclusion($pluginDir);
            GitIgnoreHelper::addPluginExclusion($pluginDir);
            ComposerLocalHelper::syncAutoload();

            // 監査はインストール時に実行する（ダウンロード時はスキップ）
            // プラグインファイルは plugins/ に配置されただけでは実行されない。
            // インストール時の2段階モーダル（scan → confirm）で適切なタイミングで監査される。

            // plugin.json から表示用の名前を取得
            $displayName = null;
            if (isset($pluginData) && is_array($pluginData)) {
                $displayName = $pluginData['name'] ?? null;
            } elseif (File::exists(base_path("plugins/{$pluginDir}/plugin.json"))) {
                try {
                    $pluginData = json_decode(File::get(base_path("plugins/{$pluginDir}/plugin.json")), true);
                    $displayName = $pluginData['name'] ?? null;
                } catch (\Exception) {
                    // ignore
                }
            }

            return ['success' => true, 'directory' => $pluginDir, 'name' => $displayName];
        } catch (\Exception $e) {
            if (isset($destinationPath) && File::exists($destinationPath)) {
                File::deleteDirectory($destinationPath);
            }
            File::delete($zipPath);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * plugin.json の内容から正しいディレクトリ名を決定する
     *
     * 優先順位:
     * 1. 明示された package フィールド（マニフェストとインストール先の一意なマッピング）
     * 2. namespace の最終セグメント（例: Plugins\DixlaseSEO → DixlaseSEO）
     * 3. package_name の最後の部分（例: plugins/dixlase-seo → dixlase-seo）
     * 4. null（既存のディレクトリ名を維持）
     */
    protected function resolvePluginDirectoryName(?array $pluginData): ?string
    {
        if (! is_array($pluginData)) {
            return null;
        }

        // 明示された package フィールドを最優先
        $package = $pluginData['package'] ?? null;
        if (is_string($package) && $package !== '') {
            return $package;
        }

        // namespace の最終セグメントを優先
        $namespace = $pluginData['namespace'] ?? null;
        if (is_string($namespace) && $namespace !== '') {
            $parts = explode('\\', trim($namespace, '\\'));
            $lastSegment = end($parts);
            if ($lastSegment !== false && $lastSegment !== '') {
                return $lastSegment;
            }
        }

        // フォールバック: package_name の最後の部分
        $packageName = $pluginData['package_name'] ?? null;
        if (is_string($packageName) && $packageName !== '') {
            $parts = explode('/', $packageName);
            $lastSegment = end($parts);
            if ($lastSegment !== false && $lastSegment !== '') {
                return $lastSegment;
            }
        }

        return null;
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
                'message' => empty($result['plugins']) && empty($result['themes'])
                    ? __('admin/settings/plugins/index.updates.all_up_to_date')
                    : __('admin/settings/plugins/index.updates.updates_found', ['count' => count($result['plugins'])]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * プラグインをアップデート（新バージョンをダウンロード → 置換）
     */
    public function updatePlugin(int $id, \App\Services\Extension\ExtensionSourceManager $manager)
    {
        $plugin = Plugin::findOrFail($id);

        if (! $plugin->hasUpdateAvailable()) {
            return back()->with('error', __('admin/settings/plugins/index.updates.no_update'));
        }

        $slug = $plugin->slug;
        $newVersion = $plugin->available_version;
        $directory = $plugin->directory;
        $pluginPath = base_path("plugins/{$directory}");
        $backupPath = base_path("plugins/{$directory}.backup");

        try {
            // 新バージョンの ZIP をダウンロード
            $zipPath = $manager->download($slug, 'plugin', $newVersion);

            // 更新前のメタデータを保存（履歴記録用）
            $oldVersion = $plugin->version;
            $oldSigningKeyId = $plugin->signing_key_id;
            $oldAuthorId = $plugin->author_id;

            // 現在のディレクトリをバックアップ
            if (File::exists($pluginPath)) {
                File::move($pluginPath, $backupPath);
            }

            // ZIP を展開して配置
            $result = $this->extractAndPlacePlugin($zipPath);

            if (! $result['success']) {
                // 失敗時はバックアップから復元
                $this->restoreFromBackup($backupPath, $pluginPath);

                return back()->with('error', $result['error']);
            }

            // DB のバージョン情報を更新
            $plugin->update([
                'version' => $newVersion,
                'available_version' => null,
                'last_version_check' => now(),
            ]);

            // 新しい plugin.json からサプライチェーン防御用メタデータを更新
            $this->persistSupplyChainMetadata($plugin, 'update');

            // バージョン履歴を記録（アップデート）
            $this->recordVersionHistory(
                plugin: $plugin,
                oldVersion: $oldVersion,
                oldSigningKeyId: $oldSigningKeyId,
                oldAuthorId: $oldAuthorId,
                installationMethod: PluginVersionHistory::METHOD_UPDATE,
            );

            // バックアップを削除
            if (File::exists($backupPath)) {
                File::deleteDirectory($backupPath);
            }

            // 再監査
            $this->runPluginAudit($slug);

            return redirect()->route('admin.settings.plugins.index')
                ->with('success', __('admin/settings/plugins/index.updates.update_success', ['name' => $plugin->name, 'version' => $newVersion]));
        } catch (\Throwable $e) {
            // 失敗時はバックアップから復元
            $this->restoreFromBackup($backupPath, $pluginPath);

            Log::error('Plugin update failed', [
                'plugin' => $slug,
                'version' => $newVersion,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('admin/settings/plugins/index.updates.update_failed', ['error' => $e->getMessage()]));
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

    /**
     * plugin.json からサプライチェーン防御用のメタデータを抽出して Plugin に保存
     *
     * @param  string  $installationMethod  "upload" / "marketplace" / "cli" / "github"
     */
    protected function persistSupplyChainMetadata(Plugin $plugin, string $installationMethod, ?string $sourceUrl = null): void
    {
        $pluginJsonPath = base_path("plugins/{$plugin->directory}/plugin.json");
        if (! File::exists($pluginJsonPath)) {
            return;
        }

        try {
            $data = json_decode(File::get($pluginJsonPath), true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
                return;
            }

            $plugin->update([
                'author_id' => $data['author_id'] ?? null,
                'publisher_key_id' => $data['publisher_key_id'] ?? null,
                'signing_key_id' => $data['signing']['key_id'] ?? null,
                'installation_method' => $installationMethod,
                'installed_from_url' => $sourceUrl,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to persist supply-chain metadata', [
                'plugin' => $plugin->slug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * プラグインのバージョン履歴を記録し、署名鍵・オーナー変更があれば監査ログも記録
     */
    protected function recordVersionHistory(
        Plugin $plugin,
        ?string $oldVersion,
        ?string $oldSigningKeyId,
        ?string $oldAuthorId,
        string $installationMethod,
    ): void {
        $newSigningKeyId = $plugin->signing_key_id;
        $newAuthorId = $plugin->author_id;
        $signingKeyChanged = $oldSigningKeyId !== null && $oldSigningKeyId !== $newSigningKeyId;
        $authorIdChanged = $oldAuthorId !== null && $oldAuthorId !== $newAuthorId;

        $member = AdminHelper::getMember();

        try {
            PluginVersionHistory::create([
                'plugin_slug' => $plugin->slug,
                'old_version' => $oldVersion,
                'new_version' => $plugin->version,
                'old_signing_key_id' => $oldSigningKeyId,
                'new_signing_key_id' => $newSigningKeyId,
                'old_author_id' => $oldAuthorId,
                'new_author_id' => $newAuthorId,
                'files_changed_count' => 0, // 初期リリース: 未計算
                'lines_added' => 0,
                'lines_removed' => 0,
                'signing_key_changed' => $signingKeyChanged,
                'author_id_changed' => $authorIdChanged,
                'installation_method' => $installationMethod,
                'installed_from_url' => $plugin->installed_from_url,
                'applied_by_id' => $member?->id,
                'applied_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record plugin version history', [
                'plugin' => $plugin->slug,
                'error' => $e->getMessage(),
            ]);
        }

        // 署名鍵変更・オーナー変更は監査ログに記録（承認フロー等は Phase 2 で実装）
        if ($signingKeyChanged) {
            $this->logSupplyChainEvent($plugin, AuditLog::ACTION_PLUGIN_SIGNING_KEY_CHANGED, [
                'old_signing_key_id' => $oldSigningKeyId,
                'new_signing_key_id' => $newSigningKeyId,
            ]);
        }

        if ($authorIdChanged) {
            $this->logSupplyChainEvent($plugin, AuditLog::ACTION_PLUGIN_AUTHOR_ID_CHANGED, [
                'old_author_id' => $oldAuthorId,
                'new_author_id' => $newAuthorId,
            ]);
        }
    }

    /**
     * サプライチェーン防御のイベントを監査ログに記録
     *
     * @param  array<string, mixed>  $context
     */
    protected function logSupplyChainEvent(Plugin $plugin, string $action, array $context = []): void
    {
        try {
            $fullContext = array_merge([
                'plugin_slug' => $plugin->slug,
                'plugin_name' => $plugin->name,
                'plugin_version' => $plugin->version,
            ], $context);

            Audit::logExtension($action, [
                'actor' => AdminHelper::getMember(),
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'severity' => AuditLog::SEVERITY_WARNING,
                'plugin_name' => $plugin->name,
                'plugin_version' => $plugin->version,
                'target_type' => 'plugin',
                'target_id' => (string) $plugin->id,
                'target_label' => $plugin->slug,
                'context' => $fullContext,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to log supply-chain event', [
                'plugin' => $plugin->slug,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
