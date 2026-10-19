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

namespace App\Support\Install;

use Illuminate\Http\Request;

/**
 * Where a finalize request may send the browser.
 *
 * The completion screen posts the destination it wants as `redirect_to`.
 * It only ever sends this site's own URLs, but the value arrives from the
 * client, so it is followed only when it stays on the request's host.
 * Anything else goes to the front page.
 *
 * Shared by InstallCompleteController::finalize() and by
 * CheckInstallationReady, which answers a repeated finalize (a double
 * click) after the first one has already completed the install.
 */
class FinalizeRedirectTarget
{
    /**
     * The request's `redirect_to` when it stays on this host, otherwise `/`.
     */
    public static function for(Request $request): string
    {
        $target = $request->input('redirect_to');

        return is_string($target) && self::isSameHost($request, $target) ? $target : '/';
    }

    /**
     * True for an http(s) URL on the request's host, or a path on it.
     * Protocol-relative (`//host`) and backslash forms are refused, since
     * browsers resolve them to another host.
     */
    public static function isSameHost(Request $request, string $target): bool
    {
        if ($target === '' || str_contains($target, '\\')) {
            return false;
        }

        if (str_starts_with($target, '/')) {
            return ! str_starts_with($target, '//');
        }

        $parts = parse_url($target);

        return is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && isset($parts['host'])
            && strcasecmp($parts['host'], $request->getHost()) === 0;
    }
}
