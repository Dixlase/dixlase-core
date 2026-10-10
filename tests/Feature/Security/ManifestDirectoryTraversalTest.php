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

namespace Tests\Feature\Security;

use App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController;
use App\Http\Controllers\Admin\Settings\AdminThemesSettingsController;
use App\Support\ExtensionDirectoryName;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * When a plugin or theme archive is uploaded, the install destination is taken
 * from the manifest inside that archive:
 *
 *     $correctDir  = $this->resolvePluginDirectoryName($pluginData);
 *     $correctPath = base_path("plugins/{$correctDir}");
 *     File::move($destinationPath, $correctPath);
 *
 * The manifest is attacker-controlled, and the value was used verbatim. A
 * `"package": "../public/shell"` resolved to the document root -- confirmed in
 * a booted app that base_path('plugins/../public/shell') normalises under
 * /var/www/html/public -- so the extracted tree of attacker PHP landed
 * somewhere the web server executes it.
 *
 * The move happens at UPLOAD time. The health and security scan that is meant
 * to vet plugin code runs at INSTALL time, so the payload was already in the
 * webroot before anything looked at it.
 */
class ManifestDirectoryTraversalTest extends TestCase
{
    /**
     * The guard used to be duplicated on each admin controller. It now lives
     * in one place, reached by the admin upload and "add from source" pages
     * and by dls:plugin:download, so asserting it here covers the CLI too
     * (#488).
     */
    private function resolvePluginDir(array $manifest): ?string
    {
        return ExtensionDirectoryName::fromManifest($manifest, ExtensionDirectoryName::KIND_PLUGIN);
    }

    private function resolveThemeDir(array $manifest): ?string
    {
        return ExtensionDirectoryName::fromManifest($manifest, ExtensionDirectoryName::KIND_THEME);
    }

    /**
     * The controllers must keep reaching the shared guard rather than growing
     * their own copy back: that is how the two implementations drifted into
     * existence in the first place.
     */
    public function test_both_admin_controllers_route_through_the_shared_guard(): void
    {
        foreach ([AdminPluginsSettingsController::class, AdminThemesSettingsController::class] as $controller) {
            $source = (string) file_get_contents(
                (new ReflectionClass($controller))->getFileName() ?: ''
            );

            $this->assertStringContainsString(
                'ExtensionDirectoryName::fromManifest(',
                $source,
                $controller.' must take the install destination from the shared resolver.'
            );
            $this->assertStringNotContainsString(
                'protected function sanitise',
                $source,
                $controller.' must not carry its own copy of the path-segment guard.'
            );
        }
    }

    /**
     * @return list<array{0: string}>
     */
    public static function traversalNames(): array
    {
        return [
            'into the webroot' => ['../public/shell'],
            'above the project' => ['../../evil'],
            'plain traversal' => ['..'],
            'current directory' => ['.'],
            'absolute path' => ['/etc/cron.d/evil'],
            'nested path' => ['sub/dir'],
            'backslash path' => ['sub\\dir'],
            'leading dot' => ['.hidden'],
            'nul byte' => ["ok\0/../public"],
        ];
    }

    #[DataProvider('traversalNames')]
    public function test_plugin_manifest_cannot_choose_a_path(string $package): void
    {
        $this->assertNull(
            $this->resolvePluginDir(['package' => $package]),
            "A manifest must not be able to steer the install destination with {$package}. Returning null keeps the archive's own directory, which is the safe outcome."
        );
    }

    #[DataProvider('traversalNames')]
    public function test_theme_manifest_cannot_choose_a_path(string $package): void
    {
        $this->assertNull(
            $this->resolveThemeDir(['package' => $package]),
            "The theme path has the same defect and needs the same guard: {$package}."
        );
    }

    /**
     * The other two manifest keys reach the same interpolation, so they are
     * sanitised as well.
     *
     * They were already harder to abuse: both split on their separator and
     * keep only the final segment, so `Plugins\..\..\public` yields `public`
     * -- a single segment that lands inside plugins/ and goes nowhere. The
     * guard matters for the inputs that survive that split with a separator or
     * a traversal still attached.
     */
    public function test_namespace_and_package_name_are_guarded_too(): void
    {
        $this->assertNull(
            $this->resolvePluginDir(['namespace' => 'Plugins\\..']),
            'A namespace whose final segment is .. must not become the directory name.'
        );

        $this->assertNull(
            // No backslash, so the namespace split leaves this whole string
            // intact and the separators reach the guard.
            $this->resolvePluginDir(['namespace' => 'foo/../public']),
            'A namespace carrying forward slashes must be refused.'
        );

        $this->assertNull(
            $this->resolvePluginDir(['package_name' => 'vendor/..']),
            'A package_name whose final segment is .. must not become the directory name.'
        );
    }

