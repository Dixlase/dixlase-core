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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * finalize() redirects to the `redirect_to` the completion screen posts.
 * The value comes from the client, so it is followed only while it stays on
 * the request's host; anything else goes to the front page, the same rule
 * CheckInstallationReady applies to a repeated finalize.
 *
 * The middleware is skipped so the controller is what answers, and the
 * finalizer is mocked so this test can never write to a real .env.
 */
class InstallFinalizeRedirectTargetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckInstallationReady::class);

        $this->mock(InstallFinalizer::class, function ($mock): void {
            $mock->shouldReceive('finalize')->once()->andReturn(null);
        });

        FinalizePendingMarker::mark();
    }

    protected function tearDown(): void
    {
        FinalizePendingMarker::clear();

        parent::tearDown();
    }

    public static function sameHostTargets(): array
    {
        return [
            'admin login URL' => ['http://localhost/admin-custom/login'],
            'site URL' => ['http://localhost'],
            'path only' => ['/admin-custom/login'],
        ];
    }

    #[DataProvider('sameHostTargets')]
    public function test_finalize_follows_a_same_host_redirect_to(string $target): void
    {
        $this->post('/install/finalize', ['redirect_to' => $target])->assertRedirect($target);
    }

    public static function refusedTargets(): array
    {
        return [
            'another host' => ['https://example.com/login'],
            'another host behind userinfo' => ['https://localhost@example.com/login'],
            'protocol-relative' => ['//example.com/login'],
            'backslash' => ['/\\example.com/login'],
            'javascript scheme' => ['javascript:alert(1)'],
        ];
    }

    #[DataProvider('refusedTargets')]
    public function test_finalize_sends_any_other_target_to_the_front_page(string $target): void
    {
        $this->post('/install/finalize', ['redirect_to' => $target])->assertRedirect('/');
    }

    public function test_finalize_without_redirect_to_still_answers_with_json(): void
    {
        $this->post('/install/finalize')->assertOk()->assertJson(['success' => true]);
    }
}
