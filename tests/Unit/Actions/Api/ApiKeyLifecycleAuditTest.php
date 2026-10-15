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

declare(strict_types=1);

namespace Tests\Unit\Actions\Api;

use App\Actions\Api\GenerateApiKeyAction;
use App\Actions\Api\RegenerateApiKeyAction;
use App\Actions\Api\RevokeApiKeyAction;
use App\Actors\MemberActor;
use App\Enums\MemberRole;
use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Core #476: the API key lifecycle was recorded only in the admin_activity
 * file log, so the audit trail had no record of a credential being created,
 * revoked or replaced.
 *
 * The assertions that matter are: one row per operation, the actor on the row,
 * and no key material anywhere in it.
 */
class ApiKeyLifecycleAuditTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = Member::factory()->create([
            'account_name' => 'keyadmin',
            'display_name' => 'Key Admin',
            'email' => 'keyadmin@example.test',
            'role' => MemberRole::SUPER_ADMIN,
        ]);
    }

    private function actor(?MemberRole $role = null): MemberActor
    {
        if ($role === null) {
            return new MemberActor($this->superAdmin);
        }

        return new MemberActor(Member::factory()->create([
            'account_name' => 'editor',
            'display_name' => 'Editor',
            'email' => 'editor@example.test',
            'role' => $role,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function keyInput(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Deploy key',
            'environment' => ApiKey::ENV_TEST,
            'scopes' => ['content:read'],
            'rate_limit' => 120,
            'allowed_ips' => '203.0.113.4, 203.0.113.5',
            'expires_at' => null,
            'description' => 'for the test suite',
        ], $overrides);
    }

    private function auditRow(string $action): AuditLog
    {
        return AuditLog::where('action', $action)->sole();
    }

    public function test_creating_a_key_writes_a_row_with_the_actor_and_the_key_identity(): void
    {
        $result = (new GenerateApiKeyAction())->execute($this->actor(), $this->keyInput());

        $row = $this->auditRow(AuditLog::ACTION_API_KEY_CREATED);

        $this->assertSame(AuditLog::CATEGORY_SYSTEM, $row->category);
        $this->assertSame($this->superAdmin->id, $row->actor_id);
        $this->assertSame(Member::class, $row->actor_type);
        $this->assertSame(ApiKey::class, $row->target_type);
        $this->assertSame($result->model->id, (int) $row->target_id);
        $this->assertSame('Deploy key', $row->target_label);

        $this->assertSame('Deploy key', $row->context['name']);
        $this->assertSame(ApiKey::ENV_TEST, $row->context['environment']);
        $this->assertSame(['content:read'], $row->context['scopes']);
        $this->assertSame(120, $row->context['rate_limit']);
        $this->assertSame(['203.0.113.4', '203.0.113.5'], $row->context['allowed_ips']);
    }

    public function test_no_key_material_reaches_the_audit_row(): void
    {
        $result = (new GenerateApiKeyAction())->execute($this->actor(), $this->keyInput());

        $plainKey = $result->metadata['plain_key'];
        $this->assertIsString($plainKey);

        /** @var ApiKey $key */
        $key = $result->model;

        $row = $this->auditRow(AuditLog::ACTION_API_KEY_CREATED);
        $serialised = json_encode($row->toArray());

        $this->assertStringNotContainsString($plainKey, $serialised);
        $this->assertStringNotContainsString($key->key_hash, $serialised);
        $this->assertArrayNotHasKey('plain_key', $row->context);
        $this->assertArrayNotHasKey('key_hash', $row->context);
    }

    public function test_revoking_a_key_writes_a_row_and_deletes_the_key(): void
    {
        $created = (new GenerateApiKeyAction())->execute($this->actor(), $this->keyInput());
        /** @var ApiKey $key */
        $key = $created->model;

        (new RevokeApiKeyAction())->execute($this->actor(), ['api_key' => $key]);

        $this->assertDatabaseMissing('api_keys', ['id' => $key->id]);

        $row = $this->auditRow(AuditLog::ACTION_API_KEY_REVOKED);
        $this->assertSame($key->id, $row->context['api_key_id']);
        $this->assertSame('Deploy key', $row->context['name']);
        $this->assertSame($this->superAdmin->id, $row->actor_id);
    }

    public function test_regenerating_a_key_records_both_ids(): void
    {
        $created = (new GenerateApiKeyAction())->execute($this->actor(), $this->keyInput());
        /** @var ApiKey $old */
        $old = $created->model;

        $result = (new RegenerateApiKeyAction())->execute($this->actor(), ['api_key' => $old]);
        /** @var ApiKey $new */
        $new = $result->model;

        $this->assertDatabaseMissing('api_keys', ['id' => $old->id]);
        $this->assertDatabaseHas('api_keys', ['id' => $new->id]);
        $this->assertNotSame($old->key_hash, $new->key_hash);

        $row = $this->auditRow(AuditLog::ACTION_API_KEY_REGENERATED);
        $this->assertSame($old->id, $row->context['old_api_key_id']);
        $this->assertSame($new->id, $row->context['new_api_key_id']);
        $this->assertSame('Deploy key', $row->context['name']);
    }

    public function test_regenerating_carries_the_settings_over(): void
    {
        $created = (new GenerateApiKeyAction())->execute($this->actor(), $this->keyInput());
        /** @var ApiKey $old */
        $old = $created->model;

        $result = (new RegenerateApiKeyAction())->execute($this->actor(), ['api_key' => $old]);
        /** @var ApiKey $new */
        $new = $result->model;

        $this->assertSame($old->name, $new->name);
        $this->assertSame($old->environment, $new->environment);
        $this->assertSame($old->scopes, $new->scopes);
        $this->assertSame($old->rate_limit, $new->rate_limit);
        $this->assertSame($old->allowed_ips, $new->allowed_ips);
    }

    public function test_an_empty_allowed_ips_field_becomes_null(): void
    {
        $result = (new GenerateApiKeyAction())->execute(
            $this->actor(),
            $this->keyInput(['allowed_ips' => '  ']),
        );

        $this->assertNull($result->model->allowed_ips);
        $this->assertSame([], $this->auditRow(AuditLog::ACTION_API_KEY_CREATED)->context['allowed_ips']);
    }

    public function test_an_editor_cannot_create_a_key(): void
    {
        $this->expectException(AuthorizationException::class);

        (new GenerateApiKeyAction())->execute($this->actor(MemberRole::EDITOR), $this->keyInput());
    }

    public function test_an_editor_cannot_revoke_a_key(): void
    {
        $created = (new GenerateApiKeyAction())->execute($this->actor(), $this->keyInput());

        $this->expectException(AuthorizationException::class);

        (new RevokeApiKeyAction())->execute(
            $this->actor(MemberRole::EDITOR),
            ['api_key' => $created->model],
        );
    }
}
