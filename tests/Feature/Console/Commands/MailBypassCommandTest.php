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

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailBypassCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression: enable bypass used to crash on the audit log call because
     * AuditService::log() was being invoked statically with named arguments.
     */
    public function test_enable_runs_without_audit_dispatch_error(): void
    {
        $this->artisan('security:mail-bypass', [
            'action' => 'enable',
            '--reason' => 'test',
        ])
            ->expectsConfirmation(__('admin/command.mail_bypass.confirm_enable'), 'yes')
            ->assertExitCode(0);
    }
}
