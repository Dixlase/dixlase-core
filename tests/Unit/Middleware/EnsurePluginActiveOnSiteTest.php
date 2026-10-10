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

namespace Tests\Unit\Middleware;

use App\Contracts\Site\SiteContextInterface;
use App\Http\Middleware\EnsurePluginActiveOnSite;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class EnsurePluginActiveOnSiteTest extends TestCase
{
    public function test_passes_request_through_when_plugin_is_active_on_site(): void
    {
        $siteContext = $this->createMock(SiteContextInterface::class);
        $siteContext->method('isPluginActive')->with('my-plugin')->willReturn(true);

        $middleware = new EnsurePluginActiveOnSite($siteContext);
        $request = Request::create('/api/v1/my-plugin/foo', 'GET');

        $reached = false;
        $response = $middleware->handle($request, function () use (&$reached) {
            $reached = true;

            return response()->json(['ok' => true]);
        }, 'my-plugin');

        $this->assertTrue($reached, 'next handler must be called when plugin is active on the current site');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_returns_404_unified_envelope_when_plugin_is_not_active_on_site(): void
    {
        $siteContext = $this->createMock(SiteContextInterface::class);
        $siteContext->method('isPluginActive')->with('disabled-plugin')->willReturn(false);

        $middleware = new EnsurePluginActiveOnSite($siteContext);
        $request = Request::create('/api/v1/disabled-plugin/foo', 'GET');

        $reached = false;
        $response = $middleware->handle($request, function () use (&$reached) {
            $reached = true;

            return response()->json(['ok' => true]);
        }, 'disabled-plugin');

        $this->assertFalse($reached, 'next handler must NOT be called when the plugin is disabled for the site');
        $this->assertSame(404, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertSame('not_found', $body['error']['code']);
        $this->assertArrayHasKey('timestamp', $body['meta']);
    }

    public function test_web_format_aborts_with_html_404_when_plugin_is_not_active_on_site(): void
    {
        $siteContext = $this->createMock(SiteContextInterface::class);
        $siteContext->method('isPluginActive')->with('disabled-plugin')->willReturn(false);

        $middleware = new EnsurePluginActiveOnSite($siteContext);
        $request = Request::create('/disabled-plugin/page', 'GET');

        $reached = false;

        try {
            $middleware->handle($request, function () use (&$reached) {
                $reached = true;

                return response('ok');
            }, 'disabled-plugin', 'web');
            $this->fail('An inactive plugin web route must abort with 404');
        } catch (NotFoundHttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }

        $this->assertFalse($reached, 'next handler must NOT be called when the plugin is disabled for the site');
    }
}
