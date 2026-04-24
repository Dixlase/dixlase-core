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

namespace Tests\Unit\Services;

use App\Services\ShortcodeManager;
use Tests\TestCase;

class ShortcodeManagerTest extends TestCase
{
    private ShortcodeManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ShortcodeManager(app());
    }

    public function test_parse_returns_string(): void
    {
        $result = $this->manager->parse('Hello World');

        $this->assertIsString($result);
    }

    public function test_parse_without_shortcodes_returns_original(): void
    {
        $content = '<p>No shortcodes here</p>';

        $result = $this->manager->parse($content);

        $this->assertEquals($content, $result);
    }

    public function test_parse_empty_string(): void
    {
        $result = $this->manager->parse('');

        $this->assertEquals('', $result);
    }

    public function test_add_registers_shortcode(): void
    {
        $this->manager->add('test_sc', ShortcodeManagerTestShortcode::class);

        $result = $this->manager->parse('[test_sc]');
        $this->assertIsString($result);
    }
}

class ShortcodeManagerTestShortcode
{
    public function render(array $attributes = [], ?string $content = null): string
    {
        return '<div>Test Output</div>';
    }
}
