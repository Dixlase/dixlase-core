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

namespace Tests\Unit\Helpers;

use App\Helpers\ThemeHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Route registration asks for the active theme on every request, including
 * the ones served while the install wizard is still running. Back then .env
 * still pointed at the shipped defaults, so each of those lookups failed and
 * logged an ERROR — 19 of them in a real install, which reads like a failed
 * installation to whoever opens the log next.
 */
class ThemeHelperInstallGuardTest extends TestCase
{
    use RefreshDatabase;

    private bool $hadServerFlag = false;

    /** @var mixed */
    private $originalServerFlag = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hadServerFlag = array_key_exists('INSTALLED', $_SERVER);
        $this->originalServerFlag = $_SERVER['INSTALLED'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->hadServerFlag) {
            $_SERVER['INSTALLED'] = $this->originalServerFlag;
        } else {
            unset($_SERVER['INSTALLED']);
        }

        parent::tearDown();
    }

    public function test_no_theme_is_looked_up_before_the_install_completes(): void
    {
        $_SERVER['INSTALLED'] = 'false';
        Log::shouldReceive('error')->never();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->assertNull(ThemeHelper::getActiveTheme());
        $this->assertSame(0, $queries, 'The database must not be touched before the install completes.');
    }

    public function test_the_theme_path_is_null_before_the_install_completes(): void
    {
        $_SERVER['INSTALLED'] = 'false';
        Log::shouldReceive('error')->never();

        $this->assertNull(ThemeHelper::getActiveThemePath());
    }

    public function test_an_installed_site_queries_for_its_theme(): void
    {
        $_SERVER['INSTALLED'] = 'true';

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        // No theme rows in a fresh test database: null, but the lookup ran.
        $this->assertNull(ThemeHelper::getActiveTheme());
        $this->assertGreaterThan(0, $queries);
    }
}
