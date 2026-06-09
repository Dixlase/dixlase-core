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

namespace App\Helpers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Shared logic for the admin / front-end IP access-control feature.
 *
 * Centralises how raw IP-list settings are parsed and how an IP is matched
 * against them, so that the enforcing middleware and the settings-screen
 * lockout / format checks always agree. Matching delegates to Symfony's
 * IpUtils::checkIp() so both plain IPv4/IPv6 addresses and CIDR ranges
 * (`192.168.1.0/24`, `2001:db8::/32`) are honoured.
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
     * Whether the given client IP matches any entry in the raw IP-list setting.
     *
     * Each entry may be a plain IPv4/IPv6 address or CIDR notation
     * (e.g. `192.168.1.0/24`, `2001:db8::/32`). Matching is delegated to
     * Symfony's IpUtils::checkIp() so the IPv4/IPv6 distinction and subnet
     * arithmetic are handled correctly. Invalid entries in the list are
     * silently skipped (IpUtils returns false for them without throwing).
     */
    public static function listContainsIp(?string $clientIp, ?string $raw): bool
    {
        return self::ipMatchesAny($clientIp, self::parseList($raw));
    }

    /**
     * Whether the given client IP matches any entry in the already-parsed list.
     *
     * @param  array<int, string>  $list
     */
    public static function ipMatchesAny(?string $clientIp, array $list): bool
    {
        if ($clientIp === null || $list === []) {
            return false;
        }

        return IpUtils::checkIp($clientIp, $list);
    }

    /**
     * Whether an entry is a valid plain IP address or CIDR range.
     *
     * Accepts:
     *   - IPv4 / IPv6 plain addresses
     *   - IPv4 with /0 to /32
     *   - IPv6 with /0 to /128
     */
    public static function isValidIpOrCidr(string $entry): bool
    {
        $entry = trim($entry);
        if ($entry === '') {
            return false;
        }

        if (str_contains($entry, '/')) {
            [$ip, $mask] = explode('/', $entry, 2);
            if (! ctype_digit($mask)) {
                return false;
            }
            $maskInt = (int) $mask;
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                return $maskInt >= 0 && $maskInt <= 32;
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                return $maskInt >= 0 && $maskInt <= 128;
            }

            return false;
        }

        return filter_var($entry, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Return any entries from the raw list that are not a valid IP or CIDR.
     *
     * Empty / null input returns an empty array.
     *
     * @return array<int, string>
     */
    public static function invalidEntries(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(
            self::parseList($raw),
            static fn (string $entry): bool => ! self::isValidIpOrCidr($entry),
        ));
    }

    /**
     * Inspect the current connection for reverse-proxy / TRUSTED_PROXIES issues.
     *
     * `proxy_issue` is true when a forwarded header is present AND either:
     *   - no trusted proxies are configured at all, OR
     *   - the resolved client IP is still a private / loopback address (i.e.
     *     trust is configured but does not actually cover the proxy in front
     *     of the application, so $request->ip() never escapes the proxy
     *     range).
     *
     * The misconfigured-trust case matters because the first case alone misses
     * a common operator mistake: TRUSTED_PROXIES is set but to the wrong
     * subnet, so the IP allow/block lists silently see every visitor as the
     * proxy.
     *
     * `trusted_proxies_value` is a printable form of the current configured
     * value (`null` if unconfigured, `*` for the trust-anything sentinel, or
     * a comma-joined string for an array of IPs / CIDR ranges) so the view
     * can show the operator what they have versus what is needed.
     *
     * @return array{
     *     client_ip: string|null,
     *     remote_addr: string|null,
     *     trusted_proxies_configured: bool,
     *     trusted_proxies_value: string|null,
     *     proxy_issue: bool,
     *     suggested_trusted_proxies: string|null
     * }
     */
    public static function inspectConnection(Request $request): array
    {
        $proxies = config('trustedproxy.proxies');
        $trustedProxiesConfigured = ! empty($proxies);
        $trustedProxiesValue = self::formatTrustedProxiesValue($proxies);

        $hasForwardedHeader = $request->headers->has('X-Forwarded-For')
            || $request->headers->has('Forwarded');

        $clientIp = $request->ip();
        $clientIpIsPrivate = $clientIp !== null && IpUtils::isPrivateIp($clientIp);

        $proxyIssue = $hasForwardedHeader && (! $trustedProxiesConfigured || $clientIpIsPrivate);

        $remoteAddr = $request->server->get('REMOTE_ADDR');
        $remoteAddr = is_string($remoteAddr) ? $remoteAddr : null;

        return [
            'client_ip' => $clientIp,
            'remote_addr' => $remoteAddr,
            'trusted_proxies_configured' => $trustedProxiesConfigured,
            'trusted_proxies_value' => $trustedProxiesValue,
            'proxy_issue' => $proxyIssue,
            'suggested_trusted_proxies' => $proxyIssue ? $remoteAddr : null,
        ];
    }

    /**
     * Render the current TRUSTED_PROXIES setting as a single string for display.
     */
    private static function formatTrustedProxiesValue(mixed $proxies): ?string
    {
        if (empty($proxies)) {
            return null;
        }

        if ($proxies === '*') {
            return '*';
        }

        if (is_array($proxies)) {
            return implode(', ', array_filter(array_map(static fn ($p) => is_string($p) ? $p : null, $proxies)));
        }

        if (is_string($proxies)) {
            return $proxies;
        }

        return null;
    }
}
