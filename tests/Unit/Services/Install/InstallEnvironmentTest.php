<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Unit\Services\Install;

use App\Services\Install\InstallEnvironment;
use Tests\TestCase;

/**
 * InstallEnvironment decides how the setup wizard treats this install
 * (dixlase-core#883): DIXLASE_INSTALL_METHOD first, the Docker service host
 * names only when the installer declared nothing.
 */
class InstallEnvironmentTest extends TestCase
{
    public function test_a_declared_method_is_read_and_normalised(): void
    {
        config(['dixlase.install_method' => ' Docker ']);

        $this->assertSame('docker', $this->environment()->method());
    }

    public function test_an_unknown_or_missing_method_counts_as_none(): void
    {
        config(['dixlase.install_method' => 'zip']);
        $this->assertNull($this->environment()->method());

        config(['dixlase.install_method' => null]);
        $this->assertNull($this->environment()->method());
    }

    public function test_docker_is_detected_from_the_declared_method(): void
    {
        config(['dixlase.install_method' => 'docker']);
        $env = $this->environment(resolves: []);

        $this->assertTrue($env->isDocker());
        $this->assertTrue($env->hasMailpit());
    }

    public function test_a_declared_non_docker_method_wins_over_the_host_names(): void
    {
        // A host that happens to resolve `mysql` must not be taken for Docker
        // once the installer has said how Dixlase was installed.
        foreach (['oneliner', 'composer'] as $method) {
            config(['dixlase.install_method' => $method]);
            $env = $this->environment(resolves: ['mysql', 'mailpit']);

            $this->assertFalse($env->isDocker(), $method);
            $this->assertFalse($env->hasMailpit(), $method);
        }
    }

    public function test_without_a_method_the_host_names_decide(): void
    {
        config(['dixlase.install_method' => null]);

        $docker = $this->environment(resolves: ['mysql', 'mailpit']);
        $this->assertTrue($docker->isDocker());
        $this->assertTrue($docker->hasMailpit());

        $bare = $this->environment(resolves: []);
        $this->assertFalse($bare->isDocker());
        $this->assertFalse($bare->hasMailpit());
    }

    public function test_the_mailpit_url_falls_back_to_the_current_host_on_port_8025(): void
    {
        config(['dixlase.mailpit_url' => null]);
        $env = $this->environment();

        $this->assertSame('http://localhost:8025', $env->mailpitUrl('http', 'localhost'));
        $this->assertSame('https://dixlase.test:8025', $env->mailpitUrl('https', 'dixlase.test'));
        $this->assertSame('http://[::1]:8025', $env->mailpitUrl('http', '::1'));
        $this->assertSame('http://localhost:8025', $env->mailpitUrl('ftp', ''));
    }

    public function test_the_installer_can_name_the_mailpit_url(): void
    {
        config(['dixlase.mailpit_url' => 'http://localhost:40025/']);

        $this->assertSame('http://localhost:40025', $this->environment()->mailpitUrl('http', 'example.com'));
    }

    public function test_a_mailpit_url_that_is_not_http_is_ignored(): void
    {
        // The value becomes a link, so a javascript: URL must never reach it.
        foreach (['javascript:alert(1)', 'not a url', 'ftp://localhost:8025'] as $value) {
            config(['dixlase.mailpit_url' => $value]);

            $this->assertSame('http://localhost:8025', $this->environment()->mailpitUrl('http', 'localhost'), $value);
        }
    }

    /**
     * @param  list<string>  $resolves  Host names the fake resolver answers for
     */
    private function environment(array $resolves = []): InstallEnvironment
    {
        return new class($resolves) extends InstallEnvironment
        {
            /** @param list<string> $resolves */
            public function __construct(private array $resolves) {}

            protected function hostResolves(string $host): bool
            {
                return in_array($host, $this->resolves, true);
            }
        };
    }
}
