<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

use App\Console\Traits\TakesExtensionBackup;
use App\Enums\PluginEnableAction;
use App\Helpers\AdminHelper;
use App\Helpers\ComposerLocalHelper;
use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\AdminThemeDeleteRequest;
use App\Http\Requests\Admin\Settings\AdminThemeInstallRequest;
use App\Http\Requests\Admin\Settings\AdminThemeUploadRequest;
use App\Models\AuditLog;
use App\Models\Theme;
use App\Models\ThemeAudit;
use App\Models\ThemeVersionHistory;
use App\Presenters\Admin\ExtensionCardPresenter;
use App\Services\Extension\ExtensionAssetBuildReport;
use App\Services\Extension\ExtensionDisplayName;
use App\Services\Extension\ExtensionRescanService;
use App\Services\Extension\ExtensionScanPolicy;
use App\Services\Extension\ExtensionSourceSidecar;
use App\Services\Extension\ExtensionSourceSnapshot;
use App\Services\ExtensionOperationService;
use App\Services\Theme\ThemeHealthScorer;
use App\Services\Theme\ThemePermissionService;
use App\Support\ComposerLocalManifest;
use App\Support\ExtensionArchive;
use App\Support\ExtensionDirectories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminThemesSettingsController extends AdminLoggedInController
{
    use TakesExtensionBackup;

    //

    // Theme list
    public function index()
    {
        // Get installed themes (same logic as plugin management)
        $themes = Theme::all();

        // Get currently active theme
        $themeSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        $activeThemeId = $themeSetting ? (int) $themeSetting->value : null;

        // Permission service and health scorer (for file change detection)
        $permissionService = app(ThemePermissionService::class);
        $healthScorer = app(\App\Services\Theme\ThemeHealthScorer::class);

        // Detect uninstalled themes first (to collect slugs for batch query)
        $uninstalledThemes = $this->getUninstalledThemes();

        // Get audit results for all themes in 1 query (avoid N+1)
        $allSlugs = array_filter(array_merge(
            $themes->pluck('slug')->all(),
            array_column($uninstalledThemes, 'slug'),
        ));
        $auditMap = ! empty($allSlugs)
            ? ThemeAudit::whereIn('theme_slug', $allSlugs)->get()->keyBy('theme_slug')
            : collect();

        // Check if theme has settings feature
        // Prioritize database has_settings column, only check file if null
        foreach ($themes as $theme) {
            if ($theme->has_settings === null) {
                $theme->has_settings = $this->hasThemeSettings($theme);
            }

            // Get permission summary (including audit results)
            $summary = $permissionService->getSummary($theme->slug);
            $summary['audit'] = $this->buildThemeAuditArrayFromMap($auditMap, $theme->slug);
            $theme->permission_summary = $summary;

            // Restore CSP compatibility from audit results (theme_audits)
            $theme->csp_compatibility = $this->buildCspCompatibilityFromAudit($summary['audit']);
            $theme->csp_diagnostic = null;

            // File change detection (mtime based)
            $theme->files_changed = $this->detectThemeFilesChanged($theme->slug, $auditMap, $healthScorer);
        }

        // Add permission summary and audit results to uninstalled themes as well
        foreach ($uninstalledThemes as &$theme) {
            $summary = $permissionService->getSummary($theme['slug']);
            $summary['audit'] = $this->buildThemeAuditArrayFromMap($auditMap, $theme['slug']);
            $theme['permission_summary'] = $summary;

            $theme['csp_compatibility'] = $this->buildCspCompatibilityFromAudit($summary['audit']);
            $theme['csp_diagnostic'] = null;
            $theme['files_changed'] = $this->detectThemeFilesChanged($theme['slug'], $auditMap, $healthScorer);
        }
        unset($theme);

        // Pre-calculate card data
        $themeCards = [];
        foreach ($themes as $theme) {
            $card = ExtensionCardPresenter::forTheme($theme, $activeThemeId);
            // Drives the list-page rollback button, same as the detail page:
            // shown only when an automatic pre-update backup exists.
            $card['hasBackup'] = $this->latestExtensionBackupPath(
                ExtensionSourceSnapshot::KIND_THEME,
                $theme->directory,
            ) !== null;
            $themeCards[] = $card;
        }
        $uninstalledThemeCards = [];
        foreach ($uninstalledThemes as $theme) {
            $uninstalledThemeCards[] = ExtensionCardPresenter::forTheme($theme, $activeThemeId);
        }

        // For 'Update All' button: list of themes with available_version set
        $updatableExtensions = collect($themeCards)
            ->filter(fn (array $c) => ! empty($c['hasUpdateAvailable']))
            ->map(fn (array $c) => [
                'id' => $c['id'],
                'name' => $c['name'] ?? $c['slug'],
                'currentVersion' => $c['version'] ?? '',
                'availableVersion' => $c['availableVersion'] ?? '',
            ])
            ->values()
            ->all();

        $this->viewParams['themes'] = $themes;
        $this->viewParams['uninstalledThemes'] = $uninstalledThemes;
        $this->viewParams['activeThemeId'] = $activeThemeId;
        $this->viewParams['themeCards'] = $themeCards;
        $this->viewParams['uninstalledThemeCards'] = $uninstalledThemeCards;
        $this->viewParams['updatableExtensions'] = $updatableExtensions;

        return view('admin::settings.themes.index', $this->viewParams);
    }

    /**
     * Theme detail page. Mirrors AdminPluginsSettingsController::show().
     *
     * Resolves the theme by slug from either the installed list (DB)
     * or the uninstalled-on-disk list, normalises the metadata into the
     * same shape ExtensionCardPresenter::forTheme() returns, and hands
     * everything off to admin/settings/themes/show.blade.php.
     */
    public function show(string $slug)
    {
        $theme = Theme::where('slug', $slug)->first();

        if (! $theme) {
            // Fall back to themes that are present on disk but not yet
            // registered in the database — the list page surfaces them
            // under the "uninstalled" section and they need a detail
            // page too.
            $uninstalledTheme = collect($this->getUninstalledThemes())->firstWhere('slug', $slug);

            if (! $uninstalledTheme) {
                abort(404);
            }

            $permissionService = app(ThemePermissionService::class);
            $summary = $permissionService->getSummary($uninstalledTheme['slug']);
            $summary['audit'] = $this->getThemeAuditResult($uninstalledTheme['slug']);
            $uninstalledTheme['permission_summary'] = $summary;

            // CSP compatibility is theme-side, but the presenter expects
            // these keys to exist. Leave them null when no audit ran yet.
            $uninstalledTheme['csp_compatibility'] = $this->buildCspCompatibilityFromAudit($summary['audit']);
            $uninstalledTheme['csp_diagnostic'] = null;

            $card = ExtensionCardPresenter::forTheme($uninstalledTheme);
            $rawData = $uninstalledTheme;
            $isInstalled = false;
        } else {
            $permissionService = app(ThemePermissionService::class);

            // Match what index() does so the card / scan section behave
            // identically between the list and the detail page.
            if ($theme->has_settings === null) {
                $theme->has_settings = $this->hasThemeSettings($theme);
            }

            $summary = $permissionService->getSummary($theme->slug);
            $summary['audit'] = $this->getThemeAuditResult($theme->slug);
            $theme->permission_summary = $summary;
            $theme->csp_compatibility = $this->buildCspCompatibilityFromAudit($summary['audit']);
            $theme->csp_diagnostic = null;

            // Active theme highlight on the card
            $themeSetting = DB::table('theme_settings')->where('key', 'enabled_theme_id')->first();
            $activeThemeId = $themeSetting ? (int) $themeSetting->value : null;

            $card = ExtensionCardPresenter::forTheme($theme, $activeThemeId);
            $rawData = $this->loadThemeJsonForShow($theme->directory);
            $isInstalled = true;

            // Offer admin-panel rollback only when an automatic pre-update
            // backup exists for this theme (dls:theme:update takes one unless
            // --skip-backup). The button/modal are hidden otherwise.
            $hasBackup = $this->latestExtensionBackupPath(
                ExtensionSourceSnapshot::KIND_THEME,
                $theme->directory,
            ) !== null;
        }

        $this->viewParams['card'] = $card;
        $this->viewParams['rawData'] = $rawData;
        $this->viewParams['isInstalled'] = $isInstalled;
        $this->viewParams['hasBackup'] = $hasBackup ?? false;
        $this->viewParams['heading'] = $card['name'] ?? $slug;

        return view('admin::settings.themes.show', $this->viewParams);
    }

    /**
     * Roll an installed theme back to the state captured before its last
     * update, from the admin panel. Delegates to dls:theme:rollback, which
     * restores the source tree AND the prebuilt resources/assets from the
     * automatic pre-update backup (no npm run) and reverses the schema half.
     * Only reachable when a backup exists (the button is hidden otherwise).
     */
    public function rollbackTheme(int $id)
    {
        $theme = Theme::find($id);
        if (! $theme) {
            return redirect()
                ->route('admin.settings.themes.index')
                ->with('error', __('admin/settings/themes/show.rollback.not_found'));
        }

        $hasBackup = $this->latestExtensionBackupPath(
            ExtensionSourceSnapshot::KIND_THEME,
            $theme->directory,
        ) !== null;

        if (! $hasBackup) {
            return redirect()
                ->route('admin.settings.themes.show', $theme->slug)
                ->with('error', __('admin/settings/themes/show.rollback.no_backup'));
        }

        $exitCode = Artisan::call('dls:theme:rollback', [
            'slug' => $theme->slug,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            Log::error('Admin theme rollback failed', [
                'theme' => $theme->slug,
                'exit_code' => $exitCode,
                'output' => Artisan::output(),
            ]);

            return redirect()
                ->route('admin.settings.themes.show', $theme->slug)
                ->with('error', __('admin/settings/themes/show.rollback.failed'));
        }

        return redirect()
            ->route('admin.settings.themes.show', $theme->slug)
            ->with('success', __('admin/settings/themes/show.rollback.success', [
                'name' => ExtensionDisplayName::for('theme', $theme->slug),
            ]));
    }

    /**
     * Load theme.json from disk for the detail page. Returns an empty
     * array if the file is missing or unparseable so the view's
     * `@if(! empty($rawData[...]))` guards work uniformly.
     *
     * @return array<string, mixed>
     */
    protected function loadThemeJsonForShow(string $directory): array
    {
        $path = base_path("themes/{$directory}/theme.json");
        if (! File::exists($path)) {
            return [];
        }

        try {
            $data = json_decode(File::get($path), true);
        } catch (\Throwable) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Get theme audit results from DB
     */
    protected function getThemeAuditResult(string $themeSlug): array
    {
        return app(ExtensionRescanService::class)->getThemeAuditResult($themeSlug);
    }

    /**
     * Extract one audit array from batch-fetched audit results collection
     *
     * @param  \Illuminate\Support\Collection<string, ThemeAudit>  $auditMap
     * @return array<string, mixed>
     */
    protected function buildThemeAuditArrayFromMap(\Illuminate\Support\Collection $auditMap, string $slug): array
    {
        $audit = $auditMap->get($slug);
        if ($audit instanceof ThemeAudit) {
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
     * Build CSP compatibility array from audit results
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
     * Determine if theme files were modified after scan based on mtime
     */
    protected function detectThemeFilesChanged(string $slug, \Illuminate\Support\Collection $auditMap, ThemeHealthScorer $healthScorer): bool
    {
        $audit = $auditMap->get($slug);
        if (! $audit instanceof ThemeAudit || $audit->audited_at === null) {
            return false;
        }

        $latestMtime = $healthScorer->latestSourceMtime($slug);
        if ($latestMtime === null) {
            return false;
        }

        return $latestMtime > $audit->audited_at->getTimestamp();
    }

    /**
     * Audit theme and save to DB
     */
    protected function runThemeAudit(string $themeSlug): array
    {
        // Full rescan lives in the shared service (see rescanPlugin note).
        return app(ExtensionRescanService::class)->rescanTheme($themeSlug);
    }

    /**
     * Scan a theme now and resolve what its health score allows.
     *
     * Fails closed: if the scan or the score cannot be computed, the theme
     * is treated as Blocked rather than let through unchecked.
     */
    protected function themeHealthAction(string $themeSlug): PluginEnableAction
    {
        try {
            $this->runThemeAudit($themeSlug);

            $healthScorer = app(ThemeHealthScorer::class);

            return $healthScorer->determineEnableAction($healthScorer->calculate($themeSlug));
        } catch (\Throwable $e) {
            Log::warning('Theme health check failed', [
                'theme' => $themeSlug,
                'error' => $e->getMessage(),
            ]);

            return PluginEnableAction::Blocked;
        }
    }

    /**
     * Manually audit theme (Ajax)
     */
    public function audit(Request $request)
    {
        // Get with json() if JSON request
        $slug = $request->json('slug') ?? $request->input('slug');

        Log::info('Theme audit request', ['slug' => $slug, 'content_type' => $request->header('Content-Type')]);

        if (! $slug) {
            return response()->json([
                'success' => false,
                'message' => __('admin/settings/themes/index.audit.invalid_slug'),
            ], 400);
        }

        try {
            $result = $this->runThemeAudit($slug);

            // Attach translated confirmation reasons for scan result modal
            $result['formatted_attention_reasons'] = ExtensionCardPresenter::formatAttentionReasons(
                $result['risk_reasons'] ?? [],
                'admin/settings/themes/index'
            );

            // Calculate health score
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

            // Get permission category information
            $permissionService = app(ThemePermissionService::class);
            $summary = $permissionService->getSummary($slug);
            $categories = $summary['categories'] ?? [];

            return response()->json([
                'success' => true,
                'message' => __('admin/settings/themes/index.audit.completed'),
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
                'message' => __('admin/settings/themes/index.audit.failed').': '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rescan all themes sequentially
     *
     * Execute runThemeAudit() for all installed + uninstalled themes
     */
    public function auditAll(Request $request): \Illuminate\Http\RedirectResponse
    {
        $failed = [];

        $installed = Theme::all()->pluck('slug')->all();
        $uninstalled = array_column($this->getUninstalledThemes(), 'slug');
        $allSlugs = array_values(array_unique(array_filter(array_merge($installed, $uninstalled))));

        foreach ($allSlugs as $slug) {
            try {
                $this->runThemeAudit($slug);
            } catch (\Exception $e) {
                $failed[] = $slug;
                Log::error('Theme audit-all: per-theme failure', [
                    'theme' => $slug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $total = count($allSlugs);
        $succeeded = $total - count($failed);

        $message = __('admin/settings/themes/index.audit.audit_all_summary', [
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => count($failed),
        ]);

        $redirect = redirect()->route('admin.settings.themes.index');

        return empty($failed)
            ? $redirect->with('success', $message)
            : $redirect->with('warning', $message);
    }

    // Add theme
    public function add()
    {
        $uploadMaxBytes = $this->parsePhpSize(ini_get('upload_max_filesize'));
        $this->viewParams['uploadMaxMB'] = number_format($uploadMaxBytes / 1048576, 2);

        return view('admin::settings.themes.add', $this->viewParams);
    }

    /**
     * Upload theme (only ZIP extraction and file placement)
     */
    public function upload(AdminThemeUploadRequest $request)
    {
        $uploadedFile = $request->file('theme');
        $fileName = $uploadedFile->getClientOriginalName();
        $tempDir = storage_path('app/temp/themes');
        File::ensureDirectoryExists($tempDir);
        $tempPath = $tempDir.'/'.$fileName;
        $uploadedFile->move($tempDir, $fileName);

        try {
            // Extract, place, and resolve directory name with same common process as source download
            $result = $this->extractAndPlaceTheme($tempPath);

            if (! $result['success']) {
                return redirect()->route('admin.settings.themes.add')
                    ->with('error', $result['error'] ?? __('http/controllers/admin/settings/admin_themes_settings_controller.zip_extraction_failed'));
            }

            $themeDir = $result['directory'];

            // Discard past audit results and return to unscanned state on new placement
            $themeJsonPath = base_path("themes/{$themeDir}/theme.json");
            $slugFromManifest = null;
            if (File::exists($themeJsonPath)) {
                try {
                    $themeData = json_decode(File::get($themeJsonPath), true);
                    if (is_array($themeData) && isset($themeData['slug']) && is_string($themeData['slug'])) {
                        $slugFromManifest = $themeData['slug'];
                    }
                } catch (\Exception) {
                    // ignore
                }
            }
            $this->purgeAuditRecordsForSlug($slugFromManifest ?? Str::slug($themeDir), $themeDir);

            return redirect()->route('admin.settings.themes.index')
                ->with('success', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_upload_completed_install_from_list'))
                ->with('uploaded_theme_directory', $themeDir);
        } catch (\Throwable $e) {
            if (File::exists($tempPath)) {
                File::delete($tempPath);
            }
            Log::error('Theme upload failed', [
                'file' => $fileName,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.settings.themes.add')
                ->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_upload_failed').$e->getMessage());
        }
    }

    /**
     * Install uninstalled theme
     */
    public function install(AdminThemeInstallRequest $request)
    {
        $validated = $request->validated();

        $themeDir = $validated['directory'];

        // Server-side gate, the same one plugins have: under a preset that
        // requires a scan, a theme whose health check resolves to Blocked
        // is not installed. A theme runs PHP just like a plugin, and until
        // this check the Strict preset let any theme in unscanned. The scan
        // is run here rather than read from an earlier one, so a theme
        // uploaded before the preset was tightened is judged as it is now.
        if (ExtensionScanPolicy::isScanRequired()
            && $this->themeHealthAction(Theme::resolveSlug($themeDir)) === PluginEnableAction::Blocked) {
            return redirect()->back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_install_blocked'));
        }

        try {
            // If the download came from a registered source (online add
            // flow), the sidecar written by downloadFromSource() tells us
            // which one. Plain ZIP uploads have no sidecar and stay as
            // 'install'. Read it before the install command runs: the
            // command links the row from the same sidecar (and removes
            // it once the row is written), and the metadata below needs
            // the values too.
            $sidecar = app(ExtensionSourceSidecar::class);
            $linkage = $sidecar->read(base_path("themes/{$themeDir}"));

            // Execute Artisan command to install theme (with --force option)
            $exitCode = Artisan::call('dls:theme:install', array_filter([
                'themeName' => $themeDir,
                '--force' => true,
                '--no-interaction' => true,
                '--source' => $linkage['source_id'] ?? null,
            ], fn ($v) => $v !== null));
            $sidecar->delete(base_path("themes/{$themeDir}"));

            if ($exitCode !== 0) {
                $output = Artisan::output();
                Log::error('Theme installation command failed', [
                    'directory' => $themeDir,
                    'exit_code' => $exitCode,
                    'output' => $output,
                ]);

                return redirect()->back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_installation_failed'));
            }

            // Add theme exclusion rules to .git/info/exclude and .gitignore
            GitExcludeHelper::addThemeExclusion($themeDir);
            GitIgnoreHelper::addThemeExclusion($themeDir);

            // Get installed themes and perform audit and notification
            $theme = Theme::where('directory', $themeDir)->first();
            if ($theme) {
                $installationMethod = $linkage['installation_method'] ?? 'install';
                $sourceUrl = $linkage['installed_from_url'] ?? null;

                // Capture supply-chain metadata from theme.json
                $this->persistSupplyChainMetadata($theme, $installationMethod, $sourceUrl, $linkage);

                // Record version history (install: no old values)
                $this->recordVersionHistory(
                    theme: $theme,
                    oldVersion: null,
                    oldSigningKeyId: null,
                    oldAuthorId: null,
                    installationMethod: ThemeVersionHistory::METHOD_INSTALL,
                );

                // Run audit after installation
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

            $redirect = redirect()->route('admin.settings.themes.index')
                ->with('success', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_successfully_installed'));

            // The install succeeds even when the asset build fails, but the
            // console output is discarded by Artisan::call(), so say so here
            // or the theme silently renders without its JS / CSS.
            $buildFailure = app(ExtensionAssetBuildReport::class)->failureFor(base_path("themes/{$themeDir}"));
            if ($buildFailure !== null) {
                $redirect->with('warning', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_asset_build_failed', [
                    'command' => $buildFailure['command'],
                    'directory' => $themeDir,
                ]));
            }

            return $redirect;
        } catch (\Exception $e) {
            Log::error('Theme installation failed', [
                'directory' => $themeDir,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_installation_failed_with_reason').$e->getMessage());
        }
    }

    /**
     * Uninstall theme
     */
    public function uninstall($id, Request $request)
    {
        $theme = Theme::findOrFail($id);

        // Default theme cannot be uninstalled
        $defaultThemeSlug = config('themes.default_theme_slug', 'dixlase-onepage');
        if ($theme->slug === $defaultThemeSlug) {
            return back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.default_theme_cannot_uninstall'));
        }

        // Active theme cannot be uninstalled
        $themeSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        $activeThemeId = $themeSetting ? (int) $themeSetting->value : null;

        if ($activeThemeId && $theme->id == $activeThemeId) {
            return back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.active_theme_cannot_uninstall'));
        }

        // Save theme information for notification
        $themeData = [
            'name' => $theme->name,
            'slug' => $theme->slug,
            'version' => $theme->version ?? null,
            'health_status' => 'low', // no health warning needed on uninstall
        ];

        try {
            // Uninstall using command
            $options = [
                'themeName' => $theme->slug,
                '--force' => true,
                '--no-interaction' => true,
            ];

            // If DB data should also be deleted
            if ($request->has('remove_db_data')) {
                $options['--rollback'] = true;
            }

            Artisan::call('dls:theme:uninstall', $options);

            // Notification and logging of extension operations
            app(ExtensionOperationService::class)->recordOperation(
                ExtensionOperationService::TYPE_THEME,
                ExtensionOperationService::OPERATION_UNINSTALLED,
                $themeData
            );

            return redirect()->route('admin.settings.themes.index')
                ->with('success', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_uninstalled'));
        } catch (\Exception $e) {
            Log::error('Theme uninstall failed', [
                'theme' => $theme->name,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_uninstallation_failed').$e->getMessage());
        }
    }

    /**
     * Switch (activate) theme
     */
    public function switch($id)
    {
        $theme = Theme::findOrFail($id);

        try {
            // Audit before activation and refuse a Blocked theme -- the same
            // rule plugin activation applies. The audit used to run here with
            // its result thrown away, so a theme failing every check could
            // still be made the live theme.
            if ($this->themeHealthAction($theme->slug) === PluginEnableAction::Blocked) {
                return redirect()->back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_switch_blocked'));
            }

            // Switch theme using Artisan command
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

                return redirect()->back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_switch_failed'));
            }

            // Add theme exclusion rules to .git/info/exclude and .gitignore
            GitExcludeHelper::addThemeExclusion($theme->directory);
            GitIgnoreHelper::addThemeExclusion($theme->directory);

            // Notification and logging of extension operations
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
                ->with('success', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_switched'));
        } catch (\Exception $e) {
            Log::error('Theme switch failed', [
                'theme' => $theme->name,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_switch_failed_with_reason').$e->getMessage());
        }
    }

    /**
     * Completely delete theme (files + DB records)
     */
    public function delete(AdminThemeDeleteRequest $request)
    {
        $validated = $request->validated();

        $themeDir = $validated['directory'];

        // Check if DB record exists
        $theme = Theme::where('directory', $themeDir)->first();

        try {
            // If DB record exists, uninstall first
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

                    return redirect()->back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_uninstallation_failed_general'));
                }
            }

            // Delete theme directory
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

                return redirect()->back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_deletion_failed'));
            }

            // Remove theme exclusion rules from .git/info/exclude and .gitignore
            GitExcludeHelper::removeThemeExclusion($themeDir);
            GitIgnoreHelper::removeThemeExclusion($themeDir);

            return redirect()->route('admin.settings.themes.index')
                ->with('success', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_successfully_deleted'));
        } catch (\Exception $e) {
            Log::error('Theme deletion failed', [
                'directory' => $themeDir,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_deletion_failed_with_reason').$e->getMessage());
        }
    }

    /**
     * Convert PHP size notation to bytes
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
     * Check if theme has settings functionality
     */
    private function hasThemeSettings($theme)
    {
        $themeSlug = $theme->slug;
        $themeDirectory = $theme->directory;

        // Check if route file exists
        $routeFile = base_path("themes/{$themeDirectory}/routes/admin.php");

        if (! file_exists($routeFile)) {
            return false;
        }

        // Check route file contents
        $routeContent = file_get_contents($routeFile);

        // Check if settings route is defined (supports new route structure)
        // Check both '/settings/themes/settings' route and 'settings' method
        return str_contains($routeContent, '/settings/themes/settings')
            && str_contains($routeContent, 'settings');
    }

    /**
     * Detect uninstalled themes
     */
    private function getUninstalledThemes()
    {
        $uninstalledThemes = [];
        $themesPath = base_path('themes');

        if (! File::exists($themesPath)) {
            return $uninstalledThemes;
        }

        // Get all directories in themes directory
        $directories = File::directories($themesPath);

        // Get directory names of installed themes (same logic as plugin management)
        $installedDirectories = Theme::pluck('directory')->toArray();

        foreach ($directories as $directory) {
            $dirName = basename($directory);

            // Skip move-aside copies for the same reason as the plugin list:
            // a theme update leaves DixlaseOnePage.stale.<timestamp> next to
            // the live directory, complete enough to look installable, and
            // installing one runs its migrations while its ServiceProvider can
            // never resolve.
            if (! ExtensionDirectories::isInstalledName($dirName)) {
                continue;
            }

            // Detect themes not registered in DB
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
     * Get theme information from directory
     * Prioritize theme.json, fallback to composer.json
     */
    private function getThemeInfoFromDirectory($dirName)
    {
        $themeJsonPath = base_path("themes/{$dirName}/theme.json");
        $composerPath = base_path("themes/{$dirName}/composer.json");

        // Use theme.json preferentially if it exists
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
                // Fallback to composer.json if theme.json fails to load
            }
        }

        // Use composer.json if theme.json does not exist or fails to load
        if (! File::exists($composerPath)) {
            return;
        }

        try {
            $jsonContent = File::get($composerPath);
            $composerData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return;
            }

            // Get display-name (extra.dixlase.display-name → extra.display-name → directory name)
            $displayName = $composerData['extra']['dixlase']['display-name']
                ?? $composerData['extra']['display-name']
                ?? $dirName;

            $authors = $composerData['authors'] ?? [];
            $firstAuthor = $authors[0] ?? [];

            // Get version (extra.dixlase.version → version → default)
            $version = $composerData['extra']['dixlase']['version']
                ?? $composerData['version']
                ?? '1.0.0';

            // Get slug (extra.dixlase.slug → extra.slug → kebab-case from directory name)
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
     * Run theme seeder
     */
    protected function runThemeSeeder(string $themeDirectory): void
    {
        try {
            // Run seeder using Artisan command
            Artisan::call('dls:theme:seed', [
                'themeName' => $themeDirectory,
            ]);

            Log::info('Theme seeder executed via command', [
                'theme' => $themeDirectory,
            ]);
        } catch (\Exception $e) {
            // Continue installation even if seeder execution fails
            Log::warning('Theme seeder execution failed', [
                'theme' => $themeDirectory,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Return available theme list from source (JSON API)
     */
    public function availableFromSource(\App\Services\Extension\ExtensionSourceManager $manager): \Illuminate\Http\JsonResponse
    {
        try {
            $available = $manager->listAvailableThemes();

            // Exclude themes that are already installed or exist on disk
            $installedSlugs = Theme::pluck('slug')->toArray();
            $diskSlugs = collect($this->getUninstalledThemes())->pluck('slug')->toArray();
            $excludeSlugs = array_merge($installedSlugs, $diskSlugs);

            // Exclude entries where slug is not a string as broken manifests
            $filtered = array_values(array_filter(
                $available,
                function (array $theme) use ($excludeSlugs) {
                    if (! isset($theme['slug']) || ! is_string($theme['slug']) || $theme['slug'] === '') {
                        Log::warning('Online theme entry dropped due to invalid slug', ['entry' => $theme]);

                        return false;
                    }

                    return ! in_array($theme['slug'], $excludeSlugs);
                }
            ));

            // Normalize string fields at API response time
            $normalized = array_map(
                fn (array $theme) => $this->normalizeExtensionEntry($theme),
                $filtered
            );

            return response()->json([
                'success' => true,
                'themes' => $normalized,
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
     * Explicitly normalize string fields for API response
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
     * Download and place theme from source
     */
    public function downloadFromSource(Request $request, \App\Services\Extension\ExtensionSourceManager $manager)
    {
        $request->validate([
            'slug' => ['required', 'string', 'max:100'],
        ]);

        $slug = trim((string) $request->input('slug'));

        try {
            // Download ZIP from source and capture which source served it
            $download = $manager->downloadWithSource($slug, 'theme');

            // Extract and place ZIP
            $result = $this->extractAndPlaceTheme($download['path']);

            if ($result['success']) {
                $displayName = $result['name'] ?? $slug;

                // Discard past audit results and reset to unscanned state on new download
                $this->purgeAuditRecordsForSlug($slug, $result['directory'] ?? null);

                // Persist source linkage as a sidecar file inside the
                // extracted theme directory. install() reads it back
                // so source_id / source_repo / installation_method are
                // recorded on the Theme row (otherwise update checks
                // have no way to find the matching upstream release).
                app(ExtensionSourceSidecar::class)->write(
                    base_path("themes/{$result['directory']}"),
                    $manager->resolveSourceLinkage($download['source'], $slug, 'theme'),
                );

                return redirect()->route('admin.settings.themes.index')
                    ->with('success', __('admin/settings/themes/add.messages.download_success', ['name' => $displayName]))
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
     * Delete past audit records for the specified theme (to reset to unscanned state on re-download)
     */
    protected function purgeAuditRecordsForSlug(string $slug, ?string $directory = null): void
    {
        $slugsToPurge = [$slug];

        if ($directory) {
            $themeJsonPath = base_path("themes/{$directory}/theme.json");
            if (File::exists($themeJsonPath)) {
                try {
                    $data = json_decode(File::get($themeJsonPath), true);
                    if (is_array($data) && isset($data['slug']) && is_string($data['slug']) && $data['slug'] !== '') {
                        $slugsToPurge[] = $data['slug'];
                    }
                } catch (\Exception) {
                    // ignore
                }
            }
        }

        ThemeAudit::whereIn('theme_slug', array_unique($slugsToPurge))->delete();
    }

    /**
     * Common process to extract ZIP file and place in theme directory
     *
     * @return array{success: bool, directory?: string, error?: string}
     */
    protected function extractAndPlaceTheme(string $zipPath, bool $pendingInstall = true): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            File::delete($zipPath);

            return ['success' => false, 'error' => __('admin/settings/themes/add.messages.zip_extract_failed')];
        }

        $themeDirectory = resource_path('views/themes/');

        try {
            // The archive must hold exactly one top-level directory (see
            // ExtensionArchive): anything else in it would be extracted next to
            // the theme without being checked.
            $extractedRootDir = ExtensionArchive::singleRootDirectory($zip);

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

            // Extract into a staging directory and move only that directory
            $extracted = ExtensionArchive::extractSingleRoot($zip, $extractedRootDir, $themeDirectory);
            $zip->close();
            File::delete($zipPath);

            if (! $extracted) {
                return ['success' => false, 'error' => __('admin/settings/themes/add.messages.zip_extract_failed')];
            }

            // Extracted directory path
            $extractedDirPath = $themeDirectory.$extractedRootDir;

            // Get correct directory name from theme.json
            $themeJsonPath = $extractedDirPath.'/theme.json';
            if (File::exists($themeJsonPath)) {
                try {
                    $themeData = json_decode(File::get($themeJsonPath), true);
                    $correctDir = $this->resolveThemeDirectoryName($themeData);

                    if ($correctDir) {
                        $directoryName = $correctDir;
                        $destinationPath = $themeDirectory.$directoryName;
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to resolve theme directory name', [
                        'directory' => $extractedRootDir,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Check if exists
            if (File::exists($destinationPath) && $extractedDirPath !== $destinationPath) {
                File::deleteDirectory($extractedDirPath);

                return ['success' => false, 'error' => __('admin/settings/themes/add.messages.directory_exists', ['directory' => $directoryName])];
            }

            // Rename extracted directory (if necessary)
            if (is_dir($extractedDirPath) && basename($extractedDirPath) !== $directoryName) {
                File::move($extractedDirPath, $destinationPath);
            }

            // Check theme.json existence
            if (! File::exists($destinationPath.'/theme.json')) {
                File::deleteDirectory($destinationPath);

                return ['success' => false, 'error' => __('admin/settings/themes/add.messages.theme_json_not_found')];
            }

            // A fresh upload/download is not installed yet: keep its
            // autoload.files out of composer.local.json until dls:theme:install.
            if ($pendingInstall) {
                ComposerLocalManifest::markPendingInstall($destinationPath);
            }

            // Update Git exclusion rules and composer.local.json
            GitExcludeHelper::addThemeExclusion($directoryName);
            GitIgnoreHelper::addThemeExclusion($directoryName);
            ComposerLocalHelper::syncAutoload();

            // Get display name from theme.json
            $displayName = null;
            if (isset($themeData) && is_array($themeData)) {
                $displayName = $themeData['name'] ?? null;
            }

            return ['success' => true, 'directory' => $directoryName, 'name' => $displayName];
        } catch (\Exception $e) {
            if (isset($destinationPath) && File::exists($destinationPath)) {
                File::deleteDirectory($destinationPath);
            }
            File::delete($zipPath);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sequentially update all updatable themes
     */
    public function bulkUpdate(): \Illuminate\Http\RedirectResponse
    {
        $updatable = Theme::query()->whereNotNull('available_version')->pluck('slug')->all();
        if (empty($updatable)) {
            return redirect()->route('admin.settings.themes.index')
                ->with('info', __('admin/settings/themes/index.updates.all_up_to_date'));
        }

        $total = count($updatable);
        $succeeded = 0;
        $failed = 0;
        foreach ($updatable as $slug) {
            $code = \Illuminate\Support\Facades\Artisan::call('dls:theme:update', [
                'slug' => $slug,
                '--force' => true,
            ]);
            $code === 0 ? $succeeded++ : $failed++;
        }

        $summary = __('admin/settings/themes/index.updates.update_all_summary', [
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]);

        return redirect()->route('admin.settings.themes.index')
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
     * Update theme (download new version → replace)
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
            // Download new version ZIP
            $zipPath = $manager->download($slug, 'theme', $newVersion);

            // Save metadata before update (for history recording)
            $oldVersion = $theme->version;
            $oldSigningKeyId = $theme->signing_key_id;
            $oldAuthorId = $theme->author_id;

            // Backup current directory
            if (File::exists($themePath)) {
                File::move($themePath, $backupPath);
            }

            // Extract and place ZIP
            // An update replaces an installed theme, so it is not pending install.
            $result = $this->extractAndPlaceTheme($zipPath, pendingInstall: false);

            if (! $result['success']) {
                $this->restoreFromBackup($backupPath, $themePath);

                return back()->with('error', $result['error']);
            }

            // Update version info in DB
            $theme->update([
                'version' => $newVersion,
                'available_version' => null,
                'last_version_check' => now(),
            ]);

            // Refresh supply-chain metadata from new theme.json
            $this->persistSupplyChainMetadata($theme, 'update');

            // Record version history (update)
            $this->recordVersionHistory(
                theme: $theme,
                oldVersion: $oldVersion,
                oldSigningKeyId: $oldSigningKeyId,
                oldAuthorId: $oldAuthorId,
                installationMethod: ThemeVersionHistory::METHOD_UPDATE,
            );

            // Delete backup
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
     * Extract supply-chain defense metadata from theme.json and save to Theme.
     *
     * Mirror of AdminPluginsSettingsController::persistSupplyChainMetadata().
     *
     * @param  string  $installationMethod  "upload" / "marketplace" / "cli" / "github"
     * @param  ?array{source_id: int, source_repo: ?string, installation_method: string, installed_from_url: ?string}  $linkage  Optional source linkage from the install-time sidecar; when present, source_id / source_repo are persisted so update checks know where to look.
     */
    protected function persistSupplyChainMetadata(Theme $theme, string $installationMethod, ?string $sourceUrl = null, ?array $linkage = null): void
    {
        $themeJsonPath = base_path("themes/{$theme->directory}/theme.json");
        if (! File::exists($themeJsonPath)) {
            return;
        }

        try {
            $data = json_decode(File::get($themeJsonPath), true);
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

            $theme->update($payload);
        } catch (\Throwable $e) {
            Log::warning('Failed to persist theme supply-chain metadata', [
                'theme' => $theme->slug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record theme version history and log audit if signing key or owner changes.
     *
     * Mirror of AdminPluginsSettingsController::recordVersionHistory(). The
     * theme_slug column is intentionally a free string (no FK), so the row
     * survives theme uninstall — see docs/development/supply-chain.md.
     */
    protected function recordVersionHistory(
        Theme $theme,
        ?string $oldVersion,
        ?string $oldSigningKeyId,
        ?string $oldAuthorId,
        string $installationMethod,
    ): void {
        $newSigningKeyId = $theme->signing_key_id;
        $newAuthorId = $theme->author_id;
        $signingKeyChanged = $oldSigningKeyId !== null && $oldSigningKeyId !== $newSigningKeyId;
        $authorIdChanged = $oldAuthorId !== null && $oldAuthorId !== $newAuthorId;

        $member = AdminHelper::getMember();

        try {
            ThemeVersionHistory::create([
                'theme_slug' => $theme->slug,
                'old_version' => $oldVersion,
                'new_version' => $theme->version,
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
                'installed_from_url' => $theme->installed_from_url,
                'applied_by_id' => $member?->id,
                'applied_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record theme version history', [
                'theme' => $theme->slug,
                'error' => $e->getMessage(),
            ]);
        }

        if ($signingKeyChanged) {
            $this->logSupplyChainEvent($theme, AuditLog::ACTION_THEME_SIGNING_KEY_CHANGED, [
                'old_signing_key_id' => $oldSigningKeyId,
                'new_signing_key_id' => $newSigningKeyId,
            ]);
        }

        if ($authorIdChanged) {
            $this->logSupplyChainEvent($theme, AuditLog::ACTION_THEME_AUTHOR_ID_CHANGED, [
                'old_author_id' => $oldAuthorId,
                'new_author_id' => $newAuthorId,
            ]);
        }
    }

    /**
     * Log supply-chain defense events to audit log.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logSupplyChainEvent(Theme $theme, string $action, array $context = []): void
    {
        try {
            $fullContext = array_merge([
                'theme_slug' => $theme->slug,
                'theme_name' => $theme->name,
                'theme_version' => $theme->version,
            ], $context);

            \App\Facades\Audit::log([
                'category' => AuditLog::CATEGORY_SYSTEM,
                'action' => $action,
                'target' => $theme,
                'target_label' => $theme->name,
                'severity' => AuditLog::SEVERITY_WARNING,
                'context' => $fullContext,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to log theme supply-chain event', [
                'theme' => $theme->slug,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine correct directory name from theme.json contents
     *
     * Priority:
     * 1. Explicit package field (unique mapping between manifest and installation destination)
     * 2. Final segment of namespace (e.g. Themes\MyTheme → MyTheme)
     * 3. Last part of package_name
     * 4. null (keep existing directory name)
     */
    protected function resolveThemeDirectoryName(?array $themeData): ?string
    {
        if (! is_array($themeData)) {
            return null;
        }

        // Prioritize explicit package field first
        $package = $themeData['package'] ?? null;
        if (is_string($package) && $package !== '') {
            return $this->sanitiseThemeDirectoryName($package);
        }

        // Prioritize final segment of namespace
        $namespace = $themeData['namespace'] ?? null;
        if (is_string($namespace) && $namespace !== '') {
            $parts = explode('\\', trim($namespace, '\\'));
            $lastSegment = end($parts);
            if ($lastSegment !== false && $lastSegment !== '') {
                return $this->sanitiseThemeDirectoryName($lastSegment);
            }
        }

        // Fallback: last part of package_name
        $packageName = $themeData['package_name'] ?? null;
        if (is_string($packageName) && $packageName !== '') {
            $parts = explode('/', $packageName);
            $lastSegment = end($parts);
            if ($lastSegment !== false && $lastSegment !== '') {
                return $this->sanitiseThemeDirectoryName($lastSegment);
            }
        }

        return null;
    }

    /**
     * Constrain a manifest-supplied directory name to a single path segment.
     *
     * Same defect and same reasoning as
     * AdminPluginsSettingsController::sanitisePluginDirectoryName(): the value
     * comes from the uploaded theme.json and the caller interpolates it into a
     * base_path()/File::move() destination, so `"package": "../public/shell"`
     * relocated the extracted tree into the document root -- and it happens at
     * upload time, before any scan runs.
     *
     * Returning null means "keep the directory the archive already used",
     * which is the safe outcome; rewriting the name would install the theme
     * somewhere its manifest never asked for.
     */
    protected function sanitiseThemeDirectoryName(string $candidate): ?string
    {
        $candidate = trim($candidate);

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $candidate) !== 1) {
            Log::warning('Refused a theme directory name that is not a single path segment', [
                'candidate' => $candidate,
            ]);

            return null;
        }

        if (str_contains($candidate, '..')) {
            Log::warning('Refused a theme directory name containing traversal', [
                'candidate' => $candidate,
            ]);

            return null;
        }

        return $candidate;
    }
}
