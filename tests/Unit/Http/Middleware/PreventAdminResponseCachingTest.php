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

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\PreventAdminResponseCaching;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Direct unit test — invokes the middleware without any HTTP kernel
 * so the behaviour is asserted in isolation from routing, auth, or
 * session state. Feature-level integration coverage (that admin
 * routes actually run this middleware in production) lives in
 * tests/Feature/Admin/PreventAdminResponseCachingFeatureTest.php.
 */
class PreventAdminResponseCachingTest extends TestCase
{
    private function pipe(Response $seed): Response
    {
        return (new PreventAdminResponseCaching)->handle(
            Request::create('/admin/dashboard'),
            fn () => $seed,
        );
    }

    public function test_it_forces_no_store_on_a_fresh_response(): void
    {
        $response = $this->pipe(new Response('body'));

        $this->assertSame(
            'no-store, no-cache, must-revalidate, private, max-age=0',
            $response->headers->get('Cache-Control'),
        );
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertSame('0', $response->headers->get('Expires'));
    }

    public function test_it_overrides_a_downstream_public_cache_control(): void
    {
        // A downstream controller that accidentally set `public,
        // max-age=3600` on an admin URL would otherwise let browsers
        // (and any intermediary proxy) cache authenticated HTML.
        // The middleware must clobber that decision — an admin URL
        // that legitimately wants to serve cacheable bytes should
        // be routed outside the admin group entirely.
        $seed = new Response('body');
        $seed->headers->set('Cache-Control', 'public, max-age=3600');

        $response = $this->pipe($seed);

        $this->assertSame(
            'no-store, no-cache, must-revalidate, private, max-age=0',
            $response->headers->get('Cache-Control'),
        );
    }

    public function test_it_returns_the_same_response_instance(): void
    {
        // Header mutation is in-place — proves we did not
        // silently replace the response with a fresh one and drop
        // whatever body / status the controller produced.
        $seed = new Response('body', 418);

        $response = $this->pipe($seed);

        $this->assertSame($seed, $response);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertSame('body', $response->getContent());
    }
}
