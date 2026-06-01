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

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Controllers\Install;

use App\Services\Install\InstallThemeDownloader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Install Controller
 */
class InstallController extends Controller
{
    // List of available languages
    protected $availableLocales;

    private $total_steps = 5;

    public function __construct()
    {
        $this->availableLocales = array_keys(config('language.languages', []));
    }

    // Initial screen
    public function index()
    {
        // ✅ Clear session data at install start (preserve language settings)
        $installData = session('install_data', []);
        session()->forget('install_data');
        session(['install_data' => $installData]);

        // Get language settings from session/cookie, default considers browser language settings

        $browserLocale = substr(request()->server('HTTP_ACCEPT_LANGUAGE', 'en'), 0, 2);
        $cookieLocale = request()->cookie('install_locale');
        $sessionLocale = session('install_locale');
        $candidate = $sessionLocale ?: $cookieLocale;
        $locale = $candidate && in_array($candidate, $this->availableLocales)
            ? $candidate
            : (in_array($browserLocale, $this->availableLocales) ? $browserLocale : 'en');
        app()->setLocale($locale);

        $requirements = $this->checkServerRequirements();

        // Themes shipped via GitHub Release ZIPs that are not bundled in the
        // user's release tarball. Surface them to the install index so the
        // user can download them in place before continuing.
        $downloader = app(InstallThemeDownloader::class);
        $missingDownloadableThemes = $requirements['has_theme']
            ? []
            : $downloader->missingThemes();

        return view('install.index', [
            'requirements' => $requirements,
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales,
            'missingDownloadableThemes' => $missingDownloadableThemes,
        ]);
    }

    /**
     * Download a registered theme from GitHub into themes/<directory>.
     *
     * Only callable before INSTALLED=true (the install.* route group is the
     * boundary that enforces this).
     */
    public function downloadTheme(Request $request, InstallThemeDownloader $downloader): JsonResponse
    {
        $directory = (string) $request->input('directory', '');
        if ($downloader->find($directory) === null) {
            return response()->json([
                'success' => false,
                'message' => __('install/index.theme_download.invalid'),
            ], 422);
        }

        try {
            $downloader->download($directory);
        } catch (\Throwable $e) {
            Log::channel('install')->error('Theme download failed', [
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('install/index.theme_download.failed', ['error' => $e->getMessage()]),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('install/index.theme_download.success'),
        ]);
    }

    /**
     * Check if server meets Laravel 12 requirements
     *
     * @return array
     */
    protected function checkServerRequirements()
    {
        // Required extensions (installation cannot proceed if missing)
        $requiredExtensions = [
            'Ctype' => extension_loaded('ctype'),
            'cURL' => extension_loaded('curl'),
            'DOM' => extension_loaded('dom'),
            'Fileinfo' => extension_loaded('fileinfo'),
            'JSON' => extension_loaded('json'),
            'Mbstring' => extension_loaded('mbstring'),
            'OpenSSL' => extension_loaded('openssl'),
            'PCRE' => extension_loaded('pcre'),
            'PDO' => extension_loaded('pdo'),
            'PDO MySQL or SQLite' => extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite'),
            'Tokenizer' => extension_loaded('tokenizer'),
            'XML' => extension_loaded('xml'),
            'GD' => extension_loaded('gd'),
            'Intl' => extension_loaded('intl'),
            'Zip' => extension_loaded('zip'),
        ];

        // Recommended extensions (can proceed if missing but affects performance and functionality)
        $recommendedExtensions = [
            'Redis' => extension_loaded('redis'),
            'OPcache' => extension_loaded('Zend OPcache'),
        ];

        // Optional extensions (nice to have)
        $optionalExtensions = [
            'BCMath' => extension_loaded('bcmath'),
        ];

        // Check existence of storage subdirectories and auto-create
        $storageDirs = [
            'framework/views',
            'framework/cache/data',
            'framework/sessions',
            'logs',
        ];
        foreach ($storageDirs as $dir) {
            $path = storage_path($dir);
            if (! is_dir($path)) {
                @mkdir($path, 0777, true);
            }
        }

        // Permission check
        $permissions = [
            'storage' => is_writable(storage_path()),
            'storage/framework/views' => is_writable(storage_path('framework/views')),
            'storage/framework/cache' => is_writable(storage_path('framework/cache')),
            'storage/framework/sessions' => is_writable(storage_path('framework/sessions')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            '.env' => is_writable(base_path()) || is_writable(base_path('.env')),
            'public' => is_writable(base_path('public')),
        ];

        // PHP settings check
        $phpSettings = [
            'memory_limit' => $this->checkMemoryLimit(128),
            'max_execution_time' => $this->checkMaxExecutionTime(60),
        ];

        // Check theme existence (whether there is at least one directory with theme.json)
        $themesPath = base_path('themes');
        $hasTheme = false;
        if (is_dir($themesPath)) {
            foreach (new \DirectoryIterator($themesPath) as $dir) {
                if ($dir->isDot() || ! $dir->isDir()) {
                    continue;
                }
                if (file_exists($dir->getPathname().'/theme.json')) {
                    $hasTheme = true;
                    break;
                }
            }
        }

        return [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'required_extensions' => $requiredExtensions,
            'recommended_extensions' => $recommendedExtensions,
            'optional_extensions' => $optionalExtensions,
            'permissions' => $permissions,
            'php_settings' => $phpSettings,
            'has_theme' => $hasTheme,
        ];
    }

    /**
     * Check if memory_limit meets minimum value
     */
    private function checkMemoryLimit(int $requiredMb): array
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return ['ok' => true, 'current' => __('install/index.php_settings.unlimited'), 'required' => $requiredMb.'M'];
        }
        $currentMb = (int) $limit;
        if (str_contains(strtolower($limit), 'g')) {
            $currentMb = (int) $limit * 1024;
        }

        return ['ok' => $currentMb >= $requiredMb, 'current' => $limit, 'required' => $requiredMb.'M'];
    }

    /**
     * Check if max_execution_time meets minimum value
     */
    private function checkMaxExecutionTime(int $requiredSeconds): array
    {
        $current = (int) ini_get('max_execution_time');
        // 0 is unlimited
        $ok = ($current === 0) || ($current >= $requiredSeconds);

        return [
            'ok' => $ok,
            'current' => $current === 0 ? __('install/index.php_settings.unlimited') : $current.'s',
            'required' => $requiredSeconds.'s',
        ];
    }
}
