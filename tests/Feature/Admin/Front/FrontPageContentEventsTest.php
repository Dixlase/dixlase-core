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

namespace Tests\Feature\Admin\Front;

use App\Actions\FrontPage\CreateFrontPageAction;
use App\Actions\FrontPage\DeleteFrontPageAction;
use App\Actions\FrontPage\RestoreFrontPageRevisionAction;
use App\Actions\FrontPage\UpdateFrontPageAction;
use App\Actors\MemberActor;
use App\Contracts\I18n\LocaleUrlRouting;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Events\DixlaseEvents;
use App\Jobs\SendWebhook;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Models\Member;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\FrontPageContentService;
use App\Services\FrontPageRevisionService;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Core's front page is the first emitter of the content events (#1099).
 */
class FrontPageContentEventsTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    /** @var list<array{0: string, 1: array<string, mixed>}> */
    private array $fired = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Member::create([
            'account_name' => 'ad',
            'display_name' => 'ad',
            'email' => 'ad@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Active,
        ]);

        foreach (DixlaseEvents::byCategory('content') as $event) {
            Event::listen($event, function (array $payload) use ($event) {
                $this->fired[] = [$event, $payload];
            });
        }
    }

    private function actor(): MemberActor
    {
        return new MemberActor($this->admin);
    }

    private function createPage(string $content = 'Body'): FrontPage
    {
        $result = app(CreateFrontPageAction::class)->execute($this->actor(), [
            'lang' => 'en',
            'editor_type' => 'markdown',
            'storage_type' => ContentStorageType::DATABASE->slug(),
            'content' => $content,
        ]);

        $this->assertTrue($result->success);

        return $result->model;
    }

    private function updatePage(FrontPage $page, string $content): void
    {
        $result = (new UpdateFrontPageAction(
            $page,
            app(FrontPageContentService::class),
            app(FrontPageRevisionService::class),
        ))->execute($this->actor(), ['content' => $content]);

        $this->assertTrue($result->success);
    }

    /**
     * @return list<string>
     */
    private function firedNames(): array
    {
        return array_map(fn (array $f) => $f[0], $this->fired);
    }

    public function test_creating_fires_created_then_published(): void
    {
        $page = $this->createPage();

        $this->assertSame(
            [DixlaseEvents::CONTENT_CREATED, DixlaseEvents::CONTENT_PUBLISHED],
            $this->firedNames(),
        );

        $payload = $this->fired[0][1];
        $this->assertSame('core', $payload['source']);
        $this->assertSame(FrontPage::CONTENT_TYPE, $payload['type']);
        $this->assertSame($page->id, $payload['id']);
        $this->assertSame('/', $payload['url']);
        $this->assertSame('published', $payload['status']);
        $this->assertSame($this->admin->id, $payload['actor_id']);
        $this->assertArrayNotHasKey('content', $payload);
    }

    public function test_saving_fires_updated(): void
    {
        $page = $this->createPage();
        $this->fired = [];

        $this->updatePage($page, 'Changed');

        $this->assertSame([DixlaseEvents::CONTENT_UPDATED], $this->firedNames());
        $this->assertSame($page->id, $this->fired[0][1]['id']);
    }

    public function test_restoring_a_revision_fires_updated(): void
    {
        $page = $this->createPage('First');
        $this->updatePage($page, 'Second');
        $first = FrontPageRevision::query()
            ->where('front_page_id', $page->id)
            ->orderBy('id')
            ->firstOrFail();
        $this->fired = [];

        $result = (new RestoreFrontPageRevisionAction($first, app(RevisionService::class)))
            ->execute($this->actor(), []);

        $this->assertTrue($result->success);
        $this->assertSame([DixlaseEvents::CONTENT_UPDATED], $this->firedNames());
    }

    public function test_deleting_fires_deleted_with_the_status_it_had(): void
    {
        $page = $this->createPage();
        $id = $page->id;
        $this->fired = [];

        $result = (new DeleteFrontPageAction($page, app(FrontPageContentService::class)))
            ->execute($this->actor(), []);

        $this->assertTrue($result->success);
        $this->assertSame([DixlaseEvents::CONTENT_DELETED], $this->firedNames());
        $this->assertSame($id, $this->fired[0][1]['id']);
        $this->assertSame('published', $this->fired[0][1]['status']);
    }

    public function test_a_webhook_subscribed_to_published_receives_a_delivery(): void
    {
        Queue::fake();

        $webhook = Webhook::create([
            'name' => 'Rebuild',
            'url' => 'https://example.com/rebuild',
            'secret' => 'whsec_testsecret1234567890abcdef',
            'events' => [DixlaseEvents::CONTENT_PUBLISHED],
            'is_active' => true,
            'environment' => 'live',
            'timeout' => 30,
            'retry_count' => 3,
        ]);

        $page = $this->createPage();

        $deliveries = WebhookDelivery::query()->where('webhook_id', $webhook->id)->get();
        $this->assertCount(1, $deliveries);
        $this->assertSame(DixlaseEvents::CONTENT_PUBLISHED, $deliveries[0]->event);
        $this->assertSame($page->id, $deliveries[0]->payload['id']);
        $this->assertSame(FrontPage::CONTENT_TYPE, $deliveries[0]->payload['type']);
        Queue::assertPushed(SendWebhook::class, 1);
    }

    public function test_the_url_carries_the_locale_when_locale_routing_is_on(): void
    {
        $this->app->instance(LocaleUrlRouting::class, new class implements LocaleUrlRouting
        {
            public function isEnabled(): bool
            {
                return true;
            }
        });

        $this->createPage();

        $this->assertSame('/en', $this->fired[0][1]['url']);
    }
}
