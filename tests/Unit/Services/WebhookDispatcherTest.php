<?php

namespace Tests\Unit\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private Webhook $webhook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhook = Webhook::create([
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret1234567890abcdef',
            'events' => ['order.created'],
            'is_active' => true,
            'environment' => 'live',
            'timeout' => 30,
            'retry_count' => 3,
        ]);
    }

    public function test_dispatch_to_sync_creates_delivery_record(): void
    {
        Http::fake([
            'example.com/*' => Http::response('OK', 200),
        ]);

        $delivery = WebhookDispatcher::dispatchToSync(
            $this->webhook,
            'order.created',
            ['order_id' => 123]
        );

        $this->assertInstanceOf(WebhookDelivery::class, $delivery);
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $this->webhook->id,
            'event' => 'order.created',
        ]);
    }

    public function test_dispatch_to_sync_marks_success_on_200(): void
    {
        Http::fake([
            'example.com/*' => Http::response('{"status":"ok"}', 200),
        ]);

        $delivery = WebhookDispatcher::dispatchToSync(
            $this->webhook,
            'order.created',
            ['order_id' => 123]
        );

        $delivery->refresh();
        $this->assertEquals(WebhookDelivery::STATUS_SUCCESS, $delivery->status);
        $this->assertEquals(200, $delivery->response_code);
    }

    public function test_dispatch_to_sync_marks_failed_on_500(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Internal Server Error', 500),
        ]);

        $delivery = WebhookDispatcher::dispatchToSync(
            $this->webhook,
            'order.created',
            ['order_id' => 123]
        );

        $delivery->refresh();
        // リトライ可能なので retrying になる（attempt 1, max 3）
        $this->assertContains($delivery->status, [
            WebhookDelivery::STATUS_FAILED,
            WebhookDelivery::STATUS_RETRYING,
        ]);
    }

    public function test_dispatch_to_sync_handles_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $delivery = WebhookDispatcher::dispatchToSync(
            $this->webhook,
            'order.created',
            ['order_id' => 123]
        );

        $delivery->refresh();
        $this->assertNotEquals(WebhookDelivery::STATUS_SUCCESS, $delivery->status);
    }

    public function test_send_includes_signature_headers(): void
    {
        Http::fake(function ($request) {
            // 署名ヘッダーの存在を確認
            $this->assertTrue($request->hasHeader('X-Dixlase-Signature'));
            $this->assertTrue($request->hasHeader('X-Dixlase-Timestamp'));
            $this->assertTrue($request->hasHeader('X-Dixlase-Event'));

            return Http::response('OK', 200);
        });

        WebhookDispatcher::dispatchToSync(
            $this->webhook,
            'order.created',
            ['order_id' => 456]
        );

        Http::assertSentCount(1);
    }

    public function test_send_records_webhook_success_count(): void
    {
        Http::fake(['*' => Http::response('OK', 200)]);

        $this->assertEquals(0, $this->webhook->success_count);

        WebhookDispatcher::dispatchToSync($this->webhook, 'order.created', []);

        $this->webhook->refresh();
        $this->assertEquals(1, $this->webhook->success_count);
    }

    public function test_get_stats_returns_expected_keys(): void
    {
        $stats = WebhookDispatcher::getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('successful', $stats);
        $this->assertArrayHasKey('failed', $stats);
    }

    public function test_retry_failed_delivery(): void
    {
        Http::fake(['*' => Http::response('OK', 200)]);

        $delivery = WebhookDelivery::create([
            'event_id' => WebhookDelivery::generateEventId(),
            'nonce' => WebhookDelivery::generateNonce(),
            'webhook_id' => $this->webhook->id,
            'event' => 'order.created',
            'payload' => ['id' => 1],
            'status' => WebhookDelivery::STATUS_RETRYING,
            'attempt' => 1,
            'max_attempts' => 3,
            'next_retry_at' => now()->subMinute(),
        ]);

        $result = WebhookDispatcher::retry($delivery);

        $this->assertTrue($result);
        $delivery->refresh();
        $this->assertEquals(WebhookDelivery::STATUS_SUCCESS, $delivery->status);
    }
}
