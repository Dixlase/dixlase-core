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

namespace Tests\Feature\I18n;

use App\Contracts\I18n\MissingTranslationHandler;
use App\Services\I18n\DefaultMissingTranslationHandler;
use Database\Seeders\SitesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MissingTranslationHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SitesSeeder::class);
        DB::table('sites')->where('is_primary', true)->update(['primary_locale' => 'ja']);
    }

    public function test_default_handler_is_bound_in_container(): void
    {
        $resolved = app(MissingTranslationHandler::class);

        $this->assertInstanceOf(DefaultMissingTranslationHandler::class, $resolved);
    }

    public function test_default_handler_redirects_to_site_primary_locale(): void
    {
        $handler = app(MissingTranslationHandler::class);

        $request = Request::create('/en/about', 'GET');
        $response = $handler->handle($request, 'en');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/ja/about', $response->headers->get('location') ?? '');
    }

    public function test_default_handler_returns_404_when_requested_equals_target(): void
    {
        // Site primary is 'ja'; requesting 'ja' would loop, so return 404.
        $handler = app(MissingTranslationHandler::class);

        $request = Request::create('/ja/about', 'GET');
        $response = $handler->handle($request, 'ja');

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test_default_handler_preserves_query_string(): void
    {
        $handler = app(MissingTranslationHandler::class);

        $request = Request::create('/en/about?foo=1&bar=baz', 'GET');
        $response = $handler->handle($request, 'en');

        $location = $response->headers->get('location') ?? '';
        $this->assertStringContainsString('foo=1', $location);
        $this->assertStringContainsString('bar=baz', $location);
    }

    public function test_handler_can_be_swapped_by_plugin(): void
    {
        // Simulate a plugin swapping the binding to a custom handler
        // (e.g., one that returns 404 instead of redirecting).
        app()->bind(MissingTranslationHandler::class, function () {
            return new class implements MissingTranslationHandler
            {
                public function handle(Request $request, string $requestedLocale): Response
                {
                    return new Response('plugin-overridden', Response::HTTP_NOT_FOUND);
                }
            };
        });

        $handler = app(MissingTranslationHandler::class);
        $response = $handler->handle(Request::create('/en/about', 'GET'), 'en');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('plugin-overridden', $response->getContent());
    }
}
