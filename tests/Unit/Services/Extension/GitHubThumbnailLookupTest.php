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

use App\Models\ExtensionSource;
use App\Services\Extension\GitHubSourceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * How many times the plugin list asks GitHub for one card image.
 *
 * It used to probe up to twelve candidate paths one at a time, and
 * because Laravel's retry() treats a 404 as a failure, each "not here"
 * answer cost a second of sleep and a second request. An extension with
 * no thumbnail took about 14 seconds, and the admin screen draws one
 * card per extension.
 */
class GitHubThumbnailLookupTest extends TestCase
{
    use RefreshDatabase;

    private GitHubSourceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $source = ExtensionSource::query()->create([
            'name' => 'Test GitHub',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'TestOrg',
            'auth_token' => 'ghp_test_token',
            'priority' => 0,
        ]);

        $this->provider = new GitHubSourceProvider($source);
    }

    public function test_a_root_thumbnail_is_found_from_the_directory_listing(): void
    {
        Http::fake([
            '*/contents/plugin.json' => Http::response(['content' => base64_encode('{}'), 'encoding' => 'base64']),
            '*/contents' => Http::response([
                ['type' => 'file', 'name' => 'plugin.json'],
                ['type' => 'file', 'name' => 'thumbnail.png'],
            ]),
            '*/contents/thumbnail.png' => Http::response([
                'content' => base64_encode('PNGBYTES'),
                'encoding' => 'base64',
            ]),
        ]);

        $result = $this->provider->fetchThumbnail('dixlase-example');

        $this->assertSame('PNGBYTES', $result['content']);
        $this->assertSame('image/png', $result['mime']);
    }

    public function test_an_extension_without_a_thumbnail_is_answered_in_a_handful_of_calls(): void
    {
        Http::fake([
            '*/contents/plugin.json' => Http::response(['content' => base64_encode('{}'), 'encoding' => 'base64']),
            '*/contents/resources/assets' => Http::response([], 404),
            '*/contents' => Http::response([
                ['type' => 'file', 'name' => 'plugin.json'],
                ['type' => 'file', 'name' => 'README.md'],
            ]),
        ]);

        $this->assertNull($this->provider->fetchThumbnail('dixlase-bare'));

        // Manifest, root listing, legacy directory listing. The old shape
        // made one request per candidate name, twice over, with a second of
        // sleep between the pairs.
        Http::assertSentCount(3);
    }

    public function test_the_legacy_location_is_still_found(): void
    {
        Http::fake([
            '*/contents/plugin.json' => Http::response(['content' => base64_encode('{}'), 'encoding' => 'base64']),
            '*/contents/resources/assets/thumbnail.webp' => Http::response([
                'content' => base64_encode('WEBPBYTES'),
                'encoding' => 'base64',
            ]),
            '*/contents/resources/assets' => Http::response([
                ['type' => 'file', 'name' => 'thumbnail.webp'],
            ]),
            '*/contents' => Http::response([
                ['type' => 'file', 'name' => 'plugin.json'],
            ]),
        ]);

        $result = $this->provider->fetchThumbnail('dixlase-legacy');

        $this->assertSame('WEBPBYTES', $result['content']);
        $this->assertSame('image/webp', $result['mime']);
    }

    public function test_a_manifest_can_point_somewhere_else(): void
    {
        Http::fake([
            '*/contents/plugin.json' => Http::response([
                'content' => base64_encode('{"thumbnail":"art/card.jpg"}'),
                'encoding' => 'base64',
            ]),
            '*/contents/art/card.jpg' => Http::response([
                'content' => base64_encode('JPGBYTES'),
                'encoding' => 'base64',
            ]),
            '*/contents' => Http::response([
                ['type' => 'file', 'name' => 'plugin.json'],
            ]),
        ]);

        $result = $this->provider->fetchThumbnail('dixlase-declared');

        $this->assertSame('JPGBYTES', $result['content']);
        $this->assertSame('image/jpeg', $result['mime']);
    }

    public function test_a_directory_entry_that_is_not_a_file_is_ignored(): void
    {
        Http::fake([
            '*/contents/plugin.json' => Http::response(['content' => base64_encode('{}'), 'encoding' => 'base64']),
            '*/contents/resources/assets' => Http::response([], 404),
            '*/contents' => Http::response([
                // A directory that happens to be named like the file.
                ['type' => 'dir', 'name' => 'thumbnail.png'],
            ]),
        ]);

        $this->assertNull($this->provider->fetchThumbnail('dixlase-trap'));
    }

    public function test_a_missing_answer_is_not_retried(): void
    {
        Http::fake([
            '*/contents/plugin.json' => Http::response([], 404),
            '*/contents/resources/assets' => Http::response([], 404),
            '*/contents' => Http::response([], 404),
        ]);

        $this->assertNull($this->provider->fetchThumbnail('dixlase-gone'));

        // One request each: a 404 is an answer, and sleeping a second before
        // asking the same question again only delays the same answer.
        Http::assertSentCount(3);
    }
}
