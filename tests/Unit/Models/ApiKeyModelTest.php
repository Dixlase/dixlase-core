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

namespace Tests\Unit\Models;

use App\Contracts\Site\SiteContextInterface;
use App\Models\ApiKey;
use App\Models\Member;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_creates_key_with_plain_text(): void
    {
        $result = ApiKey::generate('Test Key', ApiKey::ENV_LIVE, [ApiKey::SCOPE_READ_CONTENT]);

        $this->assertArrayHasKey('model', $result);
        $this->assertArrayHasKey('plain_key', $result);
        $this->assertInstanceOf(ApiKey::class, $result['model']);
        $this->assertStringStartsWith('dxl_live_', $result['plain_key']);
    }

    public function test_generate_test_key_has_test_prefix(): void
    {
        $result = ApiKey::generate('Test Key', ApiKey::ENV_TEST);

        $this->assertStringStartsWith('dxl_test_', $result['plain_key']);
    }

    public function test_validate_returns_model_for_valid_key(): void
    {
        $result = ApiKey::generate('Test Key');
        $plainKey = $result['plain_key'];

        $validated = ApiKey::validate($plainKey);

        $this->assertNotNull($validated);
        $this->assertEquals($result['model']->id, $validated->id);
    }

    public function test_validate_returns_null_for_invalid_key(): void
    {
        $this->assertNull(ApiKey::validate('dxl_live_nonexistent'));
    }

    public function test_validate_returns_null_for_revoked_key(): void
    {
        $result = ApiKey::generate('Test Key');
        $result['model']->revoke();

        $this->assertNull(ApiKey::validate($result['plain_key']));
    }

    public function test_validate_returns_null_for_expired_key(): void
    {
        $result = ApiKey::generate('Test Key', ApiKey::ENV_LIVE, [], null, [
            'expires_at' => now()->subDay(),
        ]);

        $this->assertNull(ApiKey::validate($result['plain_key']));
    }

    public function test_has_scope_checks_correctly(): void
    {
        $result = ApiKey::generate('Test', ApiKey::ENV_LIVE, [
            ApiKey::SCOPE_READ_CONTENT,
            ApiKey::SCOPE_WRITE_CONTENT,
        ]);

        $key = $result['model'];

        $this->assertTrue($key->hasScope(ApiKey::SCOPE_READ_CONTENT));
        $this->assertTrue($key->hasScope(ApiKey::SCOPE_WRITE_CONTENT));
        $this->assertFalse($key->hasScope(ApiKey::SCOPE_READ_EVENTS));
    }

    public function test_allows_ip_with_empty_list_allows_all(): void
    {
        $result = ApiKey::generate('Test');
        $key = $result['model'];

        $this->assertTrue($key->allowsIp('192.168.1.1'));
        $this->assertTrue($key->allowsIp('10.0.0.1'));
    }

    public function test_allows_ip_with_restricted_list(): void
    {
        $result = ApiKey::generate('Test', ApiKey::ENV_LIVE, [], null, [
            'allowed_ips' => ['192.168.1.1', '10.0.0.1'],
        ]);
        $key = $result['model'];

        $this->assertTrue($key->allowsIp('192.168.1.1'));
        $this->assertFalse($key->allowsIp('172.16.0.1'));
    }

    public function test_is_expired(): void
    {
        $result = ApiKey::generate('Test', ApiKey::ENV_LIVE, [], null, [
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($result['model']->isExpired());
    }

    public function test_is_not_expired_when_no_expiry(): void
    {
        $result = ApiKey::generate('Test');

        $this->assertFalse($result['model']->isExpired());
    }

    public function test_revoke_deactivates_key(): void
    {
        $result = ApiKey::generate('Test');
        $key = $result['model'];

        $key->revoke();
        $key->refresh();

        $this->assertFalse($key->is_active);
    }

    public function test_activate_enables_key(): void
    {
        $result = ApiKey::generate('Test');
        $key = $result['model'];

        $key->revoke();
        $key->activate();
        $key->refresh();

        $this->assertTrue($key->is_active);
    }

    public function test_record_usage_increments_count(): void
    {
        $result = ApiKey::generate('Test');
        $key = $result['model'];

        $this->assertEquals(0, $key->usage_count);

        $key->recordUsage();
        $key->refresh();

        $this->assertEquals(1, $key->usage_count);
        $this->assertNotNull($key->last_used_at);
    }

    public function test_scopes_cast_to_array(): void
    {
        $scopes = [ApiKey::SCOPE_READ_CONTENT, ApiKey::SCOPE_WRITE_CONTENT];
        $result = ApiKey::generate('Test', ApiKey::ENV_LIVE, $scopes);

        $key = ApiKey::find($result['model']->id);

        $this->assertIsArray($key->scopes);
        $this->assertEquals($scopes, $key->scopes);
    }

    public function test_creator_relationship(): void
    {
        $member = Member::factory()->create();
        $result = ApiKey::generate('Test', ApiKey::ENV_LIVE, [], $member->id);

        $key = $result['model'];

        $this->assertNotNull($key->creator);
        $this->assertEquals($member->id, $key->creator->id);
    }

    public function test_active_scope(): void
    {
        ApiKey::generate('Active Key');
        $revoked = ApiKey::generate('Revoked Key');
        $revoked['model']->revoke();

        $activeKeys = ApiKey::active()->get();

        $this->assertEquals(1, $activeKeys->count());
        $this->assertEquals('Active Key', $activeKeys->first()->name);
    }

    public function test_for_environment_scope(): void
    {
        ApiKey::generate('Live Key', ApiKey::ENV_LIVE);
        ApiKey::generate('Test Key', ApiKey::ENV_TEST);

        $liveKeys = ApiKey::forEnvironment(ApiKey::ENV_LIVE)->get();
        $testKeys = ApiKey::forEnvironment(ApiKey::ENV_TEST)->get();

        $this->assertEquals(1, $liveKeys->count());
        $this->assertEquals(1, $testKeys->count());
    }

    // ========================================
    // Network key behavior (Step 3)
    // ========================================

    public function test_generate_network_key_creates_with_null_site_id(): void
    {
        $result = ApiKey::generateNetworkKey('Network Key', [ApiKey::SCOPE_READ_CONTENT]);

        $this->assertInstanceOf(ApiKey::class, $result['model']);
        $this->assertNull($result['model']->site_id, 'Network key must have site_id = null');
        $this->assertStringStartsWith('dxl_live_', $result['plain_key']);
    }

    public function test_is_network_key_distinguishes_site_and_network(): void
    {
        Site::factory()->primary()->create(['id' => 1]);

        $networkKey = ApiKey::generateNetworkKey('Network')['model'];
        $this->assertTrue($networkKey->isNetworkKey());

        $siteKey = ApiKey::withoutSiteContext(fn () => ApiKey::create([
            'site_id' => 1,
            'name' => 'Site Key',
            'key_hash' => hash('sha256', 'dxl_live_dummy'),
            'key_prefix' => 'dxl_live_',
            'is_active' => true,
            'environment' => ApiKey::ENV_LIVE,
            'scopes' => [],
        ]));
        $this->assertFalse($siteKey->isNetworkKey());
    }

    public function test_has_network_scope_requires_network_and_scope(): void
    {
        Site::factory()->primary()->create(['id' => 1]);

        $networkKey = ApiKey::generateNetworkKey('Network', [ApiKey::SCOPE_READ_CONTENT])['model'];
        $this->assertTrue($networkKey->hasNetworkScope(ApiKey::SCOPE_READ_CONTENT));
        $this->assertFalse($networkKey->hasNetworkScope(ApiKey::SCOPE_WRITE_CONTENT));

        // Site key with the same scope must NOT count as network scope.
        $siteKey = ApiKey::withoutSiteContext(fn () => ApiKey::create([
            'site_id' => 1,
            'name' => 'Site',
            'key_hash' => hash('sha256', 'dxl_live_site_only'),
            'key_prefix' => 'dxl_live_',
            'is_active' => true,
            'environment' => ApiKey::ENV_LIVE,
            'scopes' => [ApiKey::SCOPE_READ_CONTENT],
        ]));
        $this->assertFalse($siteKey->hasNetworkScope(ApiKey::SCOPE_READ_CONTENT));
    }

    public function test_validate_accepts_network_key_regardless_of_current_site(): void
    {
        $result = ApiKey::generateNetworkKey('Network', [ApiKey::SCOPE_READ_CONTENT]);

        // Pretend the current site is anything — network keys ignore site_id.
        $mock = $this->createMock(SiteContextInterface::class);
        $mock->method('currentSiteId')->willReturn(99);
        $this->app->instance(SiteContextInterface::class, $mock);

        $validated = ApiKey::validate($result['plain_key']);

        $this->assertNotNull($validated, 'Network key must validate against any current site');
        $this->assertNull($validated->site_id);
    }

    public function test_validate_rejects_site_key_for_different_site(): void
    {
        Site::factory()->primary()->create(['id' => 1]);

        // Site key bound to site_id = 1
        $plain = 'dxl_live_'.str_repeat('a', 32);
        ApiKey::withoutSiteContext(fn () => ApiKey::create([
            'site_id' => 1,
            'name' => 'Site 1 Key',
            'key_hash' => hash('sha256', $plain),
            'key_prefix' => 'dxl_live_',
            'is_active' => true,
            'environment' => ApiKey::ENV_LIVE,
            'scopes' => [],
        ]));

        // Current request resolves to site_id = 2 — different site.
        $mock = $this->createMock(SiteContextInterface::class);
        $mock->method('currentSiteId')->willReturn(2);
        $this->app->instance(SiteContextInterface::class, $mock);

        $this->assertNull(
            ApiKey::validate($plain),
            'Site 1 key must not validate when current site is 2'
        );
    }
}
