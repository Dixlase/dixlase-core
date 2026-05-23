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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Helpers;

use Illuminate\Http\Request;

/**
 * Shared logic for the admin / front-end IP access-control feature.
 *
 * Centralises how raw IP-list settings are parsed so that the enforcing
 * middleware and the settings-screen lockout check always agree, and exposes
 * a reverse-proxy diagnosis used to guide operators on the settings screen.
 */
final class IpAccessControlHelper
{
    private function __construct() {}

    /**
     * Split a raw IP-list setting into a trimmed, de-duplicated, non-empty list.
     *
     * Entries may be separated by commas, semicolons, or any whitespace
     * (including newlines), so the value works regardless of whether the
     * operator typed one address per line or a comma-separated string.
     *
     * @return array<int, string>
     */
    public static function parseList(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($parts));
    }

    /**
     * Whether the given client IP is present in the raw IP-list setting.
     */
    public static function listContainsIp(?string $clientIp, ?string $raw): bool
    {
        if ($clientIp === null) {
            return false;
        }

        return in_array($clientIp, self::parseList($raw), true);
    }

    /**
     * Inspect the current connection for reverse-proxy / TRUSTED_PROXIES issues.
     *
     * `proxy_issue` is true when a forwarded header is present but no trusted
     * proxies are configured — meaning the application cannot see real client
     * IPs and the IP allow/block lists will not behave as expected.
     *
     * @return array{
     *     client_ip: string|null,
     *     remote_addr: string|null,
     *     trusted_proxies_configured: bool,
     *     proxy_issue: bool,
     *     suggested_trusted_proxies: string|null
     * }
     */
    public static function inspectConnection(Request $request): array
    {
        $trustedProxiesConfigured = ! empty(config('trustedproxy.proxies'));

        $hasForwardedHeader = $request->headers->has('X-Forwarded-For')
            || $request->headers->has('Forwarded');

        $proxyIssue = $hasForwardedHeader && ! $trustedProxiesConfigured;

        $remoteAddr = $request->server->get('REMOTE_ADDR');
        $remoteAddr = is_string($remoteAddr) ? $remoteAddr : null;

        return [
            'client_ip' => $request->ip(),
            'remote_addr' => $remoteAddr,
            'trusted_proxies_configured' => $trustedProxiesConfigured,
            'proxy_issue' => $proxyIssue,
            'suggested_trusted_proxies' => $proxyIssue ? $remoteAddr : null,
        ];
    }
}
