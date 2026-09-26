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

namespace Tests\Feature\Install;

use App\Http\Middleware\CheckInstallationReady;
use App\Services\Install\InstallFinalizer;
use App\Support\Install\FinalizePendingMarker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /install/finalize writes INSTALLED=true. The installation middleware let
 * it through unconditionally ("always allow finalize"), so any visitor who
 * reached a site before its owner finished the wizard could mark it installed
 * -- with an empty database, if the migrations had not run -- leaving every
 * page failing and /install closed behind it.
 *
 * Finalize is only valid once the completion screen has been shown, which
 * sets the finalize-pending marker. The finalizer is mocked so this test can
 * never write to a real .env.
 */
class InstallFinalizeRequiresCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FinalizePendingMarker::clear();

        $this->mock(InstallFinalizer::class, function ($mock): void {
            $mock->shouldNotReceive('finalize');
        });
    }

    protected function tearDown(): void
    {
        FinalizePendingMarker::clear();

        parent::tearDown();
    }

    public function test_the_middleware_refuses_finalize_before_the_completion_screen(): void
    {
        $this->post('/install/finalize')->assertRedirect(route('install.index'));
    }

    public function test_the_controller_refuses_finalize_without_the_pending_marker(): void
    {
        $this->withoutMiddleware(CheckInstallationReady::class);

        $this->post('/install/finalize')->assertRedirect(route('install.index'));
    }
}
