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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Support\Api;

use App\Contracts\Site\SiteContextInterface;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * @api Stable API for plugins/themes returning core's unified error envelope.
 *
 * Single source of truth for the API error JSON shape documented in
 * docs/development/api-reference/versioning.md:
 *
 *   {
 *     "error": {
 *       "code": "snake_case_identifier",
 *       "message": "Human-readable English sentence.",
 *       "details": { ... optional structured payload ... }
 *     },
 *     "meta": {
 *       "site_id": 1 | null,
 *       "timestamp": "ISO 8601"
 *     }
 *   }
 *
 * Error messages are always English (see Section 5 of versioning.md).
 * Plugins should use this class instead of building the envelope by
 * hand so future shape changes propagate automatically.
 */
final class ApiErrorResponse
{
    /**
     * Build a JSON error response in the unified API envelope.
     *
     * @param  string  $code  Stable snake_case identifier (e.g. 'validation_failed')
     * @param  int  $status  HTTP status code (4xx / 5xx)
     * @param  string  $message  Human-readable English sentence
     * @param  array<string, mixed>  $details  Optional structured payload (e.g. validation errors keyed by field)
     */
    public static function make(string $code, int $status, string $message, array $details = []): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== []) {
            $error['details'] = $details;
        }

        return response()->json([
            'error' => $error,
            'meta' => self::meta(),
        ], $status);
    }

    /**
     * Build the meta block carried by every API response.
     *
     * Public so non-error responses (e.g. successful resource payloads
     * via BaseApiResource) can stay shape-consistent with errors.
     *
     * @return array{site_id: int|null, timestamp: string}
     */
    public static function meta(): array
    {
        $siteId = null;
        try {
            $siteId = app(SiteContextInterface::class)->currentSiteId();
        } catch (Throwable) {
            // SiteContext may be unresolvable in edge cases (e.g. early
            // bootstrap, install flow). Tolerate a null site_id rather
            // than failing the response.
        }

        return [
            'site_id' => $siteId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
