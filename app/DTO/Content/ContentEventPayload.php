<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

declare(strict_types=1);

namespace App\DTO\Content;

use App\Events\DixlaseEvents;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Payload of the content events (DixlaseEvents::CONTENT_*).
 *
 * One shape for every emitter, core or extension, so a subscriber -- a
 * listener, a webhook, a static-site rebuild -- never has to know which
 * extension provided the content.
 *
 * It carries identifiers only. The content body is never included, so a
 * webhook delivery stays small and cannot leak a draft; a subscriber that
 * needs the body reads it through the API.
 *
 * Compatibility policy: within a schema version, fields may only be added.
 * Removing or renaming a field, or changing its type or nullability, bumps
 * {@see self::SCHEMA_VERSION}.
 */
final readonly class ContentEventPayload implements JsonSerializable
{
    /**
     * Version of the payload shape, also sent as the `version` field.
     */
    public const SCHEMA_VERSION = 1;

    /**
     * @param  string  $source  Extension slug of the emitter, or "core"
     * @param  string  $type  Content type; the same string an ApiResourceProviderInterface reports from getResourceType()
     * @param  string|int  $id  Identifier of the item within its type
     * @param  string|null  $slug  Slug of the item, when it has one
     * @param  string|null  $url  Site-relative public path beginning with "/", or null when the item has no public URL
     * @param  int|null  $siteId  Site the item belongs to; null for network-level content
     * @param  string  $status  Status slug after the change (for a deletion, the status it had before), e.g. "draft", "published", "scheduled"
     * @param  string|int|null  $actorId  Member who made the change; null for system changes (a scheduler, an import)
     * @param  string  $occurredAt  ISO 8601 timestamp of the change
     */
    public function __construct(
        public string $source,
        public string $type,
        public string|int $id,
        public ?string $slug,
        public ?string $url,
        public ?int $siteId,
        public string $status,
        public string|int|null $actorId,
        public string $occurredAt,
    ) {
        if ($url !== null && ! str_starts_with($url, '/')) {
            throw new InvalidArgumentException('A content event url must be site-relative and begin with "/".');
        }
    }

    /**
     * Fire one of the content events with this payload.
     *
     * Listeners receive the payload as a single array argument, and the
     * webhook dispatcher delivers the same array.
     *
     * @throws InvalidArgumentException When $event is not a content event
     */
    public function dispatch(string $event): void
    {
        if (! in_array($event, DixlaseEvents::byCategory('content'), true)) {
            throw new InvalidArgumentException("[{$event}] is not a content event.");
        }

        Event::dispatch($event, [$this->toArray()]);
    }

    /**
     * @return array{version: int, source: string, type: string, id: string|int, slug: string|null, url: string|null, site_id: int|null, status: string, actor_id: string|int|null, occurred_at: string}
     */
    public function toArray(): array
    {
        return [
            'version' => self::SCHEMA_VERSION,
            'source' => $this->source,
            'type' => $this->type,
            'id' => $this->id,
            'slug' => $this->slug,
            'url' => $this->url,
            'site_id' => $this->siteId,
            'status' => $this->status,
            'actor_id' => $this->actorId,
            'occurred_at' => $this->occurredAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
