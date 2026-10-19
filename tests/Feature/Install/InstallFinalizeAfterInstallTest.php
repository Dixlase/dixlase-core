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

namespace Tests\Feature\Install;

use App\Services\Install\InstallFinalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A double click on the completion screen sends finalize twice. The first
 * request writes INSTALLED=true, so the second reaches CheckInstallationReady
 * as an /install request on an installed site, and the browser follows only
 * that second response. It used to go to the front page whatever button was
 * pressed; it now follows its own redirect_to when that stays on this host.
 *
 * INSTALLED is set in-process only and restored in tearDown(); the finalizer
 * is mocked so this test can never write to a real .env.
 */
class InstallFinalizeAfterInstallTest extends TestCase
{
    use RefreshDatabase;

    private array $envSnapshot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envSnapshot = [
            'env' => getenv('INSTALLED'),
            'env_array_set' => array_key_exists('INSTALLED', $_ENV),
            'env_array' => array_key_exists('INSTALLED', $_ENV) ? (string) $_ENV['INSTALLED'] : null,
            'server_set' => array_key_exists('INSTALLED', $_SERVER),
            'server' => array_key_exists('INSTALLED', $_SERVER) ? (string) $_SERVER['INSTALLED'] : null,
        ];

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $this->mock(InstallFinalizer::class, function ($mock): void {
            $mock->shouldNotReceive('finalize');
        });
    }

    protected function tearDown(): void
    {
        if ($this->envSnapshot['env'] === false) {
            putenv('INSTALLED');
        } else {
            putenv('INSTALLED='.$this->envSnapshot['env']);
        }

        if ($this->envSnapshot['env_array_set']) {
            $_ENV['INSTALLED'] = $this->envSnapshot['env_array'];
        } else {
            unset($_ENV['INSTALLED']);
        }

        if ($this->envSnapshot['server_set']) {
            $_SERVER['INSTALLED'] = $this->envSnapshot['server'];
        } else {
            unset($_SERVER['INSTALLED']);
        }

        parent::tearDown();
    }

    public static function sameHostTargets(): array
    {
        return [
            'admin login URL' => ['http://localhost/admin-custom/login'],
            'site URL' => ['http://localhost'],
            'https on the same host' => ['https://localhost/admin-custom/login'],
            'path only' => ['/admin-custom/login'],
        ];
    }

    #[DataProvider('sameHostTargets')]
    public function test_a_repeated_finalize_follows_a_same_host_redirect_to(string $target): void
    {
        $this->post('/install/finalize', ['redirect_to' => $target])->assertRedirect($target);
    }

    public static function refusedTargets(): array
    {
        return [
            'missing' => [null],
            'another host' => ['https://example.com/login'],
            'another host behind userinfo' => ['https://localhost@example.com/login'],
            'protocol-relative' => ['//example.com/login'],
            'backslash' => ['/\\example.com/login'],
            'javascript scheme' => ['javascript:alert(1)'],
        ];
    }

    #[DataProvider('refusedTargets')]
    public function test_a_repeated_finalize_goes_to_the_front_page_otherwise(?string $target): void
    {
        $this->post('/install/finalize', array_filter(['redirect_to' => $target]))->assertRedirect('/');
    }

    public function test_other_install_requests_still_go_to_the_front_page(): void
    {
        $this->get('/install/complete?redirect_to=/admin-custom/login')->assertRedirect('/');
        $this->post('/install/confirm', ['redirect_to' => '/admin-custom/login'])->assertRedirect('/');
    }
}
