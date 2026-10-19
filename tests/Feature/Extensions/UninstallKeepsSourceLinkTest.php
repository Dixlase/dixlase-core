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

namespace Tests\Feature\Extensions;

use App\Models\ExtensionSource;
use App\Models\Plugin;
use App\Models\Theme;
use App\Services\Extension\ExtensionDownloadFreshness;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\ExtensionSourceSidecar;
use App\Services\Extension\PluginAutoloadState;
use App\Services\Tailwind\PluginSourceAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

/**
 * Uninstalling an extension while keeping its files hands the source
 * linkage back to those files, so the stale-download notice and the next
 * install still know where the extension came from (dixlase-core#485).
 *
 * The plugins and themes roots point at a throwaway tree under
 * storage/framework/testing/, so nothing touches the real plugins/ or themes/.
 */
class UninstallKeepsSourceLinkTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    /** @var array{source_id: int, source_repo: string, installation_method: string, installed_from_url: string} */
    private array $linkage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = 'storage/framework/testing/uninstall-source-'.uniqid();
        File::ensureDirectoryExists(base_path($this->root.'/plugins'));
        File::ensureDirectoryExists(base_path($this->root.'/themes'));
        config([
            'plugins.plugins_directory' => $this->root.'/plugins',
            'themes.theme_directory' => $this->root.'/themes',
        ]);

        // Keep the uninstall command's other side effects off the real tree.
        $aggregator = Mockery::mock(PluginSourceAggregator::class);
        $aggregator->shouldReceive('regenerate')->andReturn([]);
        $this->app->instance(PluginSourceAggregator::class, $aggregator);
        $autoload = Mockery::mock(PluginAutoloadState::class);
        $autoload->shouldReceive('withhold')->andReturn(true);
        $this->app->instance(PluginAutoloadState::class, $autoload);

        $source = ExtensionSource::query()->create([
            'name' => 'Acme', 'type' => 'github', 'base_url' => 'https://api.github.com',
            'owner' => 'Acme', 'is_enabled' => true, 'priority' => 0,
        ]);
        $this->linkage = [
            'source_id' => $source->id,
            'source_repo' => 'plugin-acme-example',
            'installation_method' => 'github',
            'installed_from_url' => 'https://github.com/Acme/plugin-acme-example',
        ];
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path($this->root));

        parent::tearDown();
    }

    public function test_plugin_uninstall_restores_the_sidecar_and_the_stale_notice_returns(): void
    {
        $dir = $this->makeDirectory('plugins', 'AcmeExample', 'plugin.json');
        Plugin::create([
            'name' => 'AcmeExample',
            'package_name' => 'acme/acme-example',
            'directory' => 'AcmeExample',
            'namespace' => 'Plugins\\AcmeExample',
            'slug' => 'acme-example',
            'version' => '0.1.0',
            'installed_at' => now(),
        ] + $this->linkage);

        $this->artisan('dls:plugin:uninstall', ['pluginName' => 'AcmeExample', '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('plugins', ['name' => 'AcmeExample']);
        $this->assertSame($this->linkage, app(ExtensionSourceSidecar::class)->read($dir));
        $this->assertSame('0.2.0', $this->freshness('acme-example', 'plugin')->newerRelease('plugin', 'AcmeExample', 'acme-example', '0.1.0'));

        // A reinstall links the row to the same source again.
        $this->assertSame($this->linkage, app(ExtensionSourceManager::class)->resolveInstallLinkage(
            'acme-example',
            'plugin',
            null,
            app(ExtensionSourceSidecar::class)->read($dir),
            'acme/acme-example',
        ));
    }

    public function test_plugin_uninstall_writes_nothing_for_an_unlinked_plugin(): void
    {
        $dir = $this->makeDirectory('plugins', 'AcmeLocal', 'plugin.json');
        Plugin::create([
            'name' => 'AcmeLocal',
            'directory' => 'AcmeLocal',
            'namespace' => 'Plugins\\AcmeLocal',
            'slug' => 'acme-local',
            'version' => '0.1.0',
            'installed_at' => now(),
        ]);

        $this->artisan('dls:plugin:uninstall', ['pluginName' => 'AcmeLocal', '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($dir.'/'.ExtensionSourceSidecar::FILENAME);
    }

    public function test_theme_uninstall_restores_the_sidecar_and_the_stale_notice_returns(): void
    {
        $dir = $this->makeDirectory('themes', 'AcmeTheme', 'theme.json');
        Theme::create([
            'name' => 'AcmeTheme',
            'directory' => 'AcmeTheme',
            'slug' => 'acme-theme',
            'version' => '0.1.0',
            'installed_at' => now(),
        ] + $this->linkage);

        $this->artisan('dls:theme:uninstall', ['themeName' => 'acme-theme', '--force' => true, '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('themes', ['slug' => 'acme-theme']);
        $this->assertSame($this->linkage, app(ExtensionSourceSidecar::class)->read($dir));
        $this->assertSame('0.2.0', $this->freshness('acme-theme', 'theme')->newerRelease('theme', 'AcmeTheme', 'acme-theme', '0.1.0'));
    }

    private function makeDirectory(string $kind, string $name, string $manifest): string
    {
        $dir = base_path("{$this->root}/{$kind}/{$name}");
        File::ensureDirectoryExists($dir);
        File::put($dir.'/'.$manifest, json_encode(['name' => $name, 'version' => '0.1.0']));

        return $dir;
    }

    /**
     * The real freshness check with only the release lookup stubbed, so the
     * sidecar is read from disk where the uninstall left it.
     */
    private function freshness(string $slug, string $kind): ExtensionDownloadFreshness
    {
        $sources = Mockery::mock(ExtensionSourceManager::class);
        $sources->shouldReceive('latestReleaseVersionFor')->with($slug, $kind, $this->linkage['source_id'])->andReturn('0.2.0');

        return new ExtensionDownloadFreshness($sources, app(ExtensionSourceSidecar::class));
    }
}
