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

namespace Tests\Feature\Admin;

use App\Http\Middleware\CheckInstallationReady;
use App\Models\Member;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class IpFilterMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            \App\Http\Middleware\EnsureEmailIsVerified::class,
            \App\Http\Middleware\CheckMenuAccess::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        \Illuminate\Support\Facades\View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);

        // テスト環境をproductionに設定（localではIP制限がスキップされるため）
        app()->detectEnvironment(fn () => 'production');
    }

    protected function tearDown(): void
    {
        // 環境を元に戻す
        app()->detectEnvironment(fn () => 'testing');
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        unset($_SERVER['INSTALLED']);
        parent::tearDown();
    }

    /**
     * IP制限が無効の場合はアクセス可能
     */
    public function test_access_allowed_when_ip_filter_disabled(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '0');
        SecuritySetting::set('enable_blocked_admin_ips', '0');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    /**
     * 許可リストに含まれるIPはアクセス可能
     */
    public function test_access_allowed_when_ip_in_allowlist(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '1');
        SecuritySetting::set('allowed_admin_ips', '127.0.0.1,192.168.1.1');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    /**
     * 許可リストに含まれないIPはアクセス拒否
     */
    public function test_access_denied_when_ip_not_in_allowlist(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '1');
        SecuritySetting::set('allowed_admin_ips', '192.168.1.100');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(403);
    }

    /**
     * ブロックリストに含まれるIPはアクセス拒否
     */
    public function test_access_denied_when_ip_in_blocklist(): void
    {
        SecuritySetting::set('enable_blocked_admin_ips', '1');
        SecuritySetting::set('blocked_admin_ips', '127.0.0.1');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(403);
    }

    /**
     * ブロックリストに含まれないIPはアクセス可能
     */
    public function test_access_allowed_when_ip_not_in_blocklist(): void
    {
        SecuritySetting::set('enable_blocked_admin_ips', '1');
        SecuritySetting::set('blocked_admin_ips', '10.0.0.1,10.0.0.2');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    /**
     * 許可リストとブロックリストの両方が有効な場合、ブロックリストが優先
     */
    public function test_blocklist_takes_priority_over_allowlist(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '1');
        SecuritySetting::set('allowed_admin_ips', '127.0.0.1');
        SecuritySetting::set('enable_blocked_admin_ips', '1');
        SecuritySetting::set('blocked_admin_ips', '127.0.0.1');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(403);
    }

    /**
     * 空の許可リストが有効な場合、全てのIPがブロック
     */
    public function test_empty_allowlist_blocks_all(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '1');
        SecuritySetting::set('allowed_admin_ips', '');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(403);
    }

    /**
     * 認証済みユーザーもIP制限の対象
     */
    public function test_authenticated_user_also_subject_to_ip_filter(): void
    {
        $member = Member::factory()->create();

        SecuritySetting::set('enable_blocked_admin_ips', '1');
        SecuritySetting::set('blocked_admin_ips', '127.0.0.1');

        $response = $this->actingAs($member)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    /**
     * 複数IPの許可リストが正しく機能する
     */
    public function test_multiple_ips_in_allowlist(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '1');
        SecuritySetting::set('allowed_admin_ips', '10.0.0.1,127.0.0.1,192.168.1.1');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    /**
     * Whitespace around comma-separated allowlist IPs is trimmed before matching.
     *
     * The request IP (127.0.0.1) is the second entry with surrounding spaces;
     * it must still match after trimming.
     */
    public function test_whitespace_in_allowlist_handled(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '1');
        SecuritySetting::set('allowed_admin_ips', ' 192.168.1.1 , 127.0.0.1 ');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    /**
     * Whitespace around comma-separated blocklist IPs is trimmed before matching.
     *
     * The request IP (127.0.0.1) is the second entry with a leading space;
     * it must still be blocked after trimming.
     */
    public function test_whitespace_in_blocklist_handled(): void
    {
        SecuritySetting::set('enable_blocked_admin_ips', '1');
        SecuritySetting::set('blocked_admin_ips', '10.0.0.1, 127.0.0.1');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(403);
    }

    /**
     * A denied request is logged with the client IP the application observed.
     *
     * This is the diagnostic signal operators rely on to distinguish a
     * trusted-proxy misconfiguration from a genuine allowlist mismatch.
     */
    public function test_denied_request_is_logged_with_client_ip(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '1');
        SecuritySetting::set('allowed_admin_ips', '192.168.1.100');

        Log::spy();

        $response = $this->get(route('admin.login'));

        $response->assertStatus(403);

        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Admin IP filter denied access'
                    && $context['reason'] === 'allowlist'
                    && $context['client_ip'] === '127.0.0.1';
            });
    }

    /**
     * A CIDR range that covers the client IP matches the allowlist.
     */
    public function test_access_allowed_when_ip_in_cidr_allowlist(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '1');
        SecuritySetting::set('allowed_admin_ips', '127.0.0.0/24');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    /**
     * A CIDR allowlist that does not cover the client IP correctly rejects.
     */
    public function test_access_denied_when_cidr_allowlist_excludes_ip(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '1');
        SecuritySetting::set('allowed_admin_ips', '10.0.0.0/8');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(403);
    }

    /**
     * A CIDR range that covers the client IP matches the blocklist.
     */
    public function test_access_denied_when_ip_in_cidr_blocklist(): void
    {
        SecuritySetting::set('enable_blocked_admin_ips', '1');
        SecuritySetting::set('blocked_admin_ips', '127.0.0.0/24');

        $response = $this->get(route('admin.login'));

        $response->assertStatus(403);
    }
}
