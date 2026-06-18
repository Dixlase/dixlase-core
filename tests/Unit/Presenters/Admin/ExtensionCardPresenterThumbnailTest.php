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
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins the multi-format thumbnail probe in ExtensionCardPresenter.
 *
 * The presenter looks for `thumbnail.{webp,png,jpg,jpeg}` inside an
 * extension's `resources/assets/` directory and returns the asset URL
 * with the matching extension. When nothing is found it must fall back
 * to the bundled default SVG that the card view renders as a generic
 * plugin/theme placeholder.
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

    protected function tearDown(): void
    {
        $this->removeFixtures();

        parent::tearDown();
    }

    public function test_returns_webp_url_when_webp_thumbnail_exists(): void
    {
        $this->writePluginThumbnail('webp');

        $url = $this->callResolver('plugins', self::PLUGIN_FIXTURE, 'assets/images/plugin-default.svg');

        $this->assertStringEndsWith(
            'assets/plugins/'.self::PLUGIN_FIXTURE.'/thumbnail.webp',
            $url,
            'webp should win when it is the only present format',
        );
    }

    public function test_prefers_webp_over_png_when_both_present(): void
    {
        $this->writePluginThumbnail('png');
        $this->writePluginThumbnail('webp');

        $url = $this->callResolver('plugins', self::PLUGIN_FIXTURE, 'assets/images/plugin-default.svg');

        $this->assertStringEndsWith('thumbnail.webp', $url);
    }

    public function test_picks_png_when_webp_absent_but_png_present(): void
    {
        $this->writePluginThumbnail('png');

        $url = $this->callResolver('plugins', self::PLUGIN_FIXTURE, 'assets/images/plugin-default.svg');

        $this->assertStringEndsWith('thumbnail.png', $url);
    }

    public function test_picks_jpg_over_jpeg_when_both_present(): void
    {
        $this->writePluginThumbnail('jpeg');
        $this->writePluginThumbnail('jpg');

        $url = $this->callResolver('plugins', self::PLUGIN_FIXTURE, 'assets/images/plugin-default.svg');

        $this->assertStringEndsWith('thumbnail.jpg', $url);
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

    public function test_probes_resources_assets_not_extension_root(): void
    {
        $rootFile = base_path('plugins/'.self::PLUGIN_FIXTURE.'/thumbnail.png');
        $this->ensureDirectory(dirname($rootFile));
        file_put_contents($rootFile, 'png-bytes');

        $url = $this->callResolver('plugins', self::PLUGIN_FIXTURE, 'assets/images/plugin-default.svg');

        $this->assertStringEndsWith(
            'assets/images/plugin-default.svg',
            $url,
            'a thumbnail.png placed at the extension root must not be matched; only resources/assets/ counts',
        );
    }

    public function test_resolves_themes_with_their_own_default_svg(): void
    {
        $this->writeThemeThumbnail('webp');

        $url = $this->callResolver('themes', self::THEME_FIXTURE, 'assets/images/theme-default.svg');

        $this->assertStringEndsWith(
            'assets/themes/'.self::THEME_FIXTURE.'/thumbnail.webp',
            $url,
        );
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
