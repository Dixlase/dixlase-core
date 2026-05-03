<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace Tests\Feature\PluginPrivacy;

use App\Enums\PluginPrivacy\DeletionMode;
use App\Models\Member;
use App\Models\Site;
use App\Services\Privacy\Providers\CoreMemberPrivacyProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoreMemberPrivacyProviderTest extends TestCase
{
    use RefreshDatabase;

    private CoreMemberPrivacyProvider $provider;

    private Member $member;

    private int $primarySiteId;

    private int $secondarySiteId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = $this->app->make(CoreMemberPrivacyProvider::class);

        $primary = Site::factory()->primary()->create([
            'slug' => 'primary',
            'name' => 'Primary Site',
        ]);
        $secondary = Site::factory()->create([
            'slug' => 'secondary',
            'name' => 'Secondary Site',
        ]);

        $this->primarySiteId = (int) $primary->id;
        $this->secondarySiteId = (int) $secondary->id;

        $this->member = Member::factory()->create([
            'email' => 'subject@example.com',
            'account_name' => 'subject',
            'display_name' => 'Original Display',
        ]);

        DB::table('members_login_attempts')->insert([
            'identifier' => 'subject@example.com',
            'ip_address' => '203.0.113.1',
            'user_agent' => 'TestAgent/1.0',
            'member_id' => $this->member->id,
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('audit_logs')->insert([
            ['site_id' => $this->primarySiteId, 'occurred_at' => now(), 'category' => 'auth', 'action' => 'login', 'actor_type' => Member::class, 'actor_id' => $this->member->id, 'actor_name' => 'Original Display', 'ip_address' => '203.0.113.10', 'user_agent' => 'TestUA', 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => $this->secondarySiteId, 'occurred_at' => now(), 'category' => 'auth', 'action' => 'login', 'actor_type' => Member::class, 'actor_id' => $this->member->id, 'actor_name' => 'Original Display', 'ip_address' => '203.0.113.11', 'user_agent' => 'TestUA', 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => null, 'occurred_at' => now(), 'category' => 'system', 'action' => 'noop', 'actor_type' => Member::class, 'actor_id' => $this->member->id, 'actor_name' => 'Original Display', 'ip_address' => '203.0.113.12', 'user_agent' => 'TestUA', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('security_events')->insert([
            ['site_id' => $this->primarySiteId, 'member_id' => $this->member->id, 'event_type' => 'failed_login', 'category' => 'auth', 'risk_level' => 'low', 'ip_address' => '198.51.100.5', 'user_agent' => 'TestUA', 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => $this->secondarySiteId, 'member_id' => $this->member->id, 'event_type' => 'failed_login', 'category' => 'auth', 'risk_level' => 'low', 'ip_address' => '198.51.100.6', 'user_agent' => 'TestUA', 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_network_wide_export_includes_global_and_all_site_scoped_data(): void
    {
        $dto = $this->provider->exportUserData((int) $this->member->id, null);

        $this->assertSame('core-members', $dto->providerKey);
        $this->assertSame([], $dto->warnings);
        $this->assertNotNull($dto->data['member']);
        $this->assertSame('subject@example.com', $dto->data['member']['email']);

        $this->assertCount(1, $dto->data['members_login_attempts']);
        $this->assertCount(3, $dto->data['audit_logs']);
        $this->assertCount(2, $dto->data['security_events']);
    }

    public function test_site_scoped_export_omits_global_tables_and_filters_by_site(): void
    {
        $dto = $this->provider->exportUserData((int) $this->member->id, $this->primarySiteId);

        $this->assertArrayNotHasKey('member', $dto->data);
        $this->assertArrayNotHasKey('members_login_attempts', $dto->data);
        $this->assertNotEmpty($dto->warnings);
        $this->assertStringContainsString('Site-scoped export', $dto->warnings[0]);

        $this->assertCount(1, $dto->data['audit_logs']);
        $this->assertSame($this->primarySiteId, (int) $dto->data['audit_logs'][0]['site_id']);

        $this->assertCount(1, $dto->data['security_events']);
        $this->assertSame($this->primarySiteId, (int) $dto->data['security_events'][0]['site_id']);
    }

    public function test_anonymize_replaces_pii_in_place_and_is_irreversible(): void
    {
        $result = $this->provider->deleteUserData(
            (int) $this->member->id,
            DeletionMode::Anonymize,
            null,
        );

        $this->assertSame(DeletionMode::Anonymize, $result->mode);
        $this->assertSame(0, $result->deletedRecords);
        $this->assertGreaterThan(0, $result->anonymizedRecords);
        $this->assertSame([], $result->errors);

        $member = DB::table('members')->where('id', $this->member->id)->first();
        $this->assertNotNull($member);
        $this->assertNotSame('subject@example.com', $member->email);
        $this->assertStringEndsWith('@anonymized.invalid', $member->email);
        $this->assertNotSame('subject', $member->account_name);
        $this->assertStringStartsWith('anonymized_', $member->account_name);

        $auditPii = DB::table('audit_logs')
            ->where('actor_type', Member::class)
            ->where('actor_id', $this->member->id)
            ->pluck('ip_address');
        foreach ($auditPii as $ip) {
            $this->assertNotSame('203.0.113.10', $ip);
            $this->assertNotSame('203.0.113.11', $ip);
        }
    }

    public function test_anonymize_is_deterministic_for_the_same_input(): void
    {
        $reflection = new \ReflectionClass(CoreMemberPrivacyProvider::class);
        $method = $reflection->getMethod('hashOrNull');
        $method->setAccessible(true);

        $hash1 = $method->invoke($this->provider, '203.0.113.1');
        $hash2 = $method->invoke($this->provider, '203.0.113.1');
        $hashDifferent = $method->invoke($this->provider, '203.0.113.2');

        $this->assertSame($hash1, $hash2);
        $this->assertNotSame($hash1, $hashDifferent);
        $this->assertNull($method->invoke($this->provider, ''));
        $this->assertSame(32, strlen((string) $hash1));
    }

    public function test_hard_delete_removes_member_and_child_records_but_preserves_audit_logs(): void
    {
        $result = $this->provider->deleteUserData(
            (int) $this->member->id,
            DeletionMode::HardDelete,
            null,
        );

        $this->assertSame(DeletionMode::HardDelete, $result->mode);
        $this->assertGreaterThan(0, $result->deletedRecords);
        $this->assertGreaterThan(0, $result->anonymizedRecords);
        $this->assertSame([], $result->errors);

        $this->assertNull(DB::table('members')->where('id', $this->member->id)->first());
        $this->assertSame(0, DB::table('members_login_attempts')->where('member_id', $this->member->id)->count());

        // Audit chain preserved with anonymized PII.
        $auditCount = DB::table('audit_logs')
            ->where('actor_type', Member::class)
            ->where('actor_id', $this->member->id)
            ->count();
        $this->assertSame(3, $auditCount);
    }

    public function test_soft_delete_sets_deleted_at_only_on_member_row(): void
    {
        $result = $this->provider->deleteUserData(
            (int) $this->member->id,
            DeletionMode::SoftDelete,
            null,
        );

        $this->assertSame(DeletionMode::SoftDelete, $result->mode);
        $this->assertSame(1, $result->deletedRecords);

        $member = DB::table('members')->where('id', $this->member->id)->first();
        $this->assertNotNull($member);
        $this->assertNotNull($member->deleted_at);

        // Children remain so a future undelete keeps the relationship intact.
        $this->assertSame(1, DB::table('members_login_attempts')->where('member_id', $this->member->id)->count());
    }

    public function test_site_scoped_deletion_only_touches_audit_and_security_rows_on_that_site(): void
    {
        $result = $this->provider->deleteUserData(
            (int) $this->member->id,
            DeletionMode::HardDelete,
            $this->primarySiteId,
        );

        $this->assertSame(0, $result->deletedRecords);
        $this->assertGreaterThan(0, $result->anonymizedRecords);

        // Member row untouched.
        $member = DB::table('members')->where('id', $this->member->id)->first();
        $this->assertSame('subject@example.com', $member->email);

        // Secondary site audit log PII preserved.
        $secondaryAudit = DB::table('audit_logs')
            ->where('site_id', $this->secondarySiteId)
            ->where('actor_id', $this->member->id)
            ->first();
        $this->assertSame('203.0.113.11', $secondaryAudit->ip_address);

        // Primary site audit log PII anonymized.
        $primaryAudit = DB::table('audit_logs')
            ->where('site_id', $this->primarySiteId)
            ->where('actor_id', $this->member->id)
            ->first();
        $this->assertNotSame('203.0.113.10', $primaryAudit->ip_address);
    }
}
