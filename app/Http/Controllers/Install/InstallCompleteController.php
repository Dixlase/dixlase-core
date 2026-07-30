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

namespace App\Http\Controllers\Install;

use App\Models\CoreVersionHistory;
use App\Services\Core\CoreUpdater;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Install - Completion screen
 */
class InstallCompleteController extends BaseInstallController
{
    /**
     * Display completion screen
     */
    public function show()
    {
        Log::channel('install')->info('=== InstallCompleteController::show() start ===');

        // Clear redirect loop prevention flag
        session()->forget('_redirect_to_complete');

        // Log current environment variables
        $installed = env('INSTALLED');
        Log::channel('install')->info('INSTALLED environment variable value: '.var_export($installed, true));

        // Determine installation status
        $isInstalled = ($installed === 'true' || $installed === true);

        if ($isInstalled) {
            Log::channel('install')->info('INSTALLED=true: Redirect to front page');

            return redirect('/')->with('message', __('http/controllers/install/install_complete_controller.install_already_completed'));
        }

        Log::channel('install')->info('Display installation completion screen (waiting for button press)');

        // Retrieve admin panel URL (admin_url is Global scope, so get from global_settings via SettingResolver)
        $adminSlug = 'admin';
        try {
            $resolver = app(\App\Services\Site\SettingResolver::class);
            $dbAdminUrl = $resolver->get('admin_url');
            if ($dbAdminUrl) {
                $adminSlug = $dbAdminUrl;
            }
        } catch (\Throwable $e) {
            Log::channel('install')->warning('Failed to get admin_url setting: '.$e->getMessage());
        }

        // Check force_ssl settings
        $forceSsl = false;

        // First check FORCE_SSL in .env
        $envForceSsl = env('FORCE_SSL');
        if ($envForceSsl === 'true' || $envForceSsl === true) {
            $forceSsl = true;
        } else {
            // Also check from global_settings (force_ssl is Global scope)
            try {
                $resolver = app(\App\Services\Site\SettingResolver::class);
                $forceSsl = (bool) $resolver->get('force_ssl');
            } catch (\Throwable $e) {
                Log::channel('install')->warning('Failed to get force_ssl setting: '.$e->getMessage());
            }
        }

        Log::channel('install')->info('force_ssl setting: '.($forceSsl ? 'true' : 'false'));

        // Reliably retrieve APP_URL from .env
        $envAppUrl = env('APP_URL');
        if (! $envAppUrl) {
            // Fallback: construct current URL from request
            $scheme = ($forceSsl || request()->isSecure()) ? 'https' : 'http';
            $host = request()->getHost();
            $port = request()->getPort();

            if (($scheme === 'http' && $port != 80) || ($scheme === 'https' && $port != 443)) {
                $envAppUrl = $scheme.'://'.$host.':'.$port;
            } else {
                $envAppUrl = $scheme.'://'.$host;
            }
        } else {
            // If APP_URL exists, convert to https when force_ssl is enabled
            if ($forceSsl) {
                $envAppUrl = preg_replace('/^http:/', 'https:', $envAppUrl);
            }
        }

        config()->set('app.url', $envAppUrl);

        // Retrieve application URL
        $appUrl = rtrim(config('app.url'), '/');
        // Retrieve admin panel URL
        $adminUrl = rtrim($appUrl.'/'.$adminSlug, '/');
        $adminLoginUrl = $adminUrl.'/login';

        // Retrieve admin_mode (Global scope, so get from global_settings via SettingResolver)
        $isSimpleMode = false;
        try {
            $resolver = app(\App\Services\Site\SettingResolver::class);
            $adminMode = $resolver->get('admin_mode');
            $isSimpleMode = ((string) $adminMode === '0' || $adminMode === null);
        } catch (\Throwable $e) {
            Log::channel('install')->warning('Failed to get admin_mode setting: '.$e->getMessage());
        }

        // Delete session data
        session()->forget('install_data');

        Log::channel('install')->info('Display completion screen: appUrl='.$appUrl.', adminLoginUrl='.$adminLoginUrl.', isSimpleMode='.($isSimpleMode ? 'true' : 'false'));
        Log::channel('install')->info('APP_URL retrieval result: '.$envAppUrl);
        Log::channel('install')->info('Redirect flag cleared');

        Log::channel('install')->info('=== InstallCompleteController::show() end ===');

        // Display completion screen (setting INSTALLED=true is done in finalize method)
        $forceSslEnabled = $forceSsl;

        return view('install.complete', compact('appUrl', 'adminUrl', 'adminLoginUrl', 'isSimpleMode', 'adminSlug', 'forceSslEnabled'));
    }

