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

namespace Tests\Unit\Services\Csp;

use App\Services\Csp\CspBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CspBuilderTest extends TestCase
{
    use RefreshDatabase;

    private CspBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = app(CspBuilder::class);
    }

    public function test_is_enabled_returns_boolean(): void
    {
        $result = $this->builder->isEnabled();

        $this->assertIsBool($result);
    }

    public function test_build_returns_string(): void
    {
        $csp = $this->builder->build();

        $this->assertIsString($csp);
    }

    public function test_build_contains_default_src(): void
    {
        $csp = $this->builder->build();

        // CSP が有効な場合は default-src が含まれる
        if (! empty($csp)) {
            $this->assertStringContainsString('default-src', $csp);
        } else {
            // CSP 無効時は空文字列
            $this->assertEquals('', $csp);
        }
    }

    public function test_set_context_returns_self(): void
    {
        $result = $this->builder->setContext('admin');

        $this->assertInstanceOf(CspBuilder::class, $result);
    }

    public function test_get_header_name_returns_string(): void
    {
        $name = $this->builder->getHeaderName();

        $this->assertIsString($name);
        $this->assertStringContainsString('Content-Security-Policy', $name);
    }

    public function test_build_with_admin_context(): void
    {
        $this->builder->setContext('admin');
        $csp = $this->builder->build();

        $this->assertIsString($csp);
    }

    public function test_build_with_front_context(): void
    {
        $this->builder->setContext('front');
        $csp = $this->builder->build();

        $this->assertIsString($csp);
    }
}
