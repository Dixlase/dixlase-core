<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Feature\Security;

use App\Models\Plugin;
use App\Providers\PluginServiceProvider;
use App\Services\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * What an installed plugin can influence at boot (#634).
 *
 * Two boundaries that used to be looser than the rest of the extension
 * model, both of which matter once a plugin's code is already running:
 *
 *   1. Loading a plugin's `config/admin/*.php` means `require`-ing its PHP.
 *      That has to follow the enabled state, because disabling a plugin is
 *      how an operator stops code they distrust.
 *   2. A manifest's `providers` list used to be checked with nothing but
 *      `class_exists()`, so it could name any autoloadable class in the
 *      application and have it registered at boot.
 *
 * The fixtures live under `storage/framework/testing/`, never in the real
 * `plugins/` directory: these tests create and delete directories.
 */
class PluginBootBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private string $sandbox = '';

    protected function tearDown(): void
    {
        if ($this->sandbox !== '' && File::isDirectory($this->sandbox)) {
            File::deleteDirectory($this->sandbox);
        }

        parent::tearDown();
    }

    // 1. A disabled plugin's config is not loaded

    public function test_enabled_plugin_directories_skip_a_disabled_plugin(): void
    {
        $root = $this->sandboxPath();

        $this->plugin('Enabled', enabled: true);
        $this->plugin('Disabled', enabled: false);

        $this->assertSame(['Enabled'], PermissionRegistry::enabledPluginDirectories($root));
    }

    public function test_installed_plugin_directories_still_report_a_disabled_plugin(): void
    {
        // The older method keeps its meaning. The boundary is which of the
        // two the config loaders call, not a change to what "installed" is.
        $root = $this->sandboxPath();

        $this->plugin('Enabled', enabled: true);
        $this->plugin('Disabled', enabled: false);

        $this->assertSame(['Disabled', 'Enabled'], PermissionRegistry::installedPluginDirectories($root));
    }

    public function test_disabling_a_plugin_removes_it_from_the_permission_set(): void
    {
        $root = $this->sandboxPath();
        $plugin = $this->plugin('Togglable', enabled: true);

        $this->assertSame(['Togglable'], PermissionRegistry::enabledPluginDirectories($root));

        $plugin->update(['enabled_at' => null]);

        $this->assertSame([], PermissionRegistry::enabledPluginDirectories($root));
    }

    // 2. A manifest provider outside the plugin's namespace is refused

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function providerClasses(): array
    {
        return [
            'own namespace' => ['Plugins\DixlaseFoo\App\Providers\ExtraServiceProvider', 'DixlaseFoo', true],
            'own namespace, leading separator' => ['\Plugins\DixlaseFoo\App\Providers\ExtraServiceProvider', 'DixlaseFoo', true],
            'own namespace, different case' => ['plugins\dixlasefoo\App\Providers\ExtraServiceProvider', 'DixlaseFoo', true],
            'another plugin' => ['Plugins\DixlaseBar\App\Providers\BarServiceProvider', 'DixlaseFoo', false],
            // Without the trailing separator in the comparison, a directory
            // whose name is a prefix of another would let that other one in.
            'a plugin whose name starts the same' => ['Plugins\DixlaseFooBar\App\Providers\X', 'DixlaseFoo', false],
            'a core class' => ['App\Providers\AppServiceProvider', 'DixlaseFoo', false],
            'a vendor class' => ['Illuminate\Foundation\Providers\ConsoleSupportServiceProvider', 'DixlaseFoo', false],
            'the plugin root alone' => ['Plugins\DixlaseFoo', 'DixlaseFoo', false],
        ];
    }

    #[DataProvider('providerClasses')]
    public function test_a_manifest_provider_is_accepted_only_inside_the_plugins_namespace(
        string $provider,
        string $directory,
        bool $expected,
    ): void {
        $belongs = (function (string $p, string $d): bool {
            return $this->providerBelongsToPlugin($p, $d);
        })->call(new PluginServiceProvider($this->app), $provider, $directory);

        $this->assertSame($expected, $belongs);
    }

    private function plugin(string $directory, bool $enabled): Plugin
    {
        File::ensureDirectoryExists($this->sandbox.'/'.$directory.'/config/admin');
        File::put($this->sandbox.'/'.$directory.'/config/admin/roles.php', '<?php return [];');

        return Plugin::create([
            'name' => $directory,
            'directory' => $directory,
            'slug' => strtolower($directory),
            'namespace' => 'Plugins\\'.$directory,
            'version' => '1.0.0',
            'enabled_at' => $enabled ? now() : null,
        ]);
    }

    private function sandboxPath(): string
    {
        $this->sandbox = storage_path('framework/testing/plugin-boot-boundary-'.uniqid());
        File::ensureDirectoryExists($this->sandbox);

        return $this->sandbox;
    }
}
