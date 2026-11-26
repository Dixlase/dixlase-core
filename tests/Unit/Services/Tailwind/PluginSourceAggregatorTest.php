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

namespace Tests\Unit\Services\Tailwind;

use App\Models\Plugin;
use App\Services\Tailwind\PluginSourceAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The aggregator reads the enabled plugins on a live site and every plugin
 * on disk for a build, and falls back to the disk when there is no
 * database to ask (#781).
 */
class PluginSourceAggregatorTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    private string $pluginsRoot;

    private string $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = 'storage/framework/testing/tailwind-sources-'.uniqid();
        $this->pluginsRoot = base_path($this->root.'/plugins');
        $this->output = base_path($this->root.'/sources.css');
        File::ensureDirectoryExists($this->pluginsRoot);

        // Point every reader of the plugins root at the throwaway tree.
        config(['plugins.plugins_directory' => $this->pluginsRoot]);
        $this->app->instance(PluginSourceAggregator::class, new PluginSourceAggregator($this->output));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path($this->root));
        parent::tearDown();
    }

    public function test_reads_every_plugin_on_disk_with_views_and_declared_paths(): void
    {
        $this->makePlugin('AlphaPlugin', 'alpha-plugin', views: true, declared: ['storage/app/private/alpha/', 'storage\\app\\alpha-two']);
        $this->makePlugin('BetaPlugin', 'beta-plugin', views: false, declared: ['storage/app/beta']);
        $this->makePlugin('GammaPlugin', 'gamma-plugin', views: false);
        $this->makePlugin('AlphaPlugin.bak', 'alpha-plugin', views: true);
        File::ensureDirectoryExists($this->pluginsRoot.'/NoManifest/resources/views');

        $result = $this->aggregator()->regenerate(fromDisk: true);

        $this->assertSame(PluginSourceAggregator::MODE_DISK, $result['mode']);
        $this->assertSame(2, $result['plugin_count']);
        $this->assertSame(4, $result['source_count']);
        $this->assertSame([
            '/* alpha-plugin */',
            '@source "../../../../'.$this->root.'/plugins/AlphaPlugin/resources/views";',
            '@source "../../../../storage/app/private/alpha";',
            '@source "../../../../storage/app/alpha-two";',
            '/* beta-plugin */',
            '@source "../../../../storage/app/beta";',
        ], $this->sourceLines());
        $this->assertStringContainsString('every plugin on disk', File::get($this->output));
    }

    public function test_reads_only_the_enabled_plugins_by_default(): void
    {
        $this->makePlugin('AlphaPlugin', 'alpha-plugin', views: true);
        $this->makePlugin('BetaPlugin', 'beta-plugin', views: true);
        $this->record('AlphaPlugin', 'alpha-plugin', enabled: true);
        $this->record('BetaPlugin', 'beta-plugin', enabled: false);

        $result = $this->aggregator()->regenerate();

        $this->assertSame(PluginSourceAggregator::MODE_ENABLED, $result['mode']);
        $this->assertSame([
            '/* alpha-plugin */',
            '@source "../../../../'.$this->root.'/plugins/AlphaPlugin/resources/views";',
        ], $this->sourceLines());
    }

    public function test_falls_back_to_the_disk_when_the_database_cannot_be_queried(): void
    {
        $this->makePlugin('AlphaPlugin', 'alpha-plugin', views: true);
        $default = config('database.default');
        config([
            'database.connections.unreachable' => ['driver' => 'sqlite', 'database' => base_path($this->root.'/missing/db.sqlite')],
            'database.default' => 'unreachable',
        ]);

        try {
            $result = $this->aggregator()->regenerate();
        } finally {
            config(['database.default' => $default]);
        }

        $this->assertSame(PluginSourceAggregator::MODE_DISK, $result['mode']);
        $this->assertSame(1, $result['plugin_count']);
    }

    public function test_drops_paths_that_climb_out_or_would_break_the_css(): void
    {
        $this->makePlugin('AlphaPlugin', 'alpha-plugin', views: false, declared: [
            'storage/app/ok',
            '../outside',
            'storage/../../outside',
            'storage/app/"quoted',
            "storage/app/new\nline",
            'storage/app/*/comment',
            '',
            42,
        ]);

        $this->aggregator()->regenerate(fromDisk: true);

        $this->assertSame([
            '/* alpha-plugin */',
            '@source "../../../../storage/app/ok";',
        ], $this->sourceLines());
    }

    public function test_writes_a_valid_file_when_nothing_contributes(): void
    {
        $result = $this->aggregator()->regenerate(fromDisk: true);

        $this->assertSame(0, $result['plugin_count']);
        $this->assertSame([], $this->sourceLines());
        $this->assertStringContainsString('No plugin on disk contributes', File::get($this->output));
    }

    public function test_the_command_reads_the_disk_with_the_flag(): void
    {
        $this->makePlugin('AlphaPlugin', 'alpha-plugin', views: true);

        $this->artisan('dls:tailwind:regenerate-plugin-sources', ['--from-disk' => true])
            ->expectsOutputToContain('from 1 plugin(s) on disk')
            ->assertSuccessful();

        $this->artisan('dls:tailwind:regenerate-plugin-sources')
            ->expectsOutputToContain('from 0 enabled plugin(s)')
            ->assertSuccessful();
    }

    private function aggregator(): PluginSourceAggregator
    {
        return $this->app->make(PluginSourceAggregator::class);
    }

    /**
     * @param  array<mixed>  $declared
     */
    private function makePlugin(string $directory, string $slug, bool $views, array $declared = []): void
    {
        $path = $this->pluginsRoot.'/'.$directory;
        File::ensureDirectoryExists($path);

        if ($views) {
            File::ensureDirectoryExists($path.'/resources/views');
        }

        $manifest = ['slug' => $slug];
        if ($declared !== []) {
            $manifest['declares'] = ['tailwind_content' => $declared];
        }

        File::put($path.'/plugin.json', (string) json_encode($manifest));
    }

    private function record(string $directory, string $slug, bool $enabled): void
    {
        Plugin::create([
            'name' => $directory,
            'package_name' => 'example/'.$slug,
            'directory' => $directory,
            'namespace' => 'Plugins\\'.$directory,
            'slug' => $slug,
            'version' => '1.0.0',
            'author' => 'Example',
            'installed_at' => now(),
            'enabled_at' => $enabled ? now() : null,
        ]);
    }

    /**
     * The comment and @source lines of the output, without the header.
     *
     * @return list<string>
     */
    private function sourceLines(): array
    {
        $lines = explode("\n", File::get($this->output));

        return array_values(array_filter(
            $lines,
            static fn (string $line): bool => str_starts_with($line, '@source') || preg_match('/^\/\* [a-z0-9_-]+ \*\/$/', $line) === 1,
        ));
    }
}
