<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Api\ApiErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

/**
 * @api Stable API for plugins/themes — base class for outgoing API collections.
 *
 * Companion to BaseApiResource for list endpoints. Produces the unified
 * envelope documented in docs/development/api-reference/versioning.md:
 *
 *   {
 *     "data":  [ ... item resources ... ],
 *     "meta":  {
 *       "site_id": 1|null,
 *       "timestamp": "ISO 8601",
 *       "pagination": { "current_page": 1, "per_page": 20, "total": 130, "last_page": 7 }
 *     },
 *     "links": {
 *       "self": "https://...?page=1",
 *       "next": "https://...?page=2"|null,
 *       "prev": "https://...?page=0"|null
 *     }
 *   }
 *
 * Pagination metadata is automatically added when the wrapped resource
 * is a Laravel paginator; otherwise meta.pagination and the next/prev
 * links are omitted.
 *
 * Plugin authors subclass this and (optionally) declare
 * `public $collects = MyResource::class` to control how each item is
 * shaped — same convention as Laravel's ResourceCollection.
 */
abstract class BaseApiCollection extends ResourceCollection
{
    /**
     * Top-level keys merged into the response alongside the data wrap.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        $meta = ApiErrorResponse::meta();
        $links = ['self' => $request->fullUrl()];

        if ($this->resource instanceof AbstractPaginator) {
            $meta['pagination'] = [
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'last_page' => $this->resource->lastPage(),
            ];

            $links['next'] = $this->resource->nextPageUrl();
            $links['prev'] = $this->resource->previousPageUrl();
        }

        return [
            'meta' => $meta,
            'links' => $links,
        ];
    }

    /**
     * Suppress Laravel's default pagination links/meta so they do not
     * conflict with the unified envelope assembled in with(). The
     * versioning spec reshapes both blocks (pagination nests under
     * meta.pagination, links use self/next/prev only), so we intercept
     * the default pipeline here.
     *
     * @param  \Illuminate\Pagination\AbstractPaginator  $paginated
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function paginationInformation($request, $paginated, $default): array
    {
        return [];
    }
}
