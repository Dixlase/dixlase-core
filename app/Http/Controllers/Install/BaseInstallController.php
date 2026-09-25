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

use App\Support\Install\EnvFile;
use App\Support\Install\SqliteDatabase;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Base class for installation controllers
 */
abstract class BaseInstallController extends Controller
{
    /**
     * List of available languages
     */
    protected $availableLocales;

    /**
     * Total number of installation steps
     */
    protected $total_steps = 5;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->availableLocales = array_keys(config('language.languages', []));
    }

    /**
     * Get current locale
     */
    protected function getCurrentLocale(): string
    {
        $browserLocale = substr(request()->server('HTTP_ACCEPT_LANGUAGE', 'en'), 0, 2);
        $cookieLocale = request()->cookie('install_locale');
        $sessionLocale = session('install_locale');
        $candidate = $sessionLocale ?: $cookieLocale;

        return $candidate && in_array($candidate, $this->availableLocales)
            ? $candidate
            : (in_array($browserLocale, $this->availableLocales) ? $browserLocale : 'en');
    }

    /**
     * Get common data to pass to views
     */
    protected function getViewData(int $currentStep): array
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        return [
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales,
            'current_step' => $currentStep,
            'total_steps' => $this->total_steps,
        ];
    }

    /**
     * Switch language and update .env
     *
     * @param  string  $locale
     * @return \Illuminate\Http\JsonResponse
     */
    public function setLanguage($locale)
    {
        // Only allow valid locales
        if (in_array($locale, $this->availableLocales)) {
            // Save to session (store with multiple keys to ensure persistence)
            session([
                'install_locale' => $locale,
                'app.locale' => $locale,
                'locale' => $locale,
            ]);

            // Also immediately change the locale for the current request
            app()->setLocale($locale);

            // Synchronously update .env file
            $envPath = base_path('.env');
            if (file_exists($envPath) && is_writable($envPath)) {
                $updates = [
                    'APP_LOCALE' => $locale,
                    'APP_FALLBACK_LOCALE' => $locale,
                    'APP_FAKER_LOCALE' => $locale.'_'.strtoupper($locale),
                ];

                $this->updateEnv($updates);
            }

            // settings for response
            $response = [
                'success' => true,
                'locale' => $locale,
                'message' => __('install/common.language_changed'),
            ];

            // Always return JSON (no redirect) + persist with cookie
            return response()->json($response)
                ->cookie('install_locale', $locale, 60 * 24 * 30);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('http/controllers/install/base_install_controller.invalid_language_selected'),
            ], 400);
        }
    }

    /**
     * Update .env file
     */
    protected function updateEnv(array $values): void
    {
        EnvFile::update($values);
    }

    /**
     * Format value for .env
     *
     * @param  mixed  $value
     */
    /**
     * Read a single value out of the .env file.
     *
     * Reads the file rather than env(), because the wizard rewrites .env
     * within the same request and the process-level values go stale.
     */
    protected function readEnvValue(string $key, ?string $envPath = null): string
    {
        return EnvFile::read($key, $envPath);
    }

    /**
     * Build a session cookie name unique to this installation.
     *
     * Two Dixlase sites on one hostname (localhost:8080 and localhost:8081,
     * say) would otherwise share a cookie and overwrite each other's
     * session, which surfaces as CSRF 419 errors.
     */
    protected function generateSessionCookieName(?string $siteName): string
    {
        $slug = Str::slug((string) $siteName) ?: 'dixlase';

        return strtolower($slug.'_'.Str::random(4).'_session');
    }

    /**
     * Resolve the SQLite database path the wizard should use.
     *
     * Laravel's SQLite connector needs a path it can `realpath()`. The field
     * may arrive empty or relative, so fall back to `database/database.sqlite`
     * inside the installation. Every install step resolves the path the same
     * way, so the file the connection test creates is the file the install
     * later opens.
     */
    protected function resolveSqliteDatabasePath(?string $database): string
    {
        return SqliteDatabase::resolvePath($database);
    }

    /**
     * Make sure the SQLite file exists, creating it (and its directory) when
     * it does not.
     *
     * The connector throws SQLiteDatabaseDoesNotExistException for a missing
     * file instead of creating it, so an installation whose operator never
     * ran the connection test would otherwise fail at the migration step.
     *
     * @return bool false when the file could not be created or is not writable
     */
    protected function ensureSqliteDatabaseFile(string $database): bool
    {
        return SqliteDatabase::ensureFile($database);
    }

    protected function formatEnvValue($value): string
    {
        return EnvFile::formatValue($value);
    }
}
