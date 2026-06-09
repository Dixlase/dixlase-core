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

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

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
        $envPath = base_path('.env');

        // Copy from .env.example if .env does not exist
        if (! File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $env = File::get($envPath);

        foreach ($values as $key => $value) {
            // Would like to use EnvHelper's formatEnvValue, but it's protected so implementing our own
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
     *
     * @param  mixed  $value
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

        // Enclose in quotes if empty string, space, or special characters are included
        if ($value === '' ||
            preg_match('/[\s"\'#$]/', $value) ||
            str_contains($value, '=')) {
            // Leave as-is if already enclosed in quotes
            if (preg_match('/^".*"$/', $value) || preg_match("/^'.*'$/", $value)) {
                return $value;
            }

            // Enclose in double quotes (escape internal double quotes)
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
