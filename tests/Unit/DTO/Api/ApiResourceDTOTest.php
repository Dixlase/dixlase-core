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

namespace Tests\Unit\DTO\Api;

use App\DTO\Api\ApiResourceDTO;
use App\DTO\Fields\FieldDefinition;
use App\Services\Fields\FieldTypeRegistry;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The resource shape: content_format and fields (#1101).
 */
class ApiResourceDTOTest extends TestCase
{
    private function resource(?string $format = null, array $fields = []): ApiResourceDTO
    {
        return new ApiResourceDTO(
            id: '01J00000000000000000000000',
            type: 'event',
            slug: 'launch',
            title: 'Launch',
            content: '<p>Hi</p>',
            excerpt: null,
            metaDescription: null,
            status: 'published',
            publishedAt: '2026-11-22T10:00:00+00:00',
            updatedAt: null,
            url: '/events/launch',
            source: 'acme-events',
            meta: ['views' => 3],
            contentFormat: $format,
            fields: $fields,
        );
    }

    public function test_a_call_written_before_the_new_properties_still_works(): void
    {
        $resource = new ApiResourceDTO('1', 'page', 'about', 'About', '<p>x</p>', null, null, 'published', null, null, '/about', 'core');

        $json = json_decode(json_encode($resource), true);

        $this->assertNull($json['content_format']);
        $this->assertSame([], $json['fields']);
        $this->assertSame([], $json['meta']);
        // An empty `fields` is an object, so a consumer can always read keys from it.
        $this->assertSame('{}', json_encode($resource->jsonSerialize()['fields']));
    }

    public function test_it_serialises_the_format_and_the_fields(): void
    {
        $fields = [
            'starts_on' => '2026-12-01',
            'hero' => ['id' => 12, 'url' => 'https://example.com/media/hero.jpg'],
            'venue' => ['type' => 'place', 'id' => '7'],
            'tags' => ['a', 'b'],
        ];

        $json = json_decode(json_encode($this->resource(ApiResourceDTO::CONTENT_FORMAT_HTML, $fields)), true);

        $this->assertSame('html', $json['content_format']);
        $this->assertSame($fields, $json['fields']);
        $this->assertSame(['views' => 3], $json['meta']);
        $this->assertSame(
            ['id', 'type', 'slug', 'title', 'content', 'content_format', 'excerpt', 'meta_description', 'status', 'published_at', 'updated_at', 'url', 'source', 'fields', 'meta'],
            array_keys($json),
        );
    }

    public function test_every_listed_format_is_accepted(): void
    {
        foreach (ApiResourceDTO::CONTENT_FORMATS as $format) {
            $this->assertSame($format, $this->resource($format)->contentFormat);
        }
    }

    public function test_an_unknown_format_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resource('rtf');
    }

    public function test_fields_take_the_field_registry_output(): void
    {
        $definitions = [
            new FieldDefinition('price', 'number', 'Price', ['integer' => true]),
            new FieldDefinition('featured', 'toggle', 'Featured'),
        ];
        $fields = app(FieldTypeRegistry::class)->apiValues($definitions, ['price' => 1200, 'featured' => true]);

        $json = json_decode(json_encode($this->resource(fields: $fields)), true);

        $this->assertSame(['price' => 1200, 'featured' => true], $json['fields']);
    }
}
