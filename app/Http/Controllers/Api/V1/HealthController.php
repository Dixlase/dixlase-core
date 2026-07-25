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

namespace App\Http\Controllers\Api\V1;

use App\Support\Api\ApiErrorResponse;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/health — public liveness probe for the REST API.
 *
 * No authentication. Returns 200 with the canonical envelope so external
 * monitors and integration tests can verify the API is reachable, the
 * site has been resolved, and the version is what they expect. See
 * docs/development/api-reference/versioning.md Section 11.
 */
final class HealthController
{
    /**
     * Hardcoded for v0.1.0. v0.2+ will read this from a centralized
     * source (composer.json or a generated VERSION file) so the value
     * does not drift on release.
     */
    public const VERSION = '0.1.0';

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'status' => 'ok',
                'version' => self::VERSION,
            ],
            'meta' => ApiErrorResponse::meta(),
        ]);
    }
}
