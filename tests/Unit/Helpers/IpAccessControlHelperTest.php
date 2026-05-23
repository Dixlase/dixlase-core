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
}
