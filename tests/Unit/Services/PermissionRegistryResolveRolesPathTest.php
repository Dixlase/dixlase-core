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

namespace Tests\Unit\Services;

use App\Services\PermissionRegistry;
use Tests\TestCase;

/**
 * Verifies {@see \App\Services\PermissionRegistry::resolvePluginRolesPath()}:
 * returns the canonical (`config/admin/roles.php`) path, or null when the
 * file does not exist.
 */
class PermissionRegistryResolveRolesPathTest extends TestCase
{
    /** @var list<string> */
    private array $created = [];

    /**
     * A throwaway plugins root.
     *
     * The fixtures used to be written into the checkout's own plugins/,
     * where core's extension scan finds one that an interrupted run left
     * behind (#653). `PermissionRegistry` resolves the root through
     * `PluginHelper::pluginsPath()`, which reads it from config.
     */
    private ?string $pluginsRoot = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginsRoot = storage_path('framework/testing/roles-plugins-'.uniqid('', true));
        mkdir($this->pluginsRoot, 0777, true);
        config(['plugins.plugins_directory' => $this->pluginsRoot]);
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->created) as $dir) {
            $this->rmrf($dir);
        }
        $this->created = [];

        if ($this->pluginsRoot !== null && is_dir($this->pluginsRoot)) {
            $this->rmrf($this->pluginsRoot);
        }

        parent::tearDown();
    }

    public function test_returns_canonical_when_only_canonical_exists(): void
    {
        $slug = $this->makePluginWith(['config/admin/roles.php' => '<?php return [];']);

        $this->assertSame(
            $this->pluginsRoot.'/'.$slug.'/config/admin/roles.php',
            PermissionRegistry::resolvePluginRolesPath($slug),
        );
    }

    public function test_returns_null_when_neither_exists(): void
    {
        $slug = $this->makePluginWith([]);

        $this->assertNull(PermissionRegistry::resolvePluginRolesPath($slug));
    }

    public function test_returns_null_when_plugin_directory_does_not_exist(): void
    {
        $this->assertNull(
            PermissionRegistry::resolvePluginRolesPath('PRRTnosuch'.uniqid()),
        );
    }

    /**
     * @param  array<string, string>  $files  Relative path => file contents.
     */
    private function makePluginWith(array $files): string
    {
        // No leading underscore and no dot: isInstalledName() refuses both,
        // and resolvePluginRolesPath() consults it, so a fixture using one
        // would be testing a name core treats as not installed anywhere
        // else -- pluginDirectories() already filters it out.
        $slug = 'PRRT'.uniqid();
        $pluginDir = $this->pluginsRoot.'/'.$slug;
        mkdir($pluginDir, 0777, true);
        $this->created[] = $pluginDir;

        foreach ($files as $relative => $contents) {
            $full = $pluginDir.'/'.$relative;
            mkdir(dirname($full), 0777, true);
            file_put_contents($full, $contents);
        }

        return $slug;
    }

    private function rmrf(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            unlink($path);

            return;
        }

        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $this->rmrf($path.'/'.$entry);
            }
        }
        rmdir($path);
    }
}
