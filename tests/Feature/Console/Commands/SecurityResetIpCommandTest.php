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

namespace Tests\Feature\Console\Commands;

use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityResetIpCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression: --disable-all used to crash on the audit log call because
     * AuditService::log() was being invoked statically with named arguments
     * (the method is non-static and accepts a single array). The command
     * must now run end-to-end via the Audit facade and actually clear the
     * IP restriction flags.
     */
    public function test_disable_all_clears_ip_restrictions_without_audit_dispatch_error(): void
    {
        SecuritySetting::set('enable_allowed_admin_ips', '1');
        SecuritySetting::set('allowed_admin_ips', '203.0.113.50');
        SecuritySetting::set('enable_blocked_admin_ips', '1');
        SecuritySetting::set('enable_allowed_front_ips', '1');
        SecuritySetting::set('enable_blocked_front_ips', '1');

        $this->artisan('security:reset-ip', [
            '--disable-all' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertFalse((bool) SecuritySetting::get('enable_allowed_admin_ips'));
        $this->assertFalse((bool) SecuritySetting::get('enable_blocked_admin_ips'));
        $this->assertFalse((bool) SecuritySetting::get('enable_allowed_front_ips'));
        $this->assertFalse((bool) SecuritySetting::get('enable_blocked_front_ips'));
    }

    public function test_add_ip_accepts_cidr_range(): void
    {
        SecuritySetting::set('allowed_admin_ips', '');

        $this->artisan('security:reset-ip', [
            '--add-ip' => '192.168.1.0/24',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertSame('192.168.1.0/24', (string) SecuritySetting::get('allowed_admin_ips'));
    }

    public function test_add_ip_appends_to_existing_newline_separated_list(): void
    {
        SecuritySetting::set('allowed_admin_ips', "10.0.0.1\n10.0.0.2");

        $this->artisan('security:reset-ip', [
            '--add-ip' => '10.0.0.3',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertSame(
            "10.0.0.1\n10.0.0.2\n10.0.0.3",
            (string) SecuritySetting::get('allowed_admin_ips'),
        );
    }

    public function test_add_ip_rejects_invalid_value(): void
    {
        $this->artisan('security:reset-ip', [
            '--add-ip' => 'not_an_ip',
            '--force' => true,
        ])->assertExitCode(1);
    }

    public function test_remove_blocked_handles_newline_separated_list(): void
    {
        SecuritySetting::set('blocked_admin_ips', "10.0.0.1\n10.0.0.2\n10.0.0.3");

        $this->artisan('security:reset-ip', [
            '--remove-blocked' => '10.0.0.2',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertSame(
            "10.0.0.1\n10.0.0.3",
            (string) SecuritySetting::get('blocked_admin_ips'),
        );
    }
}
