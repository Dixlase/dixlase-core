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

namespace Tests\Unit\Services;

use App\Contracts\PluginIntegration\CaptchaFormProviderInterface;
use App\DTO\PluginIntegration\CaptchaFormDTO;
use App\Services\CaptchaService;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\PluginServiceResolver;
use Mockery;
use Tests\TestCase;

/**
 * CaptchaService の capability ベース集約のテスト。
 *
 * プラグインが CaptchaFormProviderInterface を実装して
 * `plugin.capabilities` タグで登録した場合、CaptchaService が
 * PluginServiceResolver 経由でそれらを集約することを検証する。
 */
class CaptchaServiceCapabilityTest extends TestCase
{
    protected PluginPermissionService|Mockery\MockInterface $permissionService;

    protected PluginServiceResolver $resolver;

    protected CaptchaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = Mockery::mock(PluginPermissionService::class);
        $this->permissionService->shouldReceive('check')->andReturn(true)->byDefault();

        $this->resolver = new PluginServiceResolver($this->permissionService);
        $this->app->instance(PluginServiceResolver::class, $this->resolver);

        $this->service = new CaptchaService($this->resolver);
    }

    public function test_aggregates_forms_from_registered_providers(): void
    {
        $this->resolver->register(
            CaptchaFormProviderInterface::class,
            $this->makeProvider('dixlase-inquiry', [
                new CaptchaFormDTO(
                    key: 'inquiry_contact',
                    name: 'dixlase-inquiry::captcha.forms.inquiry_contact',
                    route: 'inquiry.send',
                    category: 'contact',
                    defaultEnabled: true,
                    priority: 200,
                ),
            ])
        );

        $forms = $this->service->getAllForms();

        $this->assertTrue($forms->has('dixlase-inquiry.inquiry_contact'));
        $form = $forms->get('dixlase-inquiry.inquiry_contact');
        $this->assertSame('inquiry.send', $form['route']);
        $this->assertSame('contact', $form['category']);
        $this->assertSame('dixlase-inquiry', $form['plugin_slug']);
    }

    public function test_aggregates_forms_from_multiple_plugins(): void
    {
        $this->resolver->register(
            CaptchaFormProviderInterface::class,
            $this->makeProvider('dixlase-inquiry', [
                new CaptchaFormDTO('inquiry_contact', 'name.a', 'inquiry.send', 'contact', true, 200),
            ])
        );

        $this->resolver->register(
            CaptchaFormProviderInterface::class,
            $this->makeProvider('dixlase-users', [
                new CaptchaFormDTO('user_login', 'name.b', 'users.login', 'users', false, 100),
                new CaptchaFormDTO('user_register', 'name.c', 'users.register', 'users', true, 110),
            ])
        );

        $forms = $this->service->getAllForms();

        $this->assertTrue($forms->has('dixlase-inquiry.inquiry_contact'));
        $this->assertTrue($forms->has('dixlase-users.user_login'));
        $this->assertTrue($forms->has('dixlase-users.user_register'));
    }

    public function test_skips_provider_when_capability_unavailable(): void
    {
        $this->resolver->register(
            CaptchaFormProviderInterface::class,
            $this->makeProvider('disabled-plugin', [
                new CaptchaFormDTO('foo', 'name.x', 'foo.route', 'misc', false, 999),
            ], available: false)
        );

        $forms = $this->service->getAllForms();

        $this->assertFalse($forms->has('disabled-plugin.foo'));
    }

    public function test_returns_only_core_forms_when_no_providers_registered(): void
    {
        $forms = $this->service->getAllForms();

        // コア定義（admin_login, admin_password_reset など）がそのまま残る。
        // 個別キー検証ではなく、プラグインキーが含まれないことを確認する。
        foreach ($forms->keys() as $key) {
            $this->assertStringNotContainsString('dixlase-inquiry.', $key);
            $this->assertStringNotContainsString('dixlase-users.', $key);
        }
    }

    /**
     * @param  CaptchaFormDTO[]  $forms
     */
    protected function makeProvider(string $slug, array $forms, bool $available = true): CaptchaFormProviderInterface
    {
        return new class($slug, $forms, $available) implements CaptchaFormProviderInterface
        {
            /**
             * @param  CaptchaFormDTO[]  $forms
             */
            public function __construct(
                private string $slug,
                private array $forms,
                private bool $available,
            ) {}

            public function getPluginSlug(): string
            {
                return $this->slug;
            }

            public function isCapabilityAvailable(): bool
            {
                return $this->available;
            }

            public function getCaptchaForms(): array
            {
                return $this->forms;
            }
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
