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

namespace Tests\Unit\DTO\Content;

use App\DTO\Content\ContentEventPayload;
use App\Events\DixlaseEvents;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The content event payload (#1099): one shape for every emitter.
 */
class ContentEventPayloadTest extends TestCase
{
    private function payload(?string $url = '/news/hello'): ContentEventPayload
    {
        return new ContentEventPayload(
            source: 'core',
            type: 'news',
            id: 7,
            slug: 'hello',
            url: $url,
            siteId: 1,
            status: 'published',
            actorId: 3,
            occurredAt: '2026-11-22T10:00:00+00:00',
        );
    }

    public function test_it_serialises_to_snake_case_with_a_version(): void
    {
        $this->assertSame([
            'version' => ContentEventPayload::SCHEMA_VERSION,
            'source' => 'core',
            'type' => 'news',
            'id' => 7,
            'slug' => 'hello',
            'url' => '/news/hello',
            'site_id' => 1,
            'status' => 'published',
            'actor_id' => 3,
            'occurred_at' => '2026-11-22T10:00:00+00:00',
        ], $this->payload()->toArray());

        $this->assertSame(
            json_encode($this->payload()->toArray()),
            json_encode($this->payload()),
        );
    }

    public function test_it_never_carries_a_body(): void
    {
        $keys = array_keys($this->payload()->toArray());

        foreach (['content', 'body', 'title', 'excerpt'] as $forbidden) {
            $this->assertNotContains($forbidden, $keys);
        }
    }

    public function test_a_null_url_is_allowed(): void
    {
        $this->assertNull($this->payload(null)->url);
    }

    public function test_an_absolute_url_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->payload('https://example.com/news/hello');
    }

    public function test_dispatch_fires_the_event_with_the_array_as_one_argument(): void
    {
        $received = null;
        Event::listen(DixlaseEvents::CONTENT_PUBLISHED, function ($payload) use (&$received) {
            $received = $payload;
        });

        $this->payload()->dispatch(DixlaseEvents::CONTENT_PUBLISHED);

        $this->assertSame($this->payload()->toArray(), $received);
    }

    public function test_dispatch_refuses_an_event_outside_the_content_category(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->payload()->dispatch(DixlaseEvents::URL_SLUG_CHANGED);
    }
}
