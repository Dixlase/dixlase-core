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

declare(strict_types=1);

namespace Tests\Unit\Config;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for config/trustedproxy.php parsing logic.
 *
 * The file is re-evaluated for each scenario so we can drive different
 * TRUSTED_PROXIES env values without booting the full framework.
 */
class TrustedProxyConfigTest extends TestCase
{
    private const CONFIG_PATH = __DIR__.'/../../../config/trustedproxy.php';

    protected function tearDown(): void
    {
        // Clean up env between cases so leakage cannot mask a regression.
        putenv('TRUSTED_PROXIES');
        unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);

        parent::tearDown();
    }

    public function test_unset_env_yields_empty_proxy_list(): void
    {
        $config = $this->loadConfigWithEnv(null);

        $this->assertSame([], $config['proxies']);
    }

    public function test_empty_string_env_yields_empty_proxy_list(): void
    {
        $config = $this->loadConfigWithEnv('');

        $this->assertSame([], $config['proxies']);
    }

    public function test_wildcard_env_yields_wildcard_string(): void
    {
        $config = $this->loadConfigWithEnv('*');

        $this->assertSame('*', $config['proxies']);
    }

    public function test_wildcard_env_with_whitespace_is_recognised(): void
    {
        $config = $this->loadConfigWithEnv('  *  ');

        $this->assertSame('*', $config['proxies']);
    }

    public function test_single_cidr_env_yields_single_element_list(): void
    {
        $config = $this->loadConfigWithEnv('10.0.0.0/8');

        $this->assertSame(['10.0.0.0/8'], $config['proxies']);
    }

    public function test_comma_separated_list_is_split_and_trimmed(): void
    {
        $config = $this->loadConfigWithEnv('10.0.0.0/8, 192.168.0.0/16 ,  172.16.0.0/12');

        $this->assertSame(
            ['10.0.0.0/8', '192.168.0.0/16', '172.16.0.0/12'],
            $config['proxies']
        );
    }

    public function test_empty_segments_are_dropped(): void
    {
        $config = $this->loadConfigWithEnv('10.0.0.0/8,,, 192.168.0.0/16,');

        $this->assertSame(
            ['10.0.0.0/8', '192.168.0.0/16'],
            $config['proxies']
        );
    }

    public function test_headers_bitmask_includes_documented_forwarded_headers(): void
    {
        $config = $this->loadConfigWithEnv(null);

        $expected = Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB;

        $this->assertSame($expected, $config['headers']);
    }

    /**
     * Re-include the config file with the requested TRUSTED_PROXIES env value.
     *
     * @return array{proxies: array<int,string>|string, headers: int}
     */
    private function loadConfigWithEnv(?string $value): array
    {
        if ($value === null) {
            putenv('TRUSTED_PROXIES');
            unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
        } else {
            putenv('TRUSTED_PROXIES='.$value);
            $_ENV['TRUSTED_PROXIES'] = $value;
            $_SERVER['TRUSTED_PROXIES'] = $value;
        }

        // The config file uses Laravel's env() which falls through to
        // getenv()/$_ENV/$_SERVER, so all three are populated above.
        return require self::CONFIG_PATH;
    }
}
