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

namespace Tests\Feature\Admin\SafeMode;

use App\View\Components\Security\SafeModeBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SafeModeBanner コンポーネントのテスト
 *
 * コンポーネントクラスの shouldRender() と activeModes を直接テストする。
 */
class SafeModeBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    public function test_banner_renders_when_csp_mode_active(): void
    {
        session(['safe_mode_csp' => true]);

        $component = app()->make(SafeModeBanner::class);

        $this->assertTrue($component->shouldRender());
        $this->assertCount(1, $component->activeModes);
        $this->assertEquals('csp', $component->activeModes[0]['value']);
    }

    public function test_banner_renders_when_plugins_mode_active(): void
    {
        session(['safe_mode_plugins' => true]);

        $component = app()->make(SafeModeBanner::class);

        $this->assertTrue($component->shouldRender());
        $this->assertCount(1, $component->activeModes);
        $this->assertEquals('plugins', $component->activeModes[0]['value']);
    }

    public function test_banner_renders_when_theme_mode_active(): void
    {
        session(['safe_mode_theme' => true]);

        $component = app()->make(SafeModeBanner::class);

        $this->assertTrue($component->shouldRender());
        $this->assertCount(1, $component->activeModes);
        $this->assertEquals('theme', $component->activeModes[0]['value']);
    }

    public function test_no_banner_when_no_safe_mode_active(): void
    {
        $component = app()->make(SafeModeBanner::class);

        $this->assertFalse($component->shouldRender());
        $this->assertCount(0, $component->activeModes);
    }

    public function test_multiple_banners_when_multiple_modes_active(): void
    {
        session(['safe_mode_csp' => true, 'safe_mode_plugins' => true]);

        $component = app()->make(SafeModeBanner::class);

        $this->assertTrue($component->shouldRender());
        $this->assertCount(2, $component->activeModes);

        $values = array_column($component->activeModes, 'value');
        $this->assertContains('csp', $values);
        $this->assertContains('plugins', $values);
    }
}
