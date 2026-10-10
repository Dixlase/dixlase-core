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

declare(strict_types=1);

namespace Tests\Unit\Services\Extension;

use App\Models\Plugin;
use App\Services\Extension\PluginAutoloadState;
use App\Services\Tailwind\PluginSourceAggregator;
use App\Support\ComposerLocalManifest;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * A disabled plugin's `autoload.files` stay out of the autoloader, and the
 * plugin lifecycle keeps that list in step.
 *
 * Everything runs against a throwaway tree under storage/framework/testing/:
 * the state file and the plugin directories live there, never in the real
 * plugins/ or storage/. The composer run is replaced by a recorder, so no
 * composer.local.json is written and no dump-autoload starts.
 */
class PluginAutoloadStateTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/plugin-autoload-'.uniqid('', true));
        mkdir($this->root.'/plugins', 0777, true);

        // Keep the commands' side effects off the real tree.
        $aggregator = Mockery::mock(PluginSourceAggregator::class);
        $aggregator->shouldReceive('regenerate')->andReturn([]);
        $this->app->instance(PluginSourceAggregator::class, $aggregator);
        $this->app->make(Kernel::class)->registerCommand(new class extends Command
        {
            protected $signature = 'dls:plugin:symlink {action} {plugin?}';

            public function handle(): int
            {
                return 0;
            }
        });
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->root);

        parent::tearDown();
    }

    public function test_reconcile_withholds_every_plugin_on_disk_that_is_not_enabled(): void
    {
        $this->makePluginDir('On');
        $this->makePluginDir('Off');
        $this->makePluginDir('Orphan'); // on disk, no row: uninstalled with files kept
        $this->makePluginRow('On', enabled: true);
        $this->makePluginRow('Off', enabled: false);

        $state = $this->state();

        $this->assertTrue($state->reconcile());
        $this->assertSame(['Off', 'Orphan'], ComposerLocalManifest::disabledPlugins($this->root));
        $this->assertFalse($state->reconcile(), 'A second run has nothing to change.');
    }

    public function test_allow_removes_the_plugin_and_regenerates_strictly(): void
    {
        $this->makePluginDir('Helpers');
        ComposerLocalManifest::writeDisabledPlugins($this->root, ['Helpers', 'Other']);

        $state = $this->state();

        $this->assertTrue($state->allow('Helpers'));
        $this->assertSame(['Other'], ComposerLocalManifest::disabledPlugins($this->root));
        $this->assertSame([true], $state->calls);
    }

    public function test_allow_puts_the_plugin_back_when_the_regeneration_fails(): void
    {
        $this->makePluginDir('Helpers');
        ComposerLocalManifest::writeDisabledPlugins($this->root, ['Helpers']);

        $state = $this->state(results: [false, true]);

        $this->assertFalse($state->allow('Helpers'));
        $this->assertSame(['Helpers'], ComposerLocalManifest::disabledPlugins($this->root));
        // The strict attempt, then the rewrite that matches the restored list.
        $this->assertSame([true, false], $state->calls);
    }

    public function test_a_plugin_without_autoload_files_never_runs_composer(): void
    {
        $this->makePluginDir('Plain', files: false);
        ComposerLocalManifest::writeDisabledPlugins($this->root, ['Plain']);

        $state = $this->state();

        $this->assertTrue($state->allow('Plain'));
        $this->assertTrue($state->withhold('Plain'));
        $this->assertSame(['Plain'], ComposerLocalManifest::disabledPlugins($this->root));
        $this->assertSame([], $state->calls);
    }

    public function test_withhold_regenerates_only_when_the_list_changes(): void
    {
        $this->makePluginDir('Helpers');

        $state = $this->state();

        $this->assertTrue($state->withhold('Helpers'));
        $this->assertTrue($state->withhold('Helpers'));
        $this->assertSame(['Helpers'], ComposerLocalManifest::disabledPlugins($this->root));
        $this->assertSame([true], $state->calls);
    }

    public function test_enable_regenerates_before_the_plugin_is_marked_enabled(): void
    {
        $this->makePluginDir('Helpers');
        $plugin = $this->makePluginRow('Helpers', enabled: false);
        ComposerLocalManifest::writeDisabledPlugins($this->root, ['Helpers']);

        $state = $this->state();
        $state->onRegenerate = function () use ($plugin): void {
            $this->assertNull($plugin->fresh()->enabled_at, 'The map must be regenerated before the plugin is enabled.');
        };
        $this->app->instance(PluginAutoloadState::class, $state);

        $this->artisan('dls:plugin:enable', ['pluginName' => $plugin->name])->assertExitCode(0);

        $this->assertNotNull($plugin->fresh()->enabled_at);
        $this->assertSame([], ComposerLocalManifest::disabledPlugins($this->root));
        $this->assertSame([true], $state->calls);
    }

    public function test_enable_is_refused_when_the_autoloader_cannot_be_regenerated(): void
    {
        $this->makePluginDir('Helpers');
        $plugin = $this->makePluginRow('Helpers', enabled: false);
        ComposerLocalManifest::writeDisabledPlugins($this->root, ['Helpers']);

        $state = $this->state(results: [false, true]);
        $this->app->instance(PluginAutoloadState::class, $state);

        $this->artisan('dls:plugin:enable', ['pluginName' => $plugin->name])->assertExitCode(1);

        $this->assertNull($plugin->fresh()->enabled_at);
        $this->assertSame(['Helpers'], ComposerLocalManifest::disabledPlugins($this->root));
    }

    public function test_disable_withholds_the_files_after_the_plugin_is_marked_disabled(): void
    {
        $this->makePluginDir('Helpers');
        $plugin = $this->makePluginRow('Helpers', enabled: true);

        $state = $this->state();
        $state->onRegenerate = function () use ($plugin): void {
            $this->assertNull($plugin->fresh()->enabled_at, 'The plugin must be disabled before its files are withheld.');
        };
        $this->app->instance(PluginAutoloadState::class, $state);

        $this->artisan('dls:plugin:disable', ['pluginName' => $plugin->name])->assertExitCode(0);

        $this->assertNull($plugin->fresh()->enabled_at);
        $this->assertSame(['Helpers'], ComposerLocalManifest::disabledPlugins($this->root));
        $this->assertSame([true], $state->calls);
    }

    public function test_uninstall_keeping_the_files_withholds_them(): void
    {
        $this->makePluginDir('Helpers');
        $plugin = $this->makePluginRow('Helpers', enabled: false);

        $state = $this->state();
        $this->app->instance(PluginAutoloadState::class, $state);

        $this->artisan('dls:plugin:uninstall', ['pluginName' => $plugin->name, '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('plugins', ['name' => $plugin->name]);
        $this->assertSame(['Helpers'], ComposerLocalManifest::disabledPlugins($this->root));
        $this->assertSame([true], $state->calls);
    }

    public function test_sync_command_reconciles_and_regenerates(): void
    {
        $this->makePluginDir('On');
        $this->makePluginDir('Off');
        $this->makePluginRow('On', enabled: true);

        $state = $this->state();
        $this->app->instance(PluginAutoloadState::class, $state);

        $this->artisan('dls:plugin:sync-autoload')->assertExitCode(0);

        $this->assertSame(['Off'], ComposerLocalManifest::disabledPlugins($this->root));
        $this->assertSame([true], $state->calls);
    }

    /**
     * @param  list<bool>  $results  what each successive regeneration reports
     */
    private function state(array $results = []): PluginAutoloadState
    {
        // Records each regeneration instead of rewriting composer.local.json
        // and running composer.
        $state = new class($this->root) extends PluginAutoloadState
        {
            /** @var list<bool> requireDump of each call */
            public array $calls = [];

            /** @var list<bool> */
            public array $results = [];

            public ?\Closure $onRegenerate = null;

            protected function regenerate(bool $requireDump): bool
            {
                $this->calls[] = $requireDump;

                if ($this->onRegenerate !== null) {
                    ($this->onRegenerate)();
                }

                return $this->results === [] ? true : array_shift($this->results);
            }
        };
        $state->results = $results;

        return $state;
    }

    private function makePluginDir(string $name, bool $files = true): void
    {
        $dir = $this->root.'/plugins/'.$name;
        mkdir($dir.'/app', 0777, true);
        file_put_contents($dir.'/plugin.json', '{"name":"'.$name.'"}');

        if ($files) {
            file_put_contents($dir.'/helpers.php', "<?php\n");
            file_put_contents($dir.'/composer.json', '{"autoload":{"files":["helpers.php"]}}');
        } else {
            file_put_contents($dir.'/composer.json', '{"autoload":{"psr-4":{}}}');
        }
    }

    private function makePluginRow(string $directory, bool $enabled): Plugin
    {
        return Plugin::create([
            'name' => $directory.'Autoload',
            'package_name' => 'dixlase/'.strtolower($directory).'-autoload',
            'directory' => $directory,
            'namespace' => 'Plugins\\'.$directory,
            'slug' => strtolower($directory).'-autoload',
            'version' => '1.0.0',
            'installed_at' => now(),
            'enabled_at' => $enabled ? now() : null,
        ]);
    }

    private function deleteTree(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $child = $path.'/'.$entry;
            is_dir($child) && ! is_link($child) ? $this->deleteTree($child) : @unlink($child);
        }

        @rmdir($path);
    }
}
