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

namespace Tests\Feature\Console\Api;

use App\Models\ApiKey;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateNetworkApiKeyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_network_key_with_null_site_id_and_critical_audit_log(): void
    {
        $this->artisan('dls:api:create-network-key', [
            '--name' => 'Test Network Key',
            '--scopes' => [ApiKey::SCOPE_READ_CONTENT, ApiKey::SCOPE_WRITE_CONTENT],
            '--environment' => 'live',
            '--description' => 'fixture',
            '--force' => true,
        ])->assertExitCode(0);

        $key = ApiKey::query()->withoutGlobalScope('belongs_to_site')->first();

        $this->assertNotNull($key);
        $this->assertNull($key->site_id, 'Network key must have site_id = null');
        $this->assertSame('Test Network Key', $key->name);
        $this->assertSame('live', $key->environment);
        $this->assertEqualsCanonicalizing(
            [ApiKey::SCOPE_READ_CONTENT, ApiKey::SCOPE_WRITE_CONTENT],
            $key->scopes,
        );
        $this->assertTrue($key->isNetworkKey());

        $audit = AuditLog::query()
            ->where('action', 'network_api_key_created')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame(AuditLog::CATEGORY_SECURITY, $audit->category);
        $this->assertSame(AuditLog::SEVERITY_CRITICAL, $audit->severity);
        $this->assertSame('cli', $audit->context['triggered_by'] ?? null);
        $this->assertSame($key->id, $audit->context['api_key_id'] ?? null);
    }

    public function test_command_rejects_unknown_scope(): void
    {
        $this->artisan('dls:api:create-network-key', [
            '--name' => 'Bad Scope',
            '--scopes' => ['read:content', 'no_such_scope'],
            '--force' => true,
        ])->assertExitCode(1);

        $this->assertSame(
            0,
            ApiKey::query()->withoutGlobalScope('belongs_to_site')->count(),
            'No key should be created when an unknown scope is supplied',
        );
    }

    public function test_command_rejects_invalid_environment(): void
    {
        $this->artisan('dls:api:create-network-key', [
            '--name' => 'Bad Env',
            '--environment' => 'staging',
            '--force' => true,
        ])->assertExitCode(1);

        $this->assertSame(
            0,
            ApiKey::query()->withoutGlobalScope('belongs_to_site')->count(),
            'No key should be created when --environment is invalid',
        );
    }
}
