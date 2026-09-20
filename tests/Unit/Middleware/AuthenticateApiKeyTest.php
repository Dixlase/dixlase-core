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

namespace Tests\Unit\Middleware;

use App\Http\Middleware\AuthenticateApiKey;
use App\Models\ApiKey;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuthenticateApiKeyTest extends TestCase
{
    use RefreshDatabase;

    protected AuthenticateApiKey $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuthenticateApiKey();
    }

    /**
     * Authorizationヘッダーなしで401を返すこと
     */
    public function test_returns_401_when_no_authorization_header(): void
    {
        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('missing_credentials', $body['error']['code']);
    }

    /**
     * 無効なキーで401を返すこと
     */
    public function test_returns_401_when_invalid_key(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer dxl_live_invalid_key_12345678');

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * 期限切れキーで401を返すこと
     */
    public function test_returns_401_when_expired_key(): void
    {
        $apiKeyData = ApiKey::generate('Expired Key', ApiKey::ENV_TEST, [], null, [
            'expires_at' => now()->subDay(),
        ]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$apiKeyData['plain_key']);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * 有効なキーで認証成功すること
     */
    public function test_passes_with_valid_key(): void
    {
        $apiKeyData = ApiKey::generate('Valid Key', ApiKey::ENV_TEST, [ApiKey::SCOPE_READ_CONTENT]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$apiKeyData['plain_key']);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf(ApiKey::class, $request->attributes->get('api_key'));
    }

    /**
     * スコープ不足で403を返すこと
     */
    public function test_returns_403_when_scope_missing(): void
    {
        $apiKeyData = ApiKey::generate('Limited Key', ApiKey::ENV_TEST, [ApiKey::SCOPE_READ_CONTENT]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$apiKeyData['plain_key']);

        $response = $this->middleware->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            ApiKey::SCOPE_WRITE_CONTENT,
        );

        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('insufficient_scope', $body['error']['code']);
        $this->assertSame(ApiKey::SCOPE_WRITE_CONTENT, $body['error']['details']['required_scope']);
    }

    /**
     * 必要なスコープが全て揃っている場合に通過すること
     */
    public function test_passes_with_required_scopes(): void
    {
        $apiKeyData = ApiKey::generate('Full Key', ApiKey::ENV_TEST, [
            ApiKey::SCOPE_READ_CONTENT,
            ApiKey::SCOPE_WRITE_CONTENT,
        ]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$apiKeyData['plain_key']);

        $response = $this->middleware->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            ApiKey::SCOPE_READ_CONTENT,
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * IP制限で403を返すこと
     */
    public function test_returns_403_when_ip_not_allowed(): void
    {
        $apiKeyData = ApiKey::generate('IP-Limited Key', ApiKey::ENV_TEST, [], null, [
            'allowed_ips' => ['192.168.1.1'],
        ]);

        $request = Request::create('/api/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
        ]);
        $request->headers->set('Authorization', 'Bearer '.$apiKeyData['plain_key']);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('ip_not_allowed', $body['error']['code']);
    }

    /**
     * 許可IPからのアクセスが成功すること
     */
    public function test_passes_when_ip_is_allowed(): void
    {
        $apiKeyData = ApiKey::generate('IP-Limited Key', ApiKey::ENV_TEST, [], null, [
            'allowed_ips' => ['127.0.0.1'],
        ]);

        $request = Request::create('/api/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $request->headers->set('Authorization', 'Bearer '.$apiKeyData['plain_key']);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * 使用記録が更新されること
     */
    public function test_records_usage_on_successful_auth(): void
    {
        $apiKeyData = ApiKey::generate('Usage Key', ApiKey::ENV_TEST);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$apiKeyData['plain_key']);

        $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $apiKey = $apiKeyData['model']->fresh();
        $this->assertEquals(1, $apiKey->usage_count);
        $this->assertNotNull($apiKey->last_used_at);
    }

    public function test_error_envelope_has_unified_structure_with_meta_block(): void
    {
        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('code', $body['error']);
        $this->assertArrayHasKey('message', $body['error']);
        $this->assertSame('missing_credentials', $body['error']['code']);

        $this->assertArrayHasKey('meta', $body);
        $this->assertArrayHasKey('timestamp', $body['meta']);
        $this->assertArrayHasKey('site_id', $body['meta']);
        $this->assertSame(1, $body['meta']['site_id']);
    }

    public function test_network_key_use_writes_audit_log_entry(): void
    {
        $apiKeyData = ApiKey::generateNetworkKey('Network Auth', [ApiKey::SCOPE_READ_CONTENT]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$apiKeyData['plain_key']);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());

        $audit = AuditLog::query()
            ->where('action', 'network_api_key_used')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit, 'Network key usage must produce an audit_logs entry');
        $this->assertSame(AuditLog::CATEGORY_SECURITY, $audit->category);
        $this->assertSame(AuditLog::SEVERITY_NOTICE, $audit->severity);
        $this->assertSame($apiKeyData['model']->id, $audit->context['api_key_id'] ?? null);
        $this->assertSame('GET', $audit->context['method'] ?? null);
        $this->assertSame('api/test', $audit->context['path'] ?? null);
    }

    public function test_site_key_use_does_not_write_network_audit_log_entry(): void
    {
        $apiKeyData = ApiKey::generate('Site Auth', ApiKey::ENV_TEST, [ApiKey::SCOPE_READ_CONTENT]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$apiKeyData['plain_key']);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());

        $audit = AuditLog::query()
            ->where('action', 'network_api_key_used')
            ->first();

        $this->assertNull($audit, 'Site keys must not trigger network_api_key_used audit entries');
    }
}
