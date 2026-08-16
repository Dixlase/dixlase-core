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

namespace Tests\Unit\Presenters\Admin;

use App\Presenters\Admin\ExtensionCardPresenter;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins the thumbnail-URL contract in ExtensionCardPresenter.
 *
 * The presenter checks for `thumbnail.{webp,png,jpg,jpeg}` at TWO
 * source locations inside an extension's directory:
 *
 *   1. `{type}/{directory}/thumbnail.<ext>` (recommended, ext root)
 *   2. `{type}/{directory}/resources/assets/thumbnail.<ext>` (legacy)
 *
 * When any format is present in either location the URL to the admin
 * thumbnail endpoint is returned; otherwise the bundled default SVG is
 * returned so the card view still renders a generic placeholder.
 *
 * The endpoint URL is intentionally format-agnostic — extension picking
 * (webp > png > jpg > jpeg) AND source-location picking (root wins over
 * legacy when both exist) are controller-side concerns pinned by
 * `AdminExtensionThumbnailControllerFormatPriorityTest`.
 */
class ExtensionCardPresenterThumbnailTest extends TestCase
{
    /**
     * A directory name that no real plugin will ever shadow. We create
     * it under plugins/ for each test and remove it in tearDown so the
     * helper can probe via its production `base_path()` lookup.
     */
    private const PLUGIN_FIXTURE = '__test_thumbnail_resolver__';

    /**
     * Themes use the same code path; isolate the fixture by name too.
     */
    private const THEME_FIXTURE = '__test_thumbnail_resolver__';

    protected function setUp(): void
    {
        parent::setUp();

        // The presenter emits route() URLs; register a stub for the
        // route it targets so the test does not need the full admin
        // route file (and its middleware / controller dependency chain)
        // loaded just to verify the URL contract.
        Route::get(
            'stub/extension-thumbnail/{type}/{directory}',
            fn () => null,
        )->name('admin.settings.extension-thumbnail');
    }

    protected function tearDown(): void
    {
        $this->removeFixtures();

        parent::tearDown();
    }

    public function test_returns_route_url_when_webp_thumbnail_exists(): void
    {
        $this->writePluginThumbnail('webp');

        $url = $this->callResolver('plugins', self::PLUGIN_FIXTURE, 'assets/images/plugin-default.svg');

        $this->assertSame(
            route('admin.settings.extension-thumbnail', ['type' => 'plugins', 'directory' => self::PLUGIN_FIXTURE]),
            $url,
            'when any supported format exists the presenter must return the admin thumbnail-endpoint URL',
        );
    }

    public function test_returns_route_url_for_every_supported_format(): void
    {
        $expected = route('admin.settings.extension-thumbnail', [
            'type' => 'plugins',
            'directory' => self::PLUGIN_FIXTURE,
        ]);

        foreach (['webp', 'png', 'jpg', 'jpeg'] as $format) {
            $this->removeFixtures();
            $this->writePluginThumbnail($format);

            $this->assertSame(
                $expected,
                $this->callResolver('plugins', self::PLUGIN_FIXTURE, 'assets/images/plugin-default.svg'),
                "presenter should emit the endpoint URL when thumbnail.{$format} is the only present format",
            );
        }
    }

    public function test_falls_back_to_default_svg_when_no_format_present(): void
    {
        $url = $this->callResolver(
            'plugins',
            'this-extension-does-not-exist-'.uniqid(),
            'assets/images/plugin-default.svg',
        );

        $this->assertStringEndsWith('assets/images/plugin-default.svg', $url);
    }

    public function test_recognises_extension_root_location(): void
    {
        // The recommended thumbnail location — sits alongside plugin.json
        // / theme.json at the extension root, safe from vite / other
        // build tools that empty resources/assets/ before writing new
        // build output. Must be recognised by the presenter as a
        // "thumbnail available" signal in its own right, independent
        // of anything in resources/assets/.
        $rootFile = base_path('plugins/'.self::PLUGIN_FIXTURE.'/thumbnail.png');
        $this->ensureDirectory(dirname($rootFile));
        file_put_contents($rootFile, 'png-bytes');

        $url = $this->callResolver('plugins', self::PLUGIN_FIXTURE, 'assets/images/plugin-default.svg');

        $this->assertSame(
            route('admin.settings.extension-thumbnail', ['type' => 'plugins', 'directory' => self::PLUGIN_FIXTURE]),
            $url,
            'thumbnail.png at the extension root must be recognised as available (recommended location)',
        );
    }

