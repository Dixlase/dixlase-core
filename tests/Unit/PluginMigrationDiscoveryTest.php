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

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\TestCase as DixlaseTestCase;

/**
 * Verifies how the base test case decides which plugin's migrations to
 * register when a plugin's PHPUnit suite runs against core.
 *
 * @see \Tests\TestCase::pluginUnderTest()
 */
class PluginMigrationDiscoveryTest extends TestCase
{
    public function test_extracts_plugin_directory_from_a_plugin_test_class(): void
    {
        $this->assertSame(
            'DixlaseInquiry',
            DixlaseTestCase::pluginUnderTest(
                'Plugins\\DixlaseInquiry\\Tests\\Unit\\TranslatableSettingsAggregateTest'
            ),
        );
    }

    public function test_returns_null_for_a_core_test_class(): void
    {
        $this->assertNull(
            DixlaseTestCase::pluginUnderTest('Tests\\Unit\\PluginMigrationDiscoveryTest'),
        );
    }

    public function test_returns_null_for_a_class_outside_any_known_namespace(): void
    {
        $this->assertNull(DixlaseTestCase::pluginUnderTest('SomeVendor\\Package\\Thing'));
        $this->assertNull(DixlaseTestCase::pluginUnderTest('Plugins'));
    }
}
