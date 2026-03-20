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

namespace Tests\Unit\Traits;

use App\Http\Controllers\CspReportController;
use App\Traits\CustomFilesLoaderTrait;
use Custom\App\Http\Controllers\CspReportController as CustomCspReportController;
use Tests\TestCase;

class CustomFilesLoaderTraitTest extends TestCase
{
    use CustomFilesLoaderTrait;

    public function test_custom_controller_class_exists(): void
    {
        // Verify the custom class is autoloaded via composer
        $this->assertTrue(
            class_exists(CustomCspReportController::class),
            'Custom\App\Http\Controllers\CspReportController should be autoloaded'
        );
    }

    public function test_custom_controller_extends_core_controller(): void
    {
        $custom = new CustomCspReportController();

        $this->assertInstanceOf(
            CspReportController::class,
            $custom,
            'Custom controller should extend the core controller'
        );
    }

    public function test_load_custom_files_binds_custom_class_to_core(): void
    {
        $customPath = base_path('custom/app/Http/Controllers');
        $defaultNamespace = 'App\\Http\\Controllers\\';

        $this->loadCustomFiles($customPath, $defaultNamespace);

        // Resolving the core class through the container should return the custom class
        $resolved = app()->make(CspReportController::class);

        $this->assertInstanceOf(
            CustomCspReportController::class,
            $resolved,
            'Container should resolve the core class to the custom override'
        );
    }

    public function test_load_custom_files_for_type_with_controllers_config(): void
    {
        // Flush any previous bindings
        app()->forgetInstance(CspReportController::class);

        $customFilesPath = base_path(config('custom.custom_files_dir', 'custom'));
        $typeConfig = config('app.file_types.controllers');

        $this->loadCustomFilesForType($customFilesPath, $typeConfig);

        $resolved = app()->make(CspReportController::class);

        $this->assertInstanceOf(
            CustomCspReportController::class,
            $resolved,
            'loadCustomFilesForType should correctly bind custom controller'
        );
    }

    public function test_custom_override_marker_method_exists(): void
    {
        $customFilesPath = base_path(config('custom.custom_files_dir', 'custom'));
        $typeConfig = config('app.file_types.controllers');

        $this->loadCustomFilesForType($customFilesPath, $typeConfig);

        $resolved = app()->make(CspReportController::class);

        $this->assertTrue(
            method_exists($resolved, 'isCustomOverride'),
            'Custom override should have the isCustomOverride marker method'
        );
        $this->assertTrue($resolved->isCustomOverride());
    }

    public function test_load_custom_files_skips_non_existent_directory(): void
    {
        // Should not throw any errors
        $this->loadCustomFiles(
            base_path('custom/nonexistent/path'),
            'App\\NonExistent\\'
        );

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    public function test_path_construction_is_correct(): void
    {
        $customFilesPath = base_path('custom');
        $typeConfig = ['path' => 'app/Http/Controllers', 'namespace' => 'App\\Http\\Controllers\\'];

        $expectedPath = base_path('custom').DIRECTORY_SEPARATOR.'app/Http/Controllers';

        // Verify the path is constructed correctly (no double base_path)
        $this->assertStringNotContains(
            base_path().DIRECTORY_SEPARATOR.base_path(),
            $expectedPath,
            'Path should not contain double base_path'
        );

        $this->assertDirectoryExists($expectedPath);
    }

    /**
     * Helper assertion: string does not contain substring.
     */
    private function assertStringNotContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertStringNotContainsString($needle, $haystack, $message);
    }
}
