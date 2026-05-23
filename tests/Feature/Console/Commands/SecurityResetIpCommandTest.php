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
}
