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

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Api\ApiErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @api Stable API for plugins/themes — base class for outgoing API resources.
 *
 * Implements the success-side of the unified envelope documented in
 * docs/development/api-reference/versioning.md:
 *
 *   {
 *     "data":  { ... toArray() result ... },
 *     "meta":  { "site_id": 1|null, "timestamp": "ISO 8601" },
 *     "links": { "self": "https://..." }
 *   }
 *
 * Plugin authors subclass this and implement toArray($request) to map
 * their model to a stable JSON shape. The data wrap, meta block, and
 * self link are handled by Laravel's JsonResource pipeline plus the
 * with() implementation here.
 *
 * Naming distinction: this class is the HTTP-side response envelope. It
 * is NOT App\DTO\Api\ApiResourceDTO, which is the plugin → core content
 * abstraction used by ApiResourceProviderInterface. The two layers are
 * complementary.
 */
abstract class BaseApiResource extends JsonResource
{
    /**
     * Top-level keys merged into the response alongside the data wrap.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => ApiErrorResponse::meta(),
            'links' => [
                'self' => $request->fullUrl(),
            ],
        ];
    }
}
