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

namespace Tests\Unit\Resources;

use App\Http\Resources\BaseApiCollection;
use App\Http\Resources\BaseApiResource;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BaseApiResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_resource_wraps_data_with_meta_and_links(): void
    {

        $resource = new FixtureItemResource((object) ['id' => 42, 'label' => 'foo']);
        $request = Request::create('https://example.com/api/v1/items/42', 'GET');

        $body = $resource->toResponse($request)->getData(true);

        $this->assertSame(['id' => 42, 'label' => 'foo'], $body['data']);
        $this->assertSame(1, $body['meta']['site_id']);
        $this->assertArrayHasKey('timestamp', $body['meta']);
        $this->assertSame('https://example.com/api/v1/items/42', $body['links']['self']);
    }

    public function test_resource_collection_omits_pagination_meta_for_plain_arrays(): void
    {

        $items = new Collection([
            (object) ['id' => 1, 'label' => 'a'],
            (object) ['id' => 2, 'label' => 'b'],
        ]);

        $collection = new FixtureItemCollection($items);
        $request = Request::create('https://example.com/api/v1/items', 'GET');

        $body = $collection->toResponse($request)->getData(true);

        $this->assertCount(2, $body['data']);
        $this->assertSame(1, $body['meta']['site_id']);
        $this->assertArrayHasKey('timestamp', $body['meta']);
        $this->assertArrayNotHasKey('pagination', $body['meta']);
        $this->assertSame('https://example.com/api/v1/items', $body['links']['self']);
        $this->assertArrayNotHasKey('next', $body['links']);
        $this->assertArrayNotHasKey('prev', $body['links']);
    }

    public function test_resource_collection_includes_pagination_block_for_paginators(): void
    {

        $items = collect([
            (object) ['id' => 1, 'label' => 'a'],
            (object) ['id' => 2, 'label' => 'b'],
        ]);
        $paginator = new LengthAwarePaginator(
            items: $items,
            total: 130,
            perPage: 20,
            currentPage: 1,
            options: ['path' => 'https://example.com/api/v1/items'],
        );

        $collection = new FixtureItemCollection($paginator);
        $request = Request::create('https://example.com/api/v1/items?page=1', 'GET');

        $body = $collection->toResponse($request)->getData(true);

        $this->assertCount(2, $body['data']);
        $this->assertSame([
            'current_page' => 1,
            'per_page' => 20,
            'total' => 130,
            'last_page' => 7,
        ], $body['meta']['pagination']);
        $this->assertNull($body['links']['prev']);
        $this->assertSame('https://example.com/api/v1/items?page=2', $body['links']['next']);
    }
}

class FixtureItemResource extends BaseApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
        ];
    }
}

class FixtureItemCollection extends BaseApiCollection
{
    public $collects = FixtureItemResource::class;
}
