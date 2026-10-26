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

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Env;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The suite knows which database it is running against.
 *
 * Both assertions below would have failed on the day a step-level
 * `DB_CONNECTION: mysql` was added to the workflow: the suite was pinned to
 * sqlite by phpunit.xml, the guard in TestCase reported sqlite, and the
 * application connected to MySQL for months without anything saying so
 * (#541). docs/development/test-database.md records which driver runs where.
 */
class TestSuiteDatabaseDriverTest extends TestCase
{
    /**
     * A run connects to sqlite unless it deliberately opted out.
     *
     * CI runs the suite against a disposable MySQL service container on
     * purpose and sets DLS_TESTS_ALLOW_NON_SQLITE=1 to say so, which is
     * what makes the opt-in a decision rather than an accident.
     */
    public function test_a_run_that_has_not_opted_in_connects_to_sqlite(): void
    {
        if (Env::get('DLS_TESTS_ALLOW_NON_SQLITE') === '1') {
            $this->markTestSkipped('This run opted into a non-sqlite driver (DLS_TESTS_ALLOW_NON_SQLITE=1).');
        }

        $connection = (string) config('database.default');

        $this->assertSame(
            'sqlite',
            config("database.connections.{$connection}.driver", $connection),
            'A run that has not set DLS_TESTS_ALLOW_NON_SQLITE must be on sqlite: RefreshDatabase '
            .'rebuilds the active connection from scratch in every setUp().'
        );
    }

    /**
     * The guard sees the value the application will use.
     *
     * phpunit writes its `<env>` values with putenv() and into $_ENV, never
     * into $_SERVER, while Laravel's Env repository reads $_SERVER first. A
     * guard that reads getenv() first therefore reports phpunit's value
     * while the application uses the process environment's.
     */
    public function test_the_guard_reads_the_environment_the_way_the_application_does(): void
    {
        $key = 'DLS_TEST_DRIVER_PROBE';
        $hadServer = array_key_exists($key, $_SERVER);
        $previous = $hadServer ? $_SERVER[$key] : null;

        putenv($key.'=from-putenv');
        $_SERVER[$key] = 'from-server';

        try {
            $this->assertSame('from-server', Env::get($key), 'env() reads $_SERVER before putenv().');

            $seen = (new ReflectionMethod(TestCase::class, 'readEnv'))->invoke(null, $key);

            $this->assertSame('from-server', $seen, 'The guard must read the environment in the same order.');
        } finally {
            if ($hadServer) {
                $_SERVER[$key] = $previous;
            } else {
                unset($_SERVER[$key]);
            }

            putenv($key);
        }
    }
}
