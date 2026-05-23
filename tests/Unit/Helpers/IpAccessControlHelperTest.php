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

namespace Tests\Unit\Helpers;

use App\Helpers\IpAccessControlHelper;
use Illuminate\Http\Request;
use Tests\TestCase;

class IpAccessControlHelperTest extends TestCase
{
    public function test_parse_list_splits_on_commas(): void
    {
        $this->assertSame(['1.2.3.4', '5.6.7.8'], IpAccessControlHelper::parseList('1.2.3.4,5.6.7.8'));
    }

    public function test_parse_list_splits_on_newlines(): void
    {
        $this->assertSame(['1.2.3.4', '5.6.7.8'], IpAccessControlHelper::parseList("1.2.3.4\n5.6.7.8"));
    }

    public function test_parse_list_handles_mixed_separators_and_whitespace(): void
    {
        $this->assertSame(
            ['1.2.3.4', '5.6.7.8', '9.9.9.9'],
            IpAccessControlHelper::parseList(" 1.2.3.4 ,\n 5.6.7.8 ;9.9.9.9\r\n"),
        );
    }

    public function test_parse_list_deduplicates_entries(): void
    {
        $this->assertSame(['1.2.3.4'], IpAccessControlHelper::parseList('1.2.3.4, 1.2.3.4'));
    }

    public function test_parse_list_returns_empty_for_blank_input(): void
    {
        $this->assertSame([], IpAccessControlHelper::parseList(null));
        $this->assertSame([], IpAccessControlHelper::parseList('   '));
        $this->assertSame([], IpAccessControlHelper::parseList(',, ;'));
    }

    public function test_list_contains_ip(): void
    {
        $this->assertTrue(IpAccessControlHelper::listContainsIp('5.6.7.8', "1.2.3.4\n5.6.7.8"));
        $this->assertFalse(IpAccessControlHelper::listContainsIp('9.9.9.9', '1.2.3.4,5.6.7.8'));
        $this->assertFalse(IpAccessControlHelper::listContainsIp(null, '1.2.3.4'));
    }

    public function test_inspect_connection_flags_proxy_issue_when_forwarded_header_untrusted(): void
    {
        config(['trustedproxy.proxies' => []]);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '172.19.0.10']);
        $request->headers->set('X-Forwarded-For', '203.0.113.7');

        $result = IpAccessControlHelper::inspectConnection($request);

        $this->assertTrue($result['proxy_issue']);
        $this->assertFalse($result['trusted_proxies_configured']);
        $this->assertSame('172.19.0.10', $result['remote_addr']);
        $this->assertSame('172.19.0.10', $result['suggested_trusted_proxies']);
    }

    public function test_inspect_connection_reports_no_issue_when_trusted_proxies_configured(): void
    {
        config(['trustedproxy.proxies' => ['172.19.0.0/16']]);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '172.19.0.10']);
        $request->headers->set('X-Forwarded-For', '203.0.113.7');

        $result = IpAccessControlHelper::inspectConnection($request);

        $this->assertFalse($result['proxy_issue']);
        $this->assertTrue($result['trusted_proxies_configured']);
        $this->assertNull($result['suggested_trusted_proxies']);
    }

    public function test_inspect_connection_reports_no_issue_for_direct_request(): void
    {
        config(['trustedproxy.proxies' => []]);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.7']);

        $result = IpAccessControlHelper::inspectConnection($request);

        $this->assertFalse($result['proxy_issue']);
        $this->assertNull($result['suggested_trusted_proxies']);
    }

    public function test_list_contains_ip_matches_cidr_range(): void
    {
        $this->assertTrue(IpAccessControlHelper::listContainsIp('192.168.1.50', '192.168.1.0/24'));
        $this->assertTrue(IpAccessControlHelper::listContainsIp('10.0.0.1', '10.0.0.0/8'));
        $this->assertFalse(IpAccessControlHelper::listContainsIp('192.168.2.1', '192.168.1.0/24'));
    }

    public function test_list_contains_ip_matches_ipv6(): void
    {
        $this->assertTrue(IpAccessControlHelper::listContainsIp('2001:db8::1', '2001:db8::/32'));
        $this->assertFalse(IpAccessControlHelper::listContainsIp('fe80::1', '2001:db8::/32'));
    }

    public function test_list_contains_ip_handles_mixed_list(): void
    {
        $list = "192.168.1.1\n10.0.0.0/8\n2001:db8::/32";

        $this->assertTrue(IpAccessControlHelper::listContainsIp('192.168.1.1', $list));
        $this->assertTrue(IpAccessControlHelper::listContainsIp('10.5.5.5', $list));
        $this->assertTrue(IpAccessControlHelper::listContainsIp('2001:db8::ffff', $list));
        $this->assertFalse(IpAccessControlHelper::listContainsIp('1.2.3.4', $list));
    }

    public function test_ip_matches_any_returns_false_for_empty_list_or_null_ip(): void
    {
        $this->assertFalse(IpAccessControlHelper::ipMatchesAny('1.2.3.4', []));
        $this->assertFalse(IpAccessControlHelper::ipMatchesAny(null, ['1.2.3.4']));
    }

    public function test_is_valid_ip_or_cidr_accepts_valid(): void
    {
        $this->assertTrue(IpAccessControlHelper::isValidIpOrCidr('1.2.3.4'));
        $this->assertTrue(IpAccessControlHelper::isValidIpOrCidr('192.168.1.0/24'));
        $this->assertTrue(IpAccessControlHelper::isValidIpOrCidr('2001:db8::1'));
        $this->assertTrue(IpAccessControlHelper::isValidIpOrCidr('2001:db8::/32'));
        $this->assertTrue(IpAccessControlHelper::isValidIpOrCidr('0.0.0.0/0'));
        $this->assertTrue(IpAccessControlHelper::isValidIpOrCidr('::/0'));
    }

    public function test_is_valid_ip_or_cidr_rejects_invalid(): void
    {
        $this->assertFalse(IpAccessControlHelper::isValidIpOrCidr('not_an_ip'));
        $this->assertFalse(IpAccessControlHelper::isValidIpOrCidr('1.2.3.4/33'));
        $this->assertFalse(IpAccessControlHelper::isValidIpOrCidr('2001:db8::/129'));
        $this->assertFalse(IpAccessControlHelper::isValidIpOrCidr(''));
        $this->assertFalse(IpAccessControlHelper::isValidIpOrCidr('1.2.3'));
        $this->assertFalse(IpAccessControlHelper::isValidIpOrCidr('1.2.3.4/abc'));
    }

    public function test_invalid_entries_returns_only_bad_values(): void
    {
        $this->assertSame([], IpAccessControlHelper::invalidEntries(null));
        $this->assertSame([], IpAccessControlHelper::invalidEntries(''));
        $this->assertSame([], IpAccessControlHelper::invalidEntries('1.2.3.4, 5.6.7.0/24, 2001:db8::/32'));
        $this->assertSame(
            ['foo', '1.2.3.4/99'],
            IpAccessControlHelper::invalidEntries("1.2.3.4\nfoo\n5.6.7.0/24\n1.2.3.4/99"),
        );
    }
}