    public function test_root_location_takes_precedence_over_legacy_within_same_format(): void
    {
        // When BOTH the recommended (extension root) and legacy
        // (resources/assets/) locations carry a thumbnail of the same
        // format, the presenter returns the same route URL either way
        // (the URL is format-agnostic and location-agnostic), but the
        // controller — which reads the actual bytes — picks the root
        // location. Presenter-side we only assert "URL was emitted".
        // Precedence at the byte level lives in the controller test.
        $this->writePluginThumbnail('png');
        $rootFile = base_path('plugins/'.self::PLUGIN_FIXTURE.'/thumbnail.png');
        file_put_contents($rootFile, 'png-bytes-root');

        $url = $this->callResolver('plugins', self::PLUGIN_FIXTURE, 'assets/images/plugin-default.svg');

        $this->assertSame(
            route('admin.settings.extension-thumbnail', ['type' => 'plugins', 'directory' => self::PLUGIN_FIXTURE]),
            $url,
            'either location present is enough for the presenter to emit the endpoint URL',
        );
    }

    public function test_recognises_every_supported_format_at_extension_root(): void
    {
        $expected = route('admin.settings.extension-thumbnail', [
            'type' => 'plugins',
            'directory' => self::PLUGIN_FIXTURE,
        ]);

        foreach (['webp', 'png', 'jpg', 'jpeg'] as $format) {
            $this->removeFixtures();
            $rootFile = base_path('plugins/'.self::PLUGIN_FIXTURE.'/thumbnail.'.$format);
            $this->ensureDirectory(dirname($rootFile));
            file_put_contents($rootFile, "fixture:{$format}");

            $this->assertSame(
                $expected,
                $this->callResolver('plugins', self::PLUGIN_FIXTURE, 'assets/images/plugin-default.svg'),
                "extension-root thumbnail.{$format} must be recognised on its own",
            );
        }
    }

    public function test_resolves_themes_with_their_own_default_svg(): void
    {
        $this->writeThemeThumbnail('webp');

        $url = $this->callResolver('themes', self::THEME_FIXTURE, 'assets/images/theme-default.svg');

        $this->assertSame(
            route('admin.settings.extension-thumbnail', ['type' => 'themes', 'directory' => self::THEME_FIXTURE]),
            $url,
        );
    }

    public function test_theme_falls_back_to_theme_default_svg(): void
    {
        $url = $this->callResolver(
            'themes',
            'this-theme-does-not-exist-'.uniqid(),
            'assets/images/theme-default.svg',
        );

        $this->assertStringEndsWith('assets/images/theme-default.svg', $url);
    }

    private function callResolver(string $type, string $directory, string $defaultSvg): string
    {
        $method = new ReflectionMethod(ExtensionCardPresenter::class, 'resolveExtensionThumbnailUrl');
        $method->setAccessible(true);

        return (string) $method->invoke(null, $type, $directory, $defaultSvg);
    }

    private function writePluginThumbnail(string $extension): void
    {
        $this->writeThumbnail('plugins', self::PLUGIN_FIXTURE, $extension);
    }

    private function writeThemeThumbnail(string $extension): void
    {
        $this->writeThumbnail('themes', self::THEME_FIXTURE, $extension);
    }

    private function writeThumbnail(string $type, string $directory, string $extension): void
    {
        $dir = base_path("{$type}/{$directory}/resources/assets");
        $this->ensureDirectory($dir);
        file_put_contents("{$dir}/thumbnail.{$extension}", "fixture:{$extension}");
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    private function removeFixtures(): void
    {
        foreach (['plugins', 'themes'] as $type) {
            $root = base_path("{$type}/".self::PLUGIN_FIXTURE);
            if (is_dir($root)) {
                $this->removeDirectory($root);
            }
        }
    }

    private function removeDirectory(string $path): void
    {
        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path.DIRECTORY_SEPARATOR.$item;
            if (is_dir($full) && ! is_link($full)) {
                $this->removeDirectory($full);
            } else {
                @unlink($full);
            }
        }

        @rmdir($path);
    }
}
