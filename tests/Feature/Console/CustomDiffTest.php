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

namespace Tests\Feature\Console;

use App\Services\Custom\CustomOverrideInspector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * dls:custom:diff reports each view override under custom/ against the
 * baseline recorded for its upstream (dixlase-core#482).
 *
 * The custom, plugins and themes roots all point at a throwaway tree under
 * storage/framework/testing/, so nothing reads or writes the real custom/,
 * plugins/ or themes/.
 */
class CustomDiffTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = 'storage/framework/testing/custom-diff-'.uniqid();
        config([
            'custom.custom_files_dir' => $this->root.'/custom',
            'plugins.plugins_directory' => $this->root.'/plugins',
            'themes.theme_directory' => $this->root.'/themes',
            'themes.default_theme' => 'AcmeDefault',
        ]);
        File::ensureDirectoryExists(base_path($this->root.'/custom'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path($this->root));

        parent::tearDown();
    }

    public function test_an_empty_custom_directory_has_nothing_to_report(): void
    {
        $this->place('custom/README.md', '# readme');

        $this->artisan('dls:custom:diff')
            ->expectsOutputToContain('No overrides under')
            ->assertExitCode(0);
    }

    public function test_a_plugin_view_override_goes_from_untracked_to_current_to_upstream_changed(): void
    {
        $this->place('plugins/AcmeExample/resources/views/front/show.blade.php', '<div>upstream v1</div>');
        $this->place('custom/plugins/AcmeExample/resources/views/front/show.blade.php', '<div>site</div>');

        $this->assertStatus(CustomOverrideInspector::UNTRACKED, 'plugins/AcmeExample/resources/views/front/show.blade.php');
        $this->assertSame(0, Artisan::call('dls:custom:diff'), 'An override without a baseline is not a failure.');
        $this->assertStringContainsString('dls:custom:diff --record', Artisan::output());

        $this->assertSame(0, Artisan::call('dls:custom:diff', ['--record' => true]));
        $this->assertStatus(CustomOverrideInspector::CURRENT, 'plugins/AcmeExample/resources/views/front/show.blade.php');
        $this->assertSame(0, Artisan::call('dls:custom:diff'));

        // Upstream gains a control the override does not have.
        $this->place('plugins/AcmeExample/resources/views/front/show.blade.php', '<div>upstream v2 <button>new</button></div>');

        $this->assertStatus(CustomOverrideInspector::UPSTREAM_CHANGED, 'plugins/AcmeExample/resources/views/front/show.blade.php');
        $this->assertSame(1, Artisan::call('dls:custom:diff'));
        $output = Artisan::output();
        $this->assertStringContainsString('upstream changed', $output);
        $this->assertStringContainsString("diff -u {$this->root}/plugins/AcmeExample/resources/views/front/show.blade.php", $output);
    }

    public function test_a_missing_upstream_fails_and_keeps_its_baseline_on_record(): void
    {
        $this->place('plugins/AcmeExample/resources/views/gone.blade.php', 'upstream');
        $this->place('custom/plugins/AcmeExample/resources/views/gone.blade.php', 'site');
        Artisan::call('dls:custom:diff', ['--record' => true]);

        File::delete(base_path($this->root.'/plugins/AcmeExample/resources/views/gone.blade.php'));

        $this->assertSame(1, Artisan::call('dls:custom:diff'));
        $this->assertStringContainsString('no longer exists', Artisan::output());

        Artisan::call('dls:custom:diff', ['--record' => true]);
        $manifest = json_decode(File::get(base_path($this->root.'/custom/'.CustomOverrideInspector::MANIFEST)), true);
        $this->assertArrayHasKey('plugins/AcmeExample/resources/views/gone.blade.php', $manifest['files']);
    }

    public function test_a_theme_view_override_falls_back_to_the_default_theme_like_the_namespace(): void
    {
        $themes = $this->root.'/themes';
        $this->place('themes/AcmeDefault/resources/views/partials/footer.blade.php', 'default footer');
        $this->place("custom/{$themes}/AcmeChild/resources/views/partials/footer.blade.php", 'site footer');
        $this->place('themes/AcmeChild/resources/views/home.blade.php', 'child home');
        $this->place("custom/{$themes}/AcmeChild/resources/views/home.blade.php", 'site home');

        $inspector = app(CustomOverrideInspector::class);

        $this->assertSame("{$themes}/AcmeDefault/resources/views/partials/footer.blade.php", $inspector->upstreamFor("{$themes}/AcmeChild/resources/views/partials/footer.blade.php"));
        $this->assertSame("{$themes}/AcmeChild/resources/views/home.blade.php", $inspector->upstreamFor("{$themes}/AcmeChild/resources/views/home.blade.php"));
    }

    public function test_core_view_namespaces_map_and_other_files_are_not_compared(): void
    {
        $inspector = app(CustomOverrideInspector::class);

        $this->assertSame('resources/views/admin/settings/index.blade.php', $inspector->upstreamFor('resources/views/admin/settings/index.blade.php'));
        $this->assertSame('resources/views/components/form-text.blade.php', $inspector->upstreamFor('resources/views/components/form-text.blade.php'));
        $this->assertSame('resources/views/layouts/admin.blade.php', $inspector->upstreamFor('resources/views/layouts/admin.blade.php'));
        // Not under a namespace that takes a custom prepend.
        $this->assertNull($inspector->upstreamFor('resources/views/welcome.blade.php'));
        // Merged over upstream rather than replacing it.
        $this->assertNull($inspector->upstreamFor('plugins/AcmeExample/config/settings.php'));
        $this->assertNull($inspector->upstreamFor('app/Http/Controllers/FooController.php'));

        $this->place('custom/plugins/AcmeExample/config/settings.php', '<?php return [];');
        $this->place('custom/.gitkeep', '');

        $results = $inspector->inspect();
        $this->assertCount(1, $results);
        $this->assertSame(CustomOverrideInspector::NOT_COMPARED, $results[0]['status']);
        $this->assertSame(0, Artisan::call('dls:custom:diff'));
    }

    public function test_an_override_identical_to_upstream_is_flagged_and_json_lists_it(): void
    {
        $this->place('plugins/AcmeExample/resources/views/same.blade.php', 'same');
        $this->place('custom/plugins/AcmeExample/resources/views/same.blade.php', 'same');

        $this->assertSame(0, Artisan::call('dls:custom:diff'));
        $this->assertStringContainsString('(identical)', Artisan::output());

        Artisan::call('dls:custom:diff', ['--json' => true]);
        $json = json_decode(Artisan::output(), true);
        $this->assertSame([[
            'path' => 'plugins/AcmeExample/resources/views/same.blade.php',
            'upstream' => "{$this->root}/plugins/AcmeExample/resources/views/same.blade.php",
            'status' => CustomOverrideInspector::UNTRACKED,
            'identical' => true,
        ]], $json);
    }

    private function place(string $relative, string $contents): void
    {
        $path = base_path($this->root.'/'.$relative);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    private function assertStatus(string $expected, string $override): void
    {
        $result = collect(app(CustomOverrideInspector::class)->inspect())->firstWhere('path', $override);

        $this->assertNotNull($result, "{$override} was not listed");
        $this->assertSame($expected, $result['status']);
    }
}
