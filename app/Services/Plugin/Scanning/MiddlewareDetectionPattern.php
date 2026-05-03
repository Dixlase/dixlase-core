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

namespace App\Services\Plugin\Scanning;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Detection pattern for middleware registration
 *
 * Detects system.register_middleware
 * Distinguishes between "usage" via Route::middleware() and "registration" via pushMiddleware()
 */
class MiddlewareDetectionPattern extends DetectionPattern
{
    public function permissionKey(): string
    {
        return 'system.register_middleware';
    }

    public function filePatterns(): array
    {
        return ['app/Http/Middleware/*.php'];
    }

    public function regexPatterns(): array
    {
        return [
            '/\$this->app\[.*Router.*\]->pushMiddleware/i',
            '/->aliasMiddleware\s*\(/i',
            '/->pushMiddlewareToGroup\s*\(/i',
        ];
    }

    /**
     * Excludes middleware "usage" via Route::middleware(),
     * detects only "registration"
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        // Middleware usage in route definitions is not "registration"
        $trimmedLine = ltrim($line);
        if (preg_match('/^Route::/i', $trimmedLine) && str_contains($trimmedLine, 'middleware')) {
            return false;
        }

        // ->middleware('name') in method chains is "usage"
        if (preg_match('/->middleware\s*\(\s*[\'"]/i', $match)) {
            return false;
        }

        return true;
    }
}
