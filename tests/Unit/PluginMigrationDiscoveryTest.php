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

use Tests\TestCase;

/**
 * Verifies how the base test case decides which plugins' migrations to
 * apply when a plugin's PHPUnit suite runs against core.
 *
 * @see \Tests\TestCase::pluginsUnderTest()
 * @see \Tests\TestCase::requestedTestsuites()
 */
class PluginMigrationDiscoveryTest extends TestCase
{
    /**
     * The PHPUnit invocation arguments, restored after each test so that
     * argv manipulation does not leak into the rest of the run.
     *
     * @var array<int, string>
     */
    private array $originalArgv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalArgv = $_SERVER['argv'] ?? [];
    }

    protected function tearDown(): void
    {
        $_SERVER['argv'] = $this->originalArgv;

        parent::tearDown();
    }

    public function test_requested_testsuites_parses_the_comma_separated_form(): void
    {
        $_SERVER['argv'] = ['phpunit', '--testsuite=DixlaseLegal,DixlaseInquiry'];

        $this->assertSame(['DixlaseLegal', 'DixlaseInquiry'], static::requestedTestsuites());
    }

    public function test_requested_testsuites_parses_the_space_separated_form(): void
    {
        $_SERVER['argv'] = ['phpunit', '--testsuite', 'DixlaseSEO'];

        $this->assertSame(['DixlaseSEO'], static::requestedTestsuites());
    }

    public function test_requested_testsuites_is_null_without_the_option(): void
    {
        $_SERVER['argv'] = ['phpunit', '--filter=SomeTest'];

        $this->assertNull(static::requestedTestsuites());
    }

    public function test_plugins_under_test_drops_non_plugin_testsuites(): void
    {
        $_SERVER['argv'] = ['phpunit', '--testsuite=Unit,Feature,__not_a_plugin__'];

        $this->assertSame([], static::pluginsUnderTest());
    }

    public function test_plugins_under_test_returns_only_real_plugin_migration_directories(): void
    {
        // With no --testsuite the whole run is covered, so every plugin
        // that ships migrations is returned — and only those.
        $_SERVER['argv'] = ['phpunit'];

        $plugins = static::pluginsUnderTest();

        foreach ($plugins as $plugin) {
            $this->assertDirectoryExists(base_path("plugins/{$plugin}/database/migrations"));
        }
    }
}
