<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Custom file override loading
 */
trait CustomFilesLoaderTrait
{
    /**
     * Load custom override files for a given file type configuration.
     *
     * @param  string  $customFilesPath  Absolute path to the custom files directory
     * @param  array{path: string, namespace: string}  $typeConfig  File type configuration
     */
    public function loadCustomFilesForType(string $customFilesPath, array $typeConfig): void
    {
        $customPath = $customFilesPath.DIRECTORY_SEPARATOR.$typeConfig['path'];
        $defaultNamespace = $typeConfig['namespace'];

        $this->loadCustomFiles($customPath, $defaultNamespace);
    }

    /**
     * Scan custom directory and bind custom classes to replace core classes.
     *
     * Custom files use the `Custom\` namespace prefix (e.g. `Custom\App\Http\Controllers\FooController`)
     * to override core classes (e.g. `App\Http\Controllers\FooController`).
     *
     * @param  string  $customPath  Absolute path to scan for custom PHP files
     * @param  string  $defaultNamespace  Core namespace prefix (e.g. `App\Http\Controllers\`)
     */
    public function loadCustomFiles(string $customPath, string $defaultNamespace): void
    {
        if (! File::exists($customPath)) {
            return;
        }

        foreach (File::allFiles($customPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Build relative path including filename, then convert to class name
            $relativePathname = Str::replaceFirst(
                $customPath.DIRECTORY_SEPARATOR,
                '',
                $file->getPathname()
            );
            $className = $this->getClassNameFromPath($relativePathname);

            // Custom class: Custom\App\Http\Controllers\FooController (matches composer autoload)
            $customClass = 'Custom\\'.$defaultNamespace.$className;
            // Core class: App\Http\Controllers\FooController
            $coreClass = $defaultNamespace.$className;

            if (class_exists($customClass) && class_exists($coreClass)) {
                app()->bind($coreClass, $customClass);
            }
        }
    }

    /**
     * Convert a relative file path to a class name.
     */
    private function getClassNameFromPath(string $relativePath): string
    {
        return str_replace(['/', '.php'], ['\\', ''], $relativePath);
    }

    /*

    public function loadCustomConfigs($path)
    {
        if (File::isDirectory($path)) {
            foreach (File::allFiles($path) as $file) {
                $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $config = Config::get($filename, []);

                // Load custom settings file
                $customConfig = require $file->getPathname();

                if (!is_array($customConfig)) {
                    throw new \UnexpectedValueException("Config file {$file->getPathname()} must return an array.");
                }

                // Prioritize mode in file, fallback to default mode
                $mergeMode = $customConfig['_merge_mode'] ?? config('app.default_merge_mode', 'merge');

                if ($mergeMode === 'replace') {
                    Config::set($filename, $customConfig);
                } else {
                    Config::set($filename, array_merge_recursive($config, $customConfig));
                }
            }
        }
    }

    // Load custom routes
    public function loadCustomRoutes($path)
    {
        if (File::isDirectory($path)) {
            foreach (File::allFiles($path) as $file) {
                // Specify middleware (can be changed as needed)
                Route::middleware('web')
                    ->group($file->getPathname());
            }
        }
    }

    // Load custom language files
    public function loadCustomLang($path)
    {
        if (File::isDirectory($path)) {
            foreach (File::directories($path) as $localePath) {
                $locale = basename($localePath);
                foreach (File::allFiles($localePath) as $file) {
                    $group = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                    // Get default translations
                    $defaultLang = Lang::getLoader()->load($locale, $group) ?? [];

                    // Get custom translations
                    $customLang = require $file->getPathname();

                    if (!is_array($customLang)) {
                        throw new \UnexpectedValueException("Language file {$file->getPathname()} must return an array.");
                    }

                    // Prioritize mode in file, fallback to default mode
                    $mergeMode = $customLang['_merge_mode'] ?? config('app.default_merge_mode', 'merge');

                    if ($mergeMode === 'replace') {
                        $mergedLang = $customLang;
                    } else {
                        $mergedLang = array_merge_recursive($defaultLang, $customLang);
                    }

                    // Register language lines
                    Lang::addLines([$group => $mergedLang], $locale);
                }
            }
        }
    }

    // Load custom view files
    public function loadCustomViews($path)
    {
        if (File::exists($path)) {
            // Load custom views
            View::addLocation($path);
        }
    }

    // Function to recursively merge custom config arrays
    function array_merge_recursive_custom(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = $this->array_merge_recursive_custom($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    // Load custom controllers
    public function loadCustomControllers($customPath, $defaultNamespace = 'App\\Http\\Controllers\\')
    {
        if (!File::exists($customPath)) {
            return;
        }

        foreach (File::allFiles($customPath) as $file) {
            $relativePath = Str::replaceFirst($customPath, '', $file->getPath());
            $className = $this->getClassNameFromPath($relativePath);

            // Identify custom class and Core class
            $customClass = $defaultNamespace . 'Custom\\' . $className;
            $coreClass = $defaultNamespace . $className;

            if (class_exists($customClass)) {
                if (class_exists($coreClass)) {
                    // Choose merge or replace
                    $mergeMode = config('custom.default_merge_mode', 'merge');

                    if ($mergeMode === 'replace') {
                        App::bind($coreClass, $customClass);
                    } elseif ($mergeMode === 'merge') {
                        $mergedClass = $this->mergeControllers($coreClass, $customClass);
                        App::bind($coreClass, $mergedClass);
                    }
                } else {
                    // Bind as-is if Core class does not exist
                    App::bind($customClass, $customClass);
                }
            }
        }
    }

    // Get custom controller class name
    private function getClassNameFromPath($relativePath)
    {
        return str_replace(['/', '.php'], ['\\', ''], $relativePath);
    }

    // Merge Core class and custom class
    private function mergeControllers($coreClass, $customClass)
    {
        return new class($coreClass, $customClass) extends $coreClass {
            public function __construct($coreClass, $customClass)
            {
                parent::__construct();
                $this->custom = new $customClass();
            }

            public function __call($method, $args)
            {
                if (method_exists($this->custom, $method)) {
                    return $this->custom->$method(...$args);
                }

                return parent::__call($method, $args);
            }
        };
    }

    */
}
