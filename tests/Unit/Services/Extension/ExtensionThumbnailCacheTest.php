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

namespace Tests\Unit\Services\Extension;

use App\Http\Controllers\Admin\Settings\AdminExtensionThumbnailController;
use App\Models\ExtensionSource;
use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * An extension that ships no thumbnail must be asked about once, not once
 * per visit to the list.
 *
 * `Cache::remember()` treats a null return as "nothing cached", so the
 * misses — the answers that cost the most round trips — were the only
 * ones never remembered.
 */
class ExtensionThumbnailCacheTest extends TestCase
{
    use RefreshDatabase;

    private ExtensionSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->source = ExtensionSource::query()->create([
            'name' => 'Test GitHub',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'TestOrg',
            'auth_token' => 'ghp_test_token',
            'priority' => 0,
            'is_enabled' => true,
        ]);
    }

    public function test_an_extension_without_a_thumbnail_is_only_looked_up_once(): void
    {
        Http::fake([
            '*/contents/plugin.json' => Http::response([], 404),
            '*/contents/resources/assets' => Http::response([], 404),
            '*/contents' => Http::response([], 404),
        ]);

        $this->show('dixlase-bare');
        $afterFirst = 3;
        Http::assertSentCount($afterFirst);

        $this->show('dixlase-bare');

        // Still the same three: the second visit was answered from cache.
        Http::assertSentCount($afterFirst);
    }

    public function test_a_found_thumbnail_is_served_from_cache_too(): void
    {
        Http::fake([
            '*/contents/plugin.json' => Http::response(['content' => base64_encode('{}'), 'encoding' => 'base64']),
            '*/contents/thumbnail.png' => Http::response([
                'content' => base64_encode('PNGBYTES'),
                'encoding' => 'base64',
            ]),
            '*/contents' => Http::response([
                ['type' => 'file', 'name' => 'thumbnail.png'],
            ]),
        ]);

        $first = $this->show('dixlase-example');
        $sent = 3;
        Http::assertSentCount($sent);

        $second = $this->show('dixlase-example');

        Http::assertSentCount($sent);
        $this->assertSame(200, $second->getStatusCode());
        $this->assertSame($first->getContent(), $second->getContent());
    }

    public function test_a_disabled_source_is_never_asked(): void
    {
        Http::fake();
        $this->source->update(['is_enabled' => false]);

        $this->show('dixlase-example');

        Http::assertNothingSent();
    }

    private function show(string $slug): \Symfony\Component\HttpFoundation\Response
    {
        $controller = new AdminExtensionThumbnailController();

        return $controller->showOnline(
            Request::create('/thumbnail'),
            $this->source,
            'plugin',
            $slug,
            app(ExtensionSourceManager::class),
        );
    }
}