    /**
     * A guard that rejects real plugin names would block every legitimate
     * upload, so the accepted shapes are pinned alongside the refused ones.
     */
    public function test_ordinary_directory_names_still_resolve(): void
    {
        $this->assertSame('DixlasePages', $this->resolvePluginDir(['package' => 'DixlasePages']));
        $this->assertSame('dixlase-seo', $this->resolvePluginDir(['package' => 'dixlase-seo']));
        $this->assertSame('My_Plugin.v2', $this->resolvePluginDir(['package' => 'My_Plugin.v2']));

        $this->assertSame(
            'MyPlugin',
            $this->resolvePluginDir(['namespace' => 'Plugins\\MyPlugin']),
            'The namespace fallback must still yield the final segment.'
        );

        $this->assertSame(
            'my-plugin',
            $this->resolvePluginDir(['package_name' => 'vendor/my-plugin']),
            'The package_name fallback must still yield the final segment.'
        );

        $this->assertSame('DixlaseOnePage', $this->resolveThemeDir(['package' => 'DixlaseOnePage']));
    }

    public function test_missing_manifest_data_is_handled(): void
    {
        $this->assertNull($this->resolvePluginDir([]));
        $this->assertNull($this->resolveThemeDir([]));
    }

    /**
     * The delete commands take the same class of value from a request rule
     * that is only `required|string`, and hand it to File::deleteDirectory().
     * base_path('plugins/../app') normalises to the application's own app/
     * directory, File::exists() confirms it, and no `plugins` row matches so
     * the "still installed" guard does not fire either.
     *
     * @return list<array{0: string}>
     */
    public static function deleteArguments(): array
    {
        return [
            'core app directory' => ['../app'],
            'storage' => ['../storage'],
            'webroot' => ['../public'],
            'above the project' => ['../../'],
            'absolute' => ['/etc'],
            'nested' => ['a/b'],
        ];
    }

    #[DataProvider('deleteArguments')]
    public function test_delete_commands_refuse_paths(string $argument): void
    {
        foreach ([\App\Console\Commands\PluginDelete::class, \App\Console\Commands\ThemeDelete::class] as $class) {
            $command = (new ReflectionClass($class))->newInstanceWithoutConstructor();
            $method = new ReflectionMethod($command, 'isSinglePathSegment');
            $method->setAccessible(true);

            $this->assertFalse(
                $method->invoke($command, $argument),
                "{$class} must refuse {$argument} before it reaches File::deleteDirectory()."
            );
        }
    }

    /**
     * The helper returning false is worth nothing if handle() never asks it.
     *
     * This assertion exists because the first version of the tests above kept
     * passing when the call site was stubbed out to `if (false)` -- they only
     * ever exercised the helper in isolation. Checking the wiring is the part
     * that actually protects the delete.
     */
    public function test_delete_commands_call_the_guard_before_deleting(): void
    {
        $commands = [
            // path => [guard argument, the actual delete call]
            'app/Console/Commands/PluginDelete.php' => ['$pluginDirectory', 'File::deleteDirectory($pluginPath)'],
            'app/Console/Commands/ThemeDelete.php' => ['$themeDirectory', 'File::deleteDirectory($themePath)'],
        ];

        foreach ($commands as $path => [$argumentVariable, $deleteCall]) {
            $source = file_get_contents(base_path($path));

            $this->assertStringContainsString(
                "isSinglePathSegment({$argumentVariable})",
                $source,
                "{$path} must run the argument through the guard."
            );

            // Matched against the call with its variable rather than
            // `File::deleteDirectory(` alone: the comments in these files
            // mention the bare function name, and searching for that finds the
            // prose before the code.
            $guardPosition = strpos($source, "isSinglePathSegment({$argumentVariable})");
            $deletePosition = strpos($source, $deleteCall);

            $this->assertNotFalse($deletePosition, "{$path} is expected to delete a directory.");
            $this->assertLessThan(
                $deletePosition,
                $guardPosition,
                "{$path} must check the argument before deleting, not after."
            );
        }
    }

    public function test_delete_commands_still_accept_real_directory_names(): void
    {
        foreach ([\App\Console\Commands\PluginDelete::class, \App\Console\Commands\ThemeDelete::class] as $class) {
            $command = (new ReflectionClass($class))->newInstanceWithoutConstructor();
            $method = new ReflectionMethod($command, 'isSinglePathSegment');
            $method->setAccessible(true);

            foreach (['DixlasePages', 'dixlase-seo', 'My_Theme.v2'] as $name) {
                $this->assertTrue(
                    $method->invoke($command, $name),
                    "{$class} must still accept the real directory name {$name}."
                );
            }
        }
    }
}
