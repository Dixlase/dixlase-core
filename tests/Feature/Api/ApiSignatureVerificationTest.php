<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\VerifyDixlaseSignature;
use App\Support\DixlaseSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * API HMAC 署名検証のセキュリティテスト
 *
 * - 正しい署名での通過
 * - 不正な署名での拒否
 * - タイムスタンプ期限切れ（リプレイ攻撃防止）
 * - 必須ヘッダー欠落
 * - 無効化されたキーの拒否
 */
class ApiSignatureVerificationTest extends TestCase
{
    use RefreshDatabase;

    private string $testSecret = 'test-secret-key-for-hmac-signing';

    private string $testApiKey = 'dls_key_test1234567890abcdefghij';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        // テスト用ルートを登録（VerifyDixlaseSignature ミドルウェア付き）
        Route::middleware(VerifyDixlaseSignature::class.':'.TestClientResolver::class)
            ->post('/test/api/signed', function () {
                return response()->json(['status' => 'ok']);
            });
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    private function signRequest(
        string $method,
        string $path,
        ?string $body = null,
        ?int $timestamp = null,
    ): array {
        $headers = DixlaseSigner::sign($method, $path, $body, $this->testSecret, $timestamp);
        $headers['X-Dixlase-Key'] = $this->testApiKey;

        return $headers;
    }

    // =========================================================================
    // 正常系: 有効な署名
    // =========================================================================

    public function test_valid_signature_passes(): void
    {
        $body = '{"data":"test"}';
        $headers = $this->signRequest('POST', '/test/api/signed', $body);

        $response = $this->postJson('/test/api/signed', ['data' => 'test'], $headers);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    // =========================================================================
    // 異常系: 必須ヘッダー欠落
    // =========================================================================

    public function test_missing_api_key_header_returns_401(): void
    {
        $response = $this->postJson('/test/api/signed', [], [
            'X-Dixlase-Timestamp' => (string) time(),
            'X-Dixlase-Signature' => 'v1=fake',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'missing_headers');
    }

    public function test_missing_timestamp_header_returns_401(): void
    {
        $response = $this->postJson('/test/api/signed', [], [
            'X-Dixlase-Key' => $this->testApiKey,
            'X-Dixlase-Signature' => 'v1=fake',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'missing_headers');
    }

    public function test_missing_signature_header_returns_401(): void
    {
        $response = $this->postJson('/test/api/signed', [], [
            'X-Dixlase-Key' => $this->testApiKey,
            'X-Dixlase-Timestamp' => (string) time(),
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'missing_headers');
    }

    // =========================================================================
    // 異常系: タイムスタンプ
    // =========================================================================

    public function test_expired_timestamp_returns_401(): void
    {
        // 6 分前のタイムスタンプ（許容範囲は 5 分）
        $expiredTimestamp = time() - 360;
        $headers = $this->signRequest('POST', '/test/api/signed', null, $expiredTimestamp);

        $response = $this->postJson('/test/api/signed', [], $headers);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'timestamp_expired');
    }

    public function test_future_timestamp_returns_401(): void
    {
        // 6 分後のタイムスタンプ
        $futureTimestamp = time() + 360;
        $headers = $this->signRequest('POST', '/test/api/signed', null, $futureTimestamp);

        $response = $this->postJson('/test/api/signed', [], $headers);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'timestamp_expired');
    }

    public function test_non_numeric_timestamp_returns_401(): void
    {
        $response = $this->postJson('/test/api/signed', [], [
            'X-Dixlase-Key' => $this->testApiKey,
            'X-Dixlase-Timestamp' => 'not-a-number',
            'X-Dixlase-Signature' => 'v1=fake',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'timestamp_expired');
    }

    // =========================================================================
    // 異常系: 不正な署名
    // =========================================================================

    public function test_tampered_signature_returns_401(): void
    {
        $headers = $this->signRequest('POST', '/test/api/signed');
        // 署名を改ざん
        $headers['X-Dixlase-Signature'] = 'v1=0000000000000000000000000000000000000000000000000000000000000000';

        $response = $this->postJson('/test/api/signed', [], $headers);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'invalid_signature');
    }

    public function test_wrong_secret_produces_invalid_signature(): void
    {
        $wrongHeaders = DixlaseSigner::sign('POST', '/test/api/signed', null, 'wrong-secret');
        $wrongHeaders['X-Dixlase-Key'] = $this->testApiKey;

        $response = $this->postJson('/test/api/signed', [], $wrongHeaders);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'invalid_signature');
    }

    // =========================================================================
    // 異常系: 無効な API キー
    // =========================================================================

    public function test_unknown_api_key_returns_401(): void
    {
        $headers = DixlaseSigner::sign('POST', '/test/api/signed', null, $this->testSecret);
        $headers['X-Dixlase-Key'] = 'dls_key_unknownkey1234567890abcd';

        $response = $this->postJson('/test/api/signed', [], $headers);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'invalid_api_key');
    }

    public function test_revoked_api_key_returns_401(): void
    {
        $headers = DixlaseSigner::sign('POST', '/test/api/signed', null, $this->testSecret);
        $headers['X-Dixlase-Key'] = 'dls_key_revokedkey123456789abcde';

        $response = $this->postJson('/test/api/signed', [], $headers);

        $response->assertStatus(401);
    }
}

/**
 * テスト用クライアントリゾルバー
 */
class TestClientResolver
{
    public function resolve(string $apiKey): ?array
    {
        $clients = [
            'dls_key_test1234567890abcdefghij' => [
                'id' => 1,
                'secret' => 'test-secret-key-for-hmac-signing',
                'active' => true,
            ],
            'dls_key_revokedkey123456789abcde' => [
                'id' => 2,
                'secret' => 'revoked-secret',
                'active' => false,
            ],
        ];

        return $clients[$apiKey] ?? null;
    }
}
