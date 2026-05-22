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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
use App\Services\Plugin\PluginTableInspector;
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
        // Installed plugins
        $plugins = Plugin::all();

        // Permission service and health scorer for file change detection
        $permissionService = app(PluginPermissionService::class);
        $healthScorer = app(PluginHealthScorer::class);

        // Detect uninstalled plugins first (to gather slugs for batch query)
        $uninstalledPlugins = $this->getUninstalledPlugins();

        // Fetch audit results for all plugins in 1 query (avoid N+1)
        $allSlugs = array_filter(array_merge(
            $plugins->pluck('slug')->all(),
            array_column($uninstalledPlugins, 'slug'),
        ));
        $auditMap = ! empty($allSlugs)
            ? PluginAudit::whereIn('plugin_slug', $allSlugs)->get()->keyBy('plugin_slug')
            : collect();

        // Check if each plugin has settings screen, get translated name and description
        foreach ($plugins as $plugin) {
            $plugin->has_settings = $this->checkPluginHasSettings($plugin);
            $plugin->translated_name = $this->getPluginName($plugin);
            $plugin->translated_description = $this->getPluginDescription($plugin);

            // Get permission summary (including audit results)
            $summary = $permissionService->getSummary($plugin->slug);
            $summary['audit'] = $this->buildAuditArrayFromMap($auditMap, $plugin->slug);
            $plugin->permission_summary = $summary;

            // Restore CSP compatibility info from audit results (plugin_audits)
            $plugin->csp_compatibility = $this->buildCspCompatibilityFromAudit($summary['audit']);
            $plugin->csp_diagnostic = null;

            // File change detection (lightweight check based on mtime)
            $plugin->files_changed = $this->detectFilesChanged($plugin->slug, $auditMap, $healthScorer);
        }

        // Add permission summary and audit results to uninstalled plugins too
        foreach ($uninstalledPlugins as &$plugin) {
            $summary = $permissionService->getSummary($plugin['slug']);
            $summary['audit'] = $this->buildAuditArrayFromMap($auditMap, $plugin['slug']);
            $plugin['permission_summary'] = $summary;

            $plugin['csp_compatibility'] = $this->buildCspCompatibilityFromAudit($summary['audit']);
            $plugin['csp_diagnostic'] = null;
            $plugin['files_changed'] = $this->detectFilesChanged($plugin['slug'], $auditMap, $healthScorer);
        }
        unset($plugin);

        // Pre-calculate card data
        $pluginCards = [];
        foreach ($plugins as $plugin) {
            $pluginCards[] = ExtensionCardPresenter::forPlugin($plugin);
        }
        $uninstalledPluginCards = [];
        foreach ($uninstalledPlugins as $plugin) {
            $uninstalledPluginCards[] = ExtensionCardPresenter::forPlugin($plugin);
        }

        // Identify just-installed plugin card (for flash message modal)
        $installedPluginId = session('installed_plugin_id');
        $installedPluginCard = null;
        if ($installedPluginId) {
            $installedPluginCard = collect($pluginCards)->firstWhere('id', $installedPluginId);
        }

        // Determine scan requirement based on security mode
        $scanRequired = self::isScanRequired();

        // For "Update All" button: list of plugins with available_version set (updated sequentially from JS)
        $updatableExtensions = collect($pluginCards)
            ->filter(fn (array $c) => ! empty($c['hasUpdateAvailable']))
            ->map(fn (array $c) => [
                'id' => $c['id'],
                'name' => $c['translatedName'] ?? $c['name'] ?? $c['slug'],
                'currentVersion' => $c['version'] ?? '',
                'availableVersion' => $c['availableVersion'] ?? '',
                'updateUrl' => route('admin.settings.plugins.update', $c['id']),
            ])
            ->values()
            ->all();

        $this->viewParams['plugins'] = $plugins;
        $this->viewParams['uninstalledPlugins'] = $uninstalledPlugins;
        $this->viewParams['pluginCards'] = $pluginCards;
        $this->viewParams['uninstalledPluginCards'] = $uninstalledPluginCards;
        $this->viewParams['installedPluginCard'] = $installedPluginCard;
        $this->viewParams['scanRequired'] = $scanRequired;
        $this->viewParams['isSimpleMode'] = \App\Helpers\AdminModeHelper::isSimpleMode();
        $this->viewParams['heading'] = __('admin/settings/plugins/index.heading');
        $this->viewParams['updatableExtensions'] = $updatableExtensions;

        return view('admin::settings.plugins.index', $this->viewParams);
    }

    /**
     * Determine whether scan is required based on security mode
     *
     * Strict/Balanced → true
     * Development → false
     * Custom → true if require_signature or require_permission_definition or permission_mismatch_action=block
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
     * Fetch plugin audit results from DB
     */
    protected function getPluginAuditResult(string $pluginSlug): array
    {
        $audit = PluginAudit::getBySlug($pluginSlug);

        if ($audit) {
            return $audit->toAuditArray();
        }

        // Return empty result if no audit results exist
        return [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 0,
            'audited_at' => null,
        ];
    }

    /**
     * Extract one audit array from batch-fetched audit results collection
     *
     * Return empty template for plugins that haven't been audited
     *
     * @param  \Illuminate\Support\Collection<string, PluginAudit>  $auditMap
     * @return array<string, mixed>
     */
    protected function buildAuditArrayFromMap(\Illuminate\Support\Collection $auditMap, string $slug): array
    {
        $audit = $auditMap->get($slug);
        if ($audit instanceof PluginAudit) {
            return $audit->toAuditArray();
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
     * Determine if plugin files were modified after scan based on mtime
     *
     * Lightweight check for list display. Does not calculate md5, only compares last modified times of PHP/JS/Blade
     * - Audit not executed (audited_at null) → false (badge prioritizes "not scanned")
     * - Last modified time is newer than audit time → true
     */
    protected function detectFilesChanged(string $slug, \Illuminate\Support\Collection $auditMap, PluginHealthScorer $healthScorer): bool
    {
        $audit = $auditMap->get($slug);
        if (! $audit instanceof PluginAudit || $audit->audited_at === null) {
            return false;
        }

        $latestMtime = $healthScorer->latestSourceMtime($slug);
        if ($latestMtime === null) {
            return false;
        }

        return $latestMtime > $audit->audited_at->getTimestamp();
    }

    /**
     * Build CSP compatibility array from audit results
     *
     * Match the structure that legacy CspExtensionLoader::getCspCompatibility() returned
     * Live scan (CspDiagnosticService::diagnosePlugin / CspComplianceScanner)
     * is executed only on rescan; on page display, only use plugin_audits results
     *
     * @param  array<string, mixed>  $audit
     * @return array<string, mixed>
     */
    protected function buildCspCompatibilityFromAudit(array $audit): array
    {
        return [
            'status' => $audit['csp_status'] ?? 'not_checked',
            'requires_inline_js' => (bool) ($audit['csp_requires_inline_js'] ?? false),
            'requires_inline_css' => (bool) ($audit['csp_requires_inline_css'] ?? false),
            'has_csp_config' => false,
            'csp_ready' => ! ($audit['csp_requires_inline_js'] ?? false),
            'violations' => $audit['csp_violations'] ?? [],
            'summary' => $audit['csp_summary'] ?? [],
        ];
    }

    /**
     * Audit plugin and save to DB
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
                // Get signature information
                $permissionService = app(PluginPermissionService::class);
                $summary = $permissionService->getSummary($pluginSlug);
                $signature = $summary['signature'] ?? [];

                // Verify CSP compliance status with code scan
                $cspScanner = app(\App\Services\Csp\CspComplianceScanner::class);
                $cspCompatibility = $cspScanner->scanPlugin($pluginSlug);

                // File hash (for rescan detection) and health score
                $healthScorer = app(PluginHealthScorer::class);
                $filesHash = $healthScorer->computeFilesHash($pluginSlug);

                // Extract owned_tables (auto-detected from migrations)
                $pluginName = \Illuminate\Support\Str::studly(str_replace('-', '_', $pluginSlug));
                $extensionDir = base_path("plugins/{$pluginName}");
                $tableInspection = app(PluginTableInspector::class)->inspect($extensionDir);

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
                    'owned_tables' => $tableInspection['tables'],
                ];

                Log::info('Plugin audit data', ['plugin' => $pluginSlug, 'data' => $auditData]);

                // Save to DB (base data before health score calculation)
                $audit = PluginAudit::saveAuditResult($pluginSlug, $auditData);

                // Calculate health score and persist its findings list
                // calculate() references plugin_audits rows, so execute after saveAuditResult
                try {
                    $healthResult = $healthScorer->calculate($pluginSlug);
                    $audit->update([
                        'health_score' => $healthResult->score,
                        'health_status' => $healthResult->status->value,
                        'health_issues' => array_map(fn ($issue) => $issue->jsonSerialize(), $healthResult->issues),
                    ]);
                    $audit->refresh();
                } catch (\Exception $e) {
                    Log::warning('Health score persist failed during audit', [
                        'plugin' => $pluginSlug,
                        'error' => $e->getMessage(),
                    ]);
                }

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
     * Manually audit plugin (Ajax)
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

        // Attach translated review reasons for scan result modal
        $result['formatted_attention_reasons'] = ExtensionCardPresenter::formatAttentionReasons(
            $result['risk_reasons'] ?? [],
            'admin/settings/plugins/index'
        );

        // For two-step modal: calculate activation action and installation permission
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

        // Get deduction items (exclude items with 0 deduction)
        $healthIssues = isset($healthResult)
            ? array_values(array_filter(
                array_map(fn ($i) => $i->jsonSerialize(), $healthResult->issues),
                fn ($i) => ($i['deduction'] ?? 0) !== 0,
            ))
            : [];

        // Get permission category information
        $permissionService = app(PluginPermissionService::class);
        $summary = $permissionService->getSummary($slug);
        $categories = $summary['categories'] ?? [];

        // Calculate CSP mode compatibility and extension compatibility barometer
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
        $signatureStatus = $summary['signature']['status'] ?? 'unsigned';
        $cspBarometerItems = ExtensionCardPresenter::buildCspBarometerItems($cspCompatibility, $auditedAt);
        $presetBarometerItems = ExtensionCardPresenter::buildPresetBarometerItems($healthStatus, $auditedAt, $signatureStatus);

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

    /**
     * Sequentially rescan all plugins
     *
     * Execute runPluginAudit() for all installed + uninstalled plugins
     * Synchronous execution → redirect to list with flash message after completion
     */
    public function auditAll(Request $request): \Illuminate\Http\RedirectResponse
    {
        $failed = [];

        $installed = Plugin::all()->pluck('slug')->all();
        $uninstalled = array_column($this->getUninstalledPlugins(), 'slug');
        $allSlugs = array_values(array_unique(array_filter(array_merge($installed, $uninstalled))));

        foreach ($allSlugs as $slug) {
            try {
                $this->runPluginAudit($slug);
            } catch (\Exception $e) {
                $failed[] = $slug;
                Log::error('Plugin audit-all: per-plugin failure', [
                    'plugin' => $slug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $total = count($allSlugs);
        $succeeded = $total - count($failed);

        $message = __('admin/settings/plugins/index.audit.audit_all_summary', [
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => count($failed),
        ]);

        $redirect = redirect()->route('admin.settings.plugins.index');

        return empty($failed)
            ? $redirect->with('success', $message)
            : $redirect->with('warning', $message);
    }

    public function add()
    {
        $uploadMaxBytes = $this->parsePhpSize(ini_get('upload_max_filesize'));
        $this->viewParams['uploadMaxMB'] = number_format($uploadMaxBytes / 1048576, 2);
        $this->viewParams['heading'] = __('admin/settings/plugins/add.heading');

        return view('admin::settings.plugins.add', $this->viewParams);
    }

    /**
     * Determine redirect destination after action execution
     *
     * If referer was detail page, return to detail page; otherwise redirect to list page
     */
    protected function redirectAfterPluginAction(Request $request, ?string $slug = null): \Illuminate\Http\RedirectResponse
    {
        // Return to detail page if coming from detail page
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
     * Detail page for installed plugin
     */
    public function show(string $slug)
    {
        $plugin = Plugin::where('slug', $slug)->first();

        // If installed plugin is not found, search from uninstalled directories
        if (! $plugin) {
            $uninstalledPlugin = collect($this->getUninstalledPlugins())->firstWhere('slug', $slug);

            if (! $uninstalledPlugin) {
                abort(404);
            }

            // Prepare additional data for uninstalled plugin
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
            // Prepare data for installed plugin
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

        // Map CSP status to translation key
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
     * Detail page for online (not downloaded) plugin
     */
    public function showOnline(string $slug, ExtensionSourceManager $manager)
    {
        $details = $manager->getExtensionDetails($slug, 'plugin');

        if ($details === null) {
            abort(404);
        }

        // Fallback to default image if thumbnail URL is missing or empty
        if (empty($details['thumbnail_url'])) {
            $details['thumbnail_url'] = asset('assets/images/plugin-default.svg');
        }

        $this->viewParams['details'] = $details;
        $this->viewParams['heading'] = $details['name'] ?? $slug;

        return view('admin::settings.plugins.show-online', $this->viewParams);
    }

    /**
     * Load raw data from plugin.json (for detail page)
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
     * Upload plugin (ZIP file extraction and file placement only)
     */
    public function upload(AdminPluginUploadRequest $request)
    {
        // Temporarily save ZIP file
        $file = $request->file('plugin_file');
        $fileName = $file->getClientOriginalName();
        $tempDir = storage_path('app/temp/plugins');
        File::ensureDirectoryExists($tempDir);
        $tempPath = $tempDir.'/'.$fileName;
        $file->move($tempDir, $fileName);

        try {
            // Extract, place, and resolve directory name using same common process as source download
            $result = $this->extractAndPlacePlugin($tempPath);

            if (! $result['success']) {
                return redirect()->route('admin.settings.plugins.add')
                    ->with('error', $result['error'] ?? __('admin/settings/plugins/add.messages.zip_extract_failed'));
            }

            $pluginDir = $result['directory'];

            // Discard past audit results and return to unscanned state on new placement (using slug from plugin.json)
            $pluginJsonPath = base_path("plugins/{$pluginDir}/plugin.json");
            $slugFromManifest = null;
            if (File::exists($pluginJsonPath)) {
                try {
                    $pluginData = json_decode(File::get($pluginJsonPath), true);
                    if (is_array($pluginData) && isset($pluginData['slug']) && is_string($pluginData['slug'])) {
                        $slugFromManifest = $pluginData['slug'];
                    }
                } catch (\Exception) {
                    // ignore
                }
            }
            $this->purgeAuditRecordsForSlug($slugFromManifest ?? Str::slug($pluginDir), $pluginDir);

            return redirect()->route('admin.settings.plugins.index')
                ->with('success', __('admin/settings/plugins/add.messages.upload_success'))
                ->with('uploaded_plugin_directory', $pluginDir);
        } catch (\Throwable $e) {
            if (File::exists($tempPath)) {
                File::delete($tempPath);
            }
            Log::error('Plugin upload failed', [
                'file' => $fileName,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.settings.plugins.add')
                ->with('error', __('admin/settings/plugins/add.messages.upload_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Install uninstalled plugin
     */
    public function install(AdminPluginInstallRequest $request)
    {
        $validated = $request->validated();

        $pluginDir = $validated['directory'];
        $pluginPath = base_path("plugins/{$pluginDir}");

        if (! File::exists($pluginPath)) {
            return redirect()->back()->with('error', __('admin/settings/plugins/index.messages.install_directory_not_found'));
        }

        // Server-side protection: pre-check in scan-required mode
        if (self::isScanRequired()) {
            $pluginJsonPath = base_path("plugins/{$pluginDir}/plugin.json");
            $slug = null;

            if (File::exists($pluginJsonPath)) {
                try {
                    $pluginData = json_decode(File::get($pluginJsonPath), true);
                    $slug = $pluginData['slug'] ?? null;
                } catch (\Exception $e) {
                    // Skip slug retrieval if plugin.json fails to load
                }
            }

            if ($slug) {
                $latestAudit = PluginAudit::where('plugin_slug', $slug)
                    ->latest('audited_at')
                    ->first();

                // Reject installation if not scanned
                if (! $latestAudit) {
                    return redirect()->back()->with('error', __('admin/settings/plugins/index.two_stage.install_blocked'));
                }

                // Reject installation if blocked even after scanning
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
            // Install using command
            Artisan::call('dls:plugin:install', [
                'pluginName' => $pluginDir,
            ]);

            // Retrieve installed plugin
            $plugin = Plugin::where('directory', $pluginDir)->first();

            // Perform audit after installation
            if ($plugin) {
                // If the download came from a registered source (online add
                // flow), the sidecar tells us which one. Plain ZIP uploads
                // have no sidecar and stay as 'upload'.
                $linkage = $this->consumeSourceSidecar($pluginDir);
                $installationMethod = $linkage['installation_method'] ?? 'upload';
                $sourceUrl = $linkage['installed_from_url'] ?? null;

                // Retrieve and save supply chain protection metadata from plugin.json
                $this->persistSupplyChainMetadata($plugin, $installationMethod, $sourceUrl, $linkage);

                // Record version history (initial installation)
                $this->recordVersionHistory(
                    plugin: $plugin,
                    oldVersion: null,
                    oldSigningKeyId: null,
                    oldAuthorId: null,
                    installationMethod: PluginVersionHistory::METHOD_INSTALL,
                );

                $this->runPluginAudit($plugin->slug);

                // Notification and logging of extension operations
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

            // Installation success message
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

        // Cannot uninstall active plugin
        if ($plugin->isEnabled()) {
            return back()->with('error', __('admin/settings/plugins/index.messages.uninstall_must_disable_first'));
        }

        // Save plugin information for notification
        $pluginData = [
            'name' => $this->getPluginName($plugin),
            'slug' => $plugin->slug,
            'version' => $plugin->version ?? null,
            'health_status' => 'low', // no health warning needed on uninstall
        ];

        try {
            // Uninstall using command
            $options = [
                'pluginName' => $plugin->name,
                '--force' => true,
                '--no-interaction' => true,
            ];

            // When deleting DB data as well
            if ($request->has('remove_db_data')) {
                $options['--rollback'] = true;
            }

            Artisan::call('dls:plugin:uninstall', $options);

            // Notification and logging of extension operations
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
            // Perform audit before activation (verify latest state)
            $this->runPluginAudit($plugin->slug);

            // Automatically re-audit if rescan is required
            $healthScorer = app(PluginHealthScorer::class);
            if ($healthScorer->needsRescan($plugin->slug)) {
                Log::info('Plugin files changed, re-scanning', ['plugin' => $plugin->slug]);
                $this->runPluginAudit($plugin->slug);
            }

            // Calculate health score and determine activation policy
            $healthResult = $healthScorer->calculate($plugin->slug);
            $enableAction = $healthScorer->determineEnableAction($healthResult);

            // Reject activation if blocked
            if ($enableAction === PluginEnableAction::Blocked) {
                return back()->with('error', __('admin/settings/plugins/index.enable_action.blocked_message'));
            }

            // Activate using command
            Artisan::call('dls:plugin:enable', [
                'pluginName' => $plugin->name,
            ]);

            // Notification and logging of extension operations
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
            // Disable using command
            Artisan::call('dls:plugin:disable', [
                'pluginName' => $plugin->name,
            ]);

            // Notification and logging of extension operations
            app(ExtensionOperationService::class)->recordOperation(
                ExtensionOperationService::TYPE_PLUGIN,
                ExtensionOperationService::OPERATION_DISABLED,
                [
                    'name' => $translatedName,
                    'slug' => $plugin->slug,
                    'version' => $plugin->version ?? null,
                    'health_status' => 'low', // no health warning needed on disable
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
     * Completely remove plugin (files + DB records)
     */
    public function delete(AdminPluginDeleteRequest $request)
    {
        $validated = $request->validated();

        $pluginDir = $validated['directory'];

        // Check if DB record exists
        $plugin = Plugin::where('directory', $pluginDir)->first();

        try {
            // Uninstall first if DB record exists
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

            // Delete plugin directory
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

    // Convert ZIP file size to bytes
    private function parsePhpSize($sizeStr)
    {
        // Support both uppercase and lowercase
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
                // No unit
                $value = (int) $sizeStr;
                break;
        }

        return $value;
    }

    /**
     * Check if plugin has a settings page
     * Determine settings page exists if settings_route is defined in config/admin.php
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

            // Settings page exists if settings_route is defined
            return ! empty($settingsRoute);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get plugin settings page URL
     * Get route name from settings_route in config/admin.php and generate URL
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

            // Generate URL if route exists
            if (\Route::has($settingsRoute)) {
                return route($settingsRoute);
            }

            // Output warning to log if route does not exist
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
     * Get translated plugin name
     */
    private function getPluginName($plugin)
    {
        // Prioritize name field (human-readable name) from plugin.json
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
                    // Fallback
                }
            }
        }

        return $plugin->name ?? __('admin/settings/plugins/index.messages.no_plugin_name');
    }

    /**
     * Get translated plugin description
     */
    private function getPluginDescription($plugin)
    {
        try {
            // Get description from plugin translation file
            $pluginSlug = strtolower(str_replace('Dixlase', 'dixlase-', $plugin->directory));
            $translationKey = $pluginSlug.'::admin.plugin.description';
            $description = __($translationKey);

            // If the translation key is returned as-is, no translation was found
            if ($description !== $translationKey) {
                return $description;
            }

            // Refer to multilingual description in plugin.json
            return $this->getLocalizedDescriptionFromPluginJson($plugin->directory)
                ?? $plugin->description
                ?? __('common.no_description');
        } catch (\Exception $e) {
            return $plugin->description ?? __('common.no_description');
        }
    }

    /**
     * Get description from plugin.json for current locale
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
     * Detect uninstalled plugins
     */
    private function getUninstalledPlugins()
    {
        $uninstalledPlugins = [];
        $pluginsPath = base_path('plugins');

        if (! File::exists($pluginsPath)) {
            return $uninstalledPlugins;
        }

        // Get all directories in plugins directory
        $directories = File::directories($pluginsPath);

        // Get directory names of installed plugins
        $installedDirectories = Plugin::pluck('directory')->toArray();

        foreach ($directories as $directory) {
            $dirName = basename($directory);

            // Detect plugins not registered in DB
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
     * Get plugin information from directory
     * Prefer plugin.json, fallback to composer.json
     */
    private function getPluginInfoFromDirectory($dirName)
    {
        $pluginJsonPath = base_path("plugins/{$dirName}/plugin.json");
        $composerPath = base_path("plugins/{$dirName}/composer.json");

        // Use plugin.json preferentially if it exists
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
                // Fallback to composer.json if plugin.json fails to load
            }
        }

        // Use composer.json if plugin.json does not exist or fails to load
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
     * Return list of available plugins from source (JSON API)
     */
    public function availableFromSource(ExtensionSourceManager $manager): JsonResponse
    {
        try {
            $available = $manager->listAvailablePlugins();

            // Exclude installed plugins and plugins existing on disk
            $installedSlugs = Plugin::pluck('slug')->toArray();
            $diskSlugs = collect($this->getUninstalledPlugins())->pluck('slug')->toArray();
            $excludeSlugs = array_merge($installedSlugs, $diskSlugs);

            // Exclude entries where slug is not a string as broken manifests (root fix for [object Object] issue on frontend)
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

            // Explicitly renormalize string fields for each entry (final defense against [object Object] on JS side)
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
     * Explicitly normalize string fields for API response (prevent leakage of multilingual objects, etc.)
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
        // Resolve multilingual objects for name and description with current locale
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
     * Download and place plugin from source
     */
    public function downloadFromSource(Request $request, ExtensionSourceManager $manager)
    {
        $request->validate([
            'slug' => ['required', 'string', 'max:100'],
        ]);

        $slug = trim((string) $request->input('slug'));

        try {
            // Download ZIP from source and capture which source served it
            $download = $manager->downloadWithSource($slug, 'plugin');

            // Extract and place ZIP
            $result = $this->extractAndPlacePlugin($download['path']);

            if ($result['success']) {
                $displayName = $result['name'] ?? $slug;

                // On new download, discard past audit results and return to unscanned state
                $this->purgeAuditRecordsForSlug($slug, $result['directory'] ?? null);

                // Persist source linkage as a sidecar file inside the
                // extracted plugin directory. install() reads it back
                // so source_id / source_repo / installation_method are
                // recorded on the Plugin row (otherwise update checks
                // have no way to find the matching upstream release).
                $this->writeSourceSidecar(
                    pluginDirectory: $result['directory'],
                    linkage: $manager->resolveSourceLinkage($download['source'], $slug, 'plugin'),
                );

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
     * File name of the sidecar JSON used to remember which source
     * served a downloaded extension. Lives next to plugin.json inside
     * the plugin directory; deleted again once install() has consumed
     * its contents so the metadata is not committed back to source
     * control on accident.
     */
    private const SOURCE_SIDECAR_FILENAME = '.dixlase-source.json';

    /**
     * Write the supply-chain linkage produced by ExtensionSourceManager
     * to a sidecar JSON file next to plugin.json. The install controller
     * reads it back when the user proceeds to install.
     *
     * @param  array{source_id: int, source_repo: ?string, installation_method: string, installed_from_url: ?string}  $linkage
     */
    protected function writeSourceSidecar(string $pluginDirectory, array $linkage): void
    {
        $path = base_path("plugins/{$pluginDirectory}/".self::SOURCE_SIDECAR_FILENAME);
        try {
            File::put($path, json_encode($linkage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            // Loss of the sidecar only degrades update-check linkage; do
            // not abort the install for it.
            Log::warning('Failed to write extension source sidecar', [
                'directory' => $pluginDirectory,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Read and remove the source sidecar written by downloadFromSource.
     * Returns null when no sidecar is present (e.g. plain ZIP upload).
     *
     * The file is deleted after reading so the linkage metadata does
     * not get committed alongside the plugin source if the operator
     * later commits plugins/ to version control.
     *
     * @return ?array{source_id: int, source_repo: ?string, installation_method: string, installed_from_url: ?string}
     */
    protected function consumeSourceSidecar(string $pluginDirectory): ?array
    {
        $path = base_path("plugins/{$pluginDirectory}/".self::SOURCE_SIDECAR_FILENAME);
        if (! File::exists($path)) {
            return null;
        }
        try {
            $data = json_decode(File::get($path), true);
            File::delete($path);
            if (! is_array($data) || ! isset($data['source_id'])) {
                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            Log::warning('Failed to read extension source sidecar', [
                'directory' => $pluginDirectory,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Delete past audit records for the specified plugin (to return to unscanned state on re-download)
     */
    protected function purgeAuditRecordsForSlug(string $slug, ?string $directory = null): void
    {
        $slugsToPurge = [$slug];

        // Added in case plugin.json slug differs from request slug
        if ($directory) {
            $pluginJsonPath = base_path("plugins/{$directory}/plugin.json");
            if (File::exists($pluginJsonPath)) {
                try {
                    $data = json_decode(File::get($pluginJsonPath), true);
                    if (is_array($data) && isset($data['slug']) && is_string($data['slug']) && $data['slug'] !== '') {
                        $slugsToPurge[] = $data['slug'];
                    }
                } catch (\Exception) {
                    // ignore
                }
            }
        }

        PluginAudit::whereIn('plugin_slug', array_unique($slugsToPurge))->delete();
    }

    /**
     * Common process to extract ZIP file and place in plugin directory
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
            // Get plugin folder name (first directory in ZIP)
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

            // Extract ZIP
            $zip->extractTo(base_path('plugins'));
            $zip->close();
            File::delete($zipPath);

            // Get correct directory name from plugin.json and rename
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

            // Check for existence of composer.json
            if (! File::exists(base_path("plugins/{$pluginDir}/composer.json"))) {
                File::deleteDirectory($destinationPath);

                return ['success' => false, 'error' => __('admin/settings/plugins/add.messages.composer_not_found')];
            }

            // Update Git exclusion rules and composer.local.json
            GitExcludeHelper::addPluginExclusion($pluginDir);
            GitIgnoreHelper::addPluginExclusion($pluginDir);
            ComposerLocalHelper::syncAutoload();

            // Audit is executed at install time (skipped at download time)
            // Plugin files are not executed just by being placed in plugins/
            // Audited at the appropriate timing in the 2-stage modal (scan → confirm) during installation

            // Get display name from plugin.json
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
     * Determine correct directory name from plugin.json contents
     *
     * Priority:
     * 1. Explicit package field (unique mapping between manifest and install destination)
     * 2. Last segment of namespace (e.g. Plugins\MyPlugin → MyPlugin)
     * 3. Last part of package_name (e.g. plugins/my-plugin → my-plugin)
     * 4. null (maintain existing directory name)
     */
    protected function resolvePluginDirectoryName(?array $pluginData): ?string
    {
        if (! is_array($pluginData)) {
            return null;
        }

        // Prioritize explicitly declared package field
        $package = $pluginData['package'] ?? null;
        if (is_string($package) && $package !== '') {
            return $package;
        }

        // Prefer the final segment of namespace
        $namespace = $pluginData['namespace'] ?? null;
        if (is_string($namespace) && $namespace !== '') {
            $parts = explode('\\', trim($namespace, '\\'));
            $lastSegment = end($parts);
            if ($lastSegment !== false && $lastSegment !== '') {
                return $lastSegment;
            }
        }

        // Fallback: last part of package_name
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
     * Sequentially update all updatable plugins (delegated to dls:source:update --all --type=plugin)
     */
    public function bulkUpdate(): \Illuminate\Http\RedirectResponse
    {
        $updatable = Plugin::query()->whereNotNull('available_version')->pluck('slug')->all();
        if (empty($updatable)) {
            return redirect()->route('admin.settings.plugins.index')
                ->with('info', __('admin/settings/plugins/index.updates.all_up_to_date'));
        }

        // Call individual update commands sequentially (pipeline/history/metadata updates are handled on individual side)
        $total = count($updatable);
        $succeeded = 0;
        $failed = 0;
        foreach ($updatable as $slug) {
            $code = \Illuminate\Support\Facades\Artisan::call('dls:plugin:update', [
                'slug' => $slug,
                '--force' => true,
            ]);
            $code === 0 ? $succeeded++ : $failed++;
        }

        $summary = __('admin/settings/plugins/index.updates.update_all_summary', [
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]);

        return redirect()->route('admin.settings.plugins.index')
            ->with($failed === 0 ? 'success' : 'error', $summary);
    }

    /**
     * Update check (AJAX)
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
     * Update plugin (download new version → replace)
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
            // Download new version ZIP
            $zipPath = $manager->download($slug, 'plugin', $newVersion);

            // Save metadata before update (for history recording)
            $oldVersion = $plugin->version;
            $oldSigningKeyId = $plugin->signing_key_id;
            $oldAuthorId = $plugin->author_id;

            // Backup current directory
            if (File::exists($pluginPath)) {
                File::move($pluginPath, $backupPath);
            }

            // Extract and place ZIP
            $result = $this->extractAndPlacePlugin($zipPath);

            if (! $result['success']) {
                // Restore from backup on failure
                $this->restoreFromBackup($backupPath, $pluginPath);

                return back()->with('error', $result['error']);
            }

            // Update version info in DB
            $plugin->update([
                'version' => $newVersion,
                'available_version' => null,
                'last_version_check' => now(),
            ]);

            // Update supply chain defense metadata from new plugin.json
            $this->persistSupplyChainMetadata($plugin, 'update');

            // Record version history (update)
            $this->recordVersionHistory(
                plugin: $plugin,
                oldVersion: $oldVersion,
                oldSigningKeyId: $oldSigningKeyId,
                oldAuthorId: $oldAuthorId,
                installationMethod: PluginVersionHistory::METHOD_UPDATE,
            );

            // Delete backup
            if (File::exists($backupPath)) {
                File::deleteDirectory($backupPath);
            }

            // Re-audit
            $this->runPluginAudit($slug);

            return redirect()->route('admin.settings.plugins.index')
                ->with('success', __('admin/settings/plugins/index.updates.update_success', ['name' => $plugin->name, 'version' => $newVersion]));
        } catch (\Throwable $e) {
            // Restore from backup on failure
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
     * Restore directory from backup
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
     * Extract supply chain defense metadata from plugin.json and save to Plugin
     *
     * @param  string  $installationMethod  "upload" / "marketplace" / "cli" / "github"
     * @param  ?array{source_id: int, source_repo: ?string, installation_method: string, installed_from_url: ?string}  $linkage  Optional source linkage from the install-time sidecar; when present, source_id / source_repo are persisted so update checks know where to look.
     */
    protected function persistSupplyChainMetadata(Plugin $plugin, string $installationMethod, ?string $sourceUrl = null, ?array $linkage = null): void
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

            $payload = [
                'author_id' => $data['author_id'] ?? null,
                'authority_key_id' => $data['authority_key_id'] ?? null,
                'signing_key_id' => $data['signing']['key_id'] ?? null,
                'installation_method' => $installationMethod,
                'installed_from_url' => $sourceUrl,
            ];

            if ($linkage !== null) {
                $payload['source_id'] = $linkage['source_id'] ?? null;
                $payload['source_repo'] = $linkage['source_repo'] ?? null;
            }

            $plugin->update($payload);
        } catch (\Throwable $e) {
            Log::warning('Failed to persist supply-chain metadata', [
                'plugin' => $plugin->slug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record plugin version history and log audit if signing key or owner changes
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
                'files_changed_count' => 0, // Initial release: not calculated
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

        // Log signing key changes and owner changes to audit log (approval flow etc. to be implemented in Phase 2)
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
     * Log supply chain defense events to audit log
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
