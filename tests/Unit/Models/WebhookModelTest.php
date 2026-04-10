<?php

namespace Tests\Unit\Models;

use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookModelTest extends TestCase
{
    use RefreshDatabase;

    private function createWebhook(array $overrides = []): Webhook
    {
        return Webhook::create(array_merge([
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => Webhook::generateSecret(),
            'events' => ['order.created', 'order.updated'],
            'is_active' => true,
            'environment' => 'live',
            'timeout' => 30,
            'retry_count' => 3,
        ], $overrides));
    }

    public function test_webhook_can_be_created(): void
    {
        $webhook = $this->createWebhook();

        $this->assertDatabaseHas('webhooks', ['name' => 'Test Webhook']);
    }

    public function test_events_is_cast_to_array(): void
    {
        $webhook = $this->createWebhook();
        $webhook->refresh();

        $this->assertIsArray($webhook->events);
        $this->assertContains('order.created', $webhook->events);
    }

    public function test_headers_is_cast_to_array(): void
    {
        $webhook = $this->createWebhook(['headers' => ['X-Custom' => 'value']]);
        $webhook->refresh();

        $this->assertIsArray($webhook->headers);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $webhook = $this->createWebhook();

        $this->assertIsBool($webhook->is_active);
    }

    public function test_is_subscribed_to_matching_event(): void
    {
        $webhook = $this->createWebhook(['events' => ['order.created']]);

        $this->assertTrue($webhook->isSubscribedTo('order.created'));
        $this->assertFalse($webhook->isSubscribedTo('user.deleted'));
    }

    public function test_is_subscribed_to_wildcard(): void
    {
        $webhook = $this->createWebhook(['events' => ['*']]);

        $this->assertTrue($webhook->isSubscribedTo('order.created'));
        $this->assertTrue($webhook->isSubscribedTo('any.event'));
    }

    public function test_is_subscribed_to_null_events_matches_all(): void
    {
        $webhook = $this->createWebhook(['events' => null]);

        $this->assertTrue($webhook->isSubscribedTo('anything'));
    }

    public function test_generate_secret_has_prefix(): void
    {
        $secret = Webhook::generateSecret();

        $this->assertStringStartsWith('whsec_', $secret);
        $this->assertGreaterThan(20, strlen($secret));
    }

    public function test_record_success_increments_count(): void
    {
        $webhook = $this->createWebhook();

        $this->assertEquals(0, $webhook->success_count);

        $webhook->recordSuccess();
        $webhook->refresh();

        $this->assertEquals(1, $webhook->success_count);
        $this->assertNotNull($webhook->last_triggered_at);
    }

    public function test_record_failure_increments_count(): void
    {
        $webhook = $this->createWebhook();

        $webhook->recordFailure();
        $webhook->refresh();

        $this->assertEquals(1, $webhook->failure_count);
    }

    public function test_get_success_rate(): void
    {
        $webhook = $this->createWebhook();
        $webhook->update(['success_count' => 8, 'failure_count' => 2]);
        $webhook->refresh();

        $rate = $webhook->getSuccessRate();
        $this->assertIsFloat($rate);
        $this->assertGreaterThan(0, $rate);
        $this->assertLessThanOrEqual(100.0, $rate);
    }

    public function test_get_masked_secret(): void
    {
        $webhook = $this->createWebhook(['secret' => 'whsec_1234567890abcdefghij']);

        $masked = $webhook->getMaskedSecret();

        $this->assertStringNotContainsString('1234567890', $masked);
    }

    public function test_active_scope(): void
    {
        $this->createWebhook(['name' => 'Active', 'is_active' => true]);
        $this->createWebhook(['name' => 'Inactive', 'is_active' => false]);

        $active = Webhook::active()->get();

        $this->assertEquals(1, $active->count());
        $this->assertEquals('Active', $active->first()->name);
    }

    public function test_is_subscribed_to_specific_events(): void
    {
        // subscribedTo スコープは json_each を使うため SQLite では動作しない
        // 代わりにモデルメソッドで検証
        $webhook = $this->createWebhook(['events' => ['order.created', 'order.updated']]);

        $this->assertTrue($webhook->isSubscribedTo('order.created'));
        $this->assertTrue($webhook->isSubscribedTo('order.updated'));
        $this->assertFalse($webhook->isSubscribedTo('user.deleted'));
    }

    public function test_deliveries_relationship(): void
    {
        $webhook = $this->createWebhook();

        $this->assertCount(0, $webhook->deliveries);
    }
}
