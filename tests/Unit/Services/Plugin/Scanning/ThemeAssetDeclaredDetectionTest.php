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

namespace Tests\Unit\Services\Plugin\Scanning;

use App\Services\Plugin\Scanning\PatternRegistry;
use App\Services\Plugin\Scanning\ThemeAssetDetectionPattern;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Core's theme asset loader reads sources from resources/src/<area>/, and
 * theme.json declares them in those terms ("front/js/app.js"). The permission
 * scanner only globbed a flat resources/src/{css,scss,js}/ layout plus the
 * gitignored build output resources/assets/, so a theme that followed the
 * loader had its correct assets.custom_css / custom_js declarations reported
 * as unused (-2 each). The fixture lives under storage/framework/testing.
 */
class ThemeAssetDeclaredDetectionTest extends TestCase
{
    private string $theme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->theme = storage_path('framework/testing/theme-assets-'.uniqid());
        File::ensureDirectoryExists($this->theme);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->theme);

        parent::tearDown();
    }

    private function writeFile(string $relative, string $content = ''): void
    {
        File::ensureDirectoryExists(dirname($this->theme.'/'.$relative));
        File::put($this->theme.'/'.$relative, $content);
    }

    private function declare(array $front): void
    {
        $this->writeFile('theme.json', json_encode(['assets' => ['common' => [], 'admin' => [], 'front' => $front]]));
    }

    /**
     * @return array<string, bool>
     */
    private function scan(): array
    {
        $registry = new PatternRegistry();
        $registry->register(new ThemeAssetDetectionPattern('custom_css'));
        $registry->register(new ThemeAssetDetectionPattern('custom_js'));

        return $registry->scan($this->theme, 'theme')['permissions'];
    }

    public function test_declared_area_layout_assets_are_detected(): void
    {
        $this->declare(['front/js/app.js', 'front/scss/style.scss']);
        $this->writeFile('resources/src/front/js/app.js', 'console.log(1);');
        $this->writeFile('resources/src/front/scss/style.scss', 'body{}');

        $permissions = $this->scan();

        $this->assertTrue($permissions['assets.custom_css']);
        $this->assertTrue($permissions['assets.custom_js']);
    }

    public function test_a_declared_asset_that_does_not_exist_is_not_counted(): void
    {
        $this->declare(['front/js/app.js']);

        $this->assertFalse($this->scan()['assets.custom_js']);
    }

    public function test_build_output_alone_is_not_evidence(): void
    {
        $this->declare([]);
        $this->writeFile('resources/assets/css/app.css', 'body{}');
        $this->writeFile('resources/assets/js/app.js', '1');

        $permissions = $this->scan();

        $this->assertFalse($permissions['assets.custom_css']);
        $this->assertFalse($permissions['assets.custom_js']);
    }

    public function test_the_scaffolded_flat_layout_is_still_detected(): void
    {
        $this->declare([]);
        $this->writeFile('resources/src/css/style.css', 'body{}');

        $this->assertTrue($this->scan()['assets.custom_css']);
    }

    public function test_a_declaration_cannot_reach_outside_resources_src(): void
    {
        $this->declare(['../../theme.json', '/etc/hosts.js']);

        $this->assertSame([], (new ThemeAssetDetectionPattern('custom_js'))->detectFiles($this->theme));
    }
}