    /**
     * Finalize installation (set INSTALLED=true)
     */
    public function finalize(Request $request)
    {
        Log::channel('install')->info('=== InstallCompleteController::finalize() start ===');

        // Set INSTALLED=true & restore session driver to guard-aware-database
        Log::channel('install')->info('Setting INSTALLED=true...');
        $this->updateEnv([
            'INSTALLED' => 'true',
            'SESSION_DRIVER' => 'guard-aware-database',
        ]);

        // Apply environment variables immediately (reflect to current process with putenv)
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // Do not run Artisan commands (to prevent APP_KEY regeneration and session destruction)
        Log::channel('install')->info('INSTALLED=true set & session driver restored to guard-aware-database', [
            'env_INSTALLED' => env('INSTALLED'),
            'putenv_check' => getenv('INSTALLED'),
        ]);

        // Record a baseline `core_version_history` row so subsequent
        // `dls:core:update` runs record `from: <real version>` instead of
        // `from: 0.0.0` (issue #171 Finding D). Extracted into its own
        // method so tests can exercise the ledger-write branch without
        // going through updateEnv() — which writes to .env and would
        // leave the test environment in an INSTALLED=true state that
        // breaks the rest of the install test suite (issue #171-D
        // follow-up).
        $this->recordBaselineVersionHistoryIfNeeded();

        // Get redirect destination
        $redirectTo = $request->input('redirect_to');

        Log::channel('install')->info('finalize: Redirect destination', [
            'redirect_to' => $redirectTo,
            'request_all' => $request->all(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : 'no session',
        ]);

        if ($redirectTo) {
            // If redirect destination is specified
            Log::channel('install')->info('finalize: Executing redirect', ['url' => $redirectTo]);

            return redirect($redirectTo)->with('message', __('http/controllers/install/install_complete_controller.install_completed'));
        } else {
            // Return JSON response for AJAX calls
            Log::channel('install')->info('finalize: Returning JSON response');

            return response()->json(['success' => true, 'message' => __('http/controllers/install/install_complete_controller.install_completed')]);
        }

        Log::channel('install')->info('=== InstallCompleteController::finalize() end ===');
    }

    /**
     * Insert a baseline `core_version_history` row when both:
     *   1. `VERSION` file exists at the repo root
     *      (`CoreUpdater::readVersionFromDisk()` returns non-null)
     *   2. The ledger is currently empty
     *
     * This is the fix for issue #171 Finding D: without a baseline row,
     * the first `dls:core:update` after a clean install records
     * `from: 0.0.0`, which subsequently breaks `dls:core:rollback`
     * (Finding B — rollback re-fetches vendor from GitHub release
     * `v0.0.0`, which does not exist).
     *
     * Wrapped in try/catch so a bookkeeping failure never fails the
     * install — the operator can `dls:core:reconcile --confirm` after
     * the fact if this ever misfires.
     *
     * `protected` (not `private`) so tests can exercise this method in
     * isolation, without invoking `finalize()` which mutates `.env` and
     * therefore leaks state across the install test suite.
     *
     * Returns the created row for callers who want to log it, or null
     * when the guard skipped the write (either condition failed) or
     * an exception was swallowed.
     */
    protected function recordBaselineVersionHistoryIfNeeded(): ?CoreVersionHistory
    {
        try {
            $onDiskVersion = CoreUpdater::readVersionFromDisk();
            $ledgerEmpty = CoreVersionHistory::query()->doesntExist();

            if ($onDiskVersion === null || ! $ledgerEmpty) {
                Log::channel('install')->info('Baseline core_version_history row NOT recorded', [
                    'on_disk_version' => $onDiskVersion,
                    'ledger_empty' => $ledgerEmpty,
                ]);

                return null;
            }

            $row = CoreVersionHistory::create([
                'old_version' => null,
                'new_version' => $onDiskVersion,
                'files_changed_count' => 0,
                'lines_added' => 0,
                'lines_removed' => 0,
                'signing_key_changed' => false,
                'author_id_changed' => false,
                'installation_method' => 'install',
                'installed_from_url' => null,
                'downloaded_sha256' => null,
                'applied_by_id' => null,
                'applied_at' => now(),
            ]);

            Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
            Log::channel('install')->info("Recorded baseline core_version_history row: v{$onDiskVersion} (installation_method=install)");

            return $row;
        } catch (\Throwable $e) {
            // Never fail the install over a bookkeeping row — log and continue.
            Log::channel('install')->warning('Failed to record baseline core_version_history row (non-fatal): '.$e->getMessage());

            return null;
        }
    }

    /**
     * Update .env file
     */
    protected function updateEnv(array $values): void
    {
        $envPath = base_path('.env');

        // Copy from .env.example if .env does not exist
        if (! File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $env = File::get($envPath);

        foreach ($values as $key => $value) {
            // Wrap in quotes if value contains spaces, special characters, or is empty
            $formattedValue = $this->formatEnvValue($value);

            if (preg_match("/^{$key}=/m", $env)) {
                // Update existing value
                $env = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$formattedValue}",
                    $env
                );
            } else {
                // Append to end if not present in .env
                $env .= "\n{$key}={$formattedValue}";
            }
        }

        File::put($envPath, $env);
    }

    /**
     * Format value for .env
     */
    protected function formatEnvValue($value): string
    {
        // Empty string if null
        if ($value === null) {
            return '';
        }

        // Convert to string if boolean
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        // Wrap in quotes if empty string, contains spaces or special characters
        if ($value === '' ||
            preg_match('/[\s"\'#$]/', $value) ||
            str_contains($value, '=')) {
            // Leave as-is if already quoted
            if (preg_match('/^".*"$/', $value) || preg_match("/^'.*'$/", $value)) {
                return $value;
            }

            // Wrap in double quotes (escape internal double quotes)
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
