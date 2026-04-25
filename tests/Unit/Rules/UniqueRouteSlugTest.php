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

namespace Tests\Unit\Rules;

use App\DTO\RouteSlug\RegisteredSlug;
use App\Rules\UniqueRouteSlug;
use App\Services\RouteSlugRegistry;
use Mockery;
use Tests\TestCase;

/**
 * UniqueRouteSlug バリデーションルール ユニットテスト
 */
class UniqueRouteSlugTest extends TestCase
{
    private RouteSlugRegistry&\Mockery\MockInterface $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = Mockery::mock(RouteSlugRegistry::class);
        $this->app->instance(RouteSlugRegistry::class, $this->registry);
    }

    /**
     * 競合なしのスラッグでバリデーションが通ることを検証
     */
    public function test_passes_when_no_conflict(): void
    {
        $this->registry->shouldReceive('findConflict')
            ->with('my-page', 'core:admin_url')
            ->andReturn(null);

        $rule = UniqueRouteSlug::for('core:admin_url');
        $failed = false;

        $rule->validate('slug', 'my-page', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    /**
     * 予約パスとの競合でバリデーションが失敗することを検証
     */
    public function test_fails_for_reserved_path(): void
    {
        $this->registry->shouldReceive('findConflict')
            ->with('api', 'core:admin_url')
            ->andReturn(RegisteredSlug::reserved('api'));

        $rule = UniqueRouteSlug::for('core:admin_url');
        $message = null;

        $rule->validate('slug', 'api', function ($msg) use (&$message) {
            $message = $msg;
        });

        $this->assertNotNull($message);
        $this->assertStringContainsString('api', $message);
    }

    /**
     * 他機能との競合でバリデーションが失敗することを検証
     */
    public function test_fails_for_conflict_with_other_feature(): void
    {
        $conflicting = new RegisteredSlug(
            slug: 'pages',
            owner: 'dixlase-pages:route_slug',
            label: 'validation/route-slug.owners.core_admin_url',
        );

        $this->registry->shouldReceive('findConflict')
            ->with('pages', 'core:admin_url')
            ->andReturn($conflicting);

        $rule = UniqueRouteSlug::for('core:admin_url');
        $message = null;

        $rule->validate('slug', 'pages', function ($msg) use (&$message) {
            $message = $msg;
        });

        $this->assertNotNull($message);
        $this->assertStringContainsString('pages', $message);
    }

    /**
     * 空文字列でバリデーションがスキップされることを検証
     */
    public function test_skips_for_empty_string(): void
    {
        $rule = UniqueRouteSlug::for('core:admin_url');
        $failed = false;

        $rule->validate('slug', '', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    /**
     * 非文字列でバリデーションがスキップされることを検証
     */
    public function test_skips_for_non_string(): void
    {
        $rule = UniqueRouteSlug::for('core:admin_url');
        $failed = false;

        $rule->validate('slug', 123, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    /**
     * for() 静的ファクトリーメソッドがインスタンスを返すことを検証
     */
    public function test_for_factory_returns_instance(): void
    {
        $rule = UniqueRouteSlug::for('core:admin_url');
        $this->assertInstanceOf(UniqueRouteSlug::class, $rule);
    }

    /**
     * 予約パスのエラーメッセージに「予約」が含まれることを検証
     */
    public function test_reserved_error_message_format(): void
    {
        app()->setLocale('en');

        $this->registry->shouldReceive('findConflict')
            ->with('login', 'test:slug')
            ->andReturn(RegisteredSlug::reserved('login'));

        $rule = UniqueRouteSlug::for('test:slug');
        $message = null;

        $rule->validate('slug', 'login', function ($msg) use (&$message) {
            $message = $msg;
        });

        $this->assertStringContainsString('login', $message);
        $this->assertStringContainsString('reserved', strtolower($message));
    }

    /**
     * 競合エラーメッセージにオーナーラベルが含まれることを検証
     */
    public function test_conflict_error_message_includes_owner_label(): void
    {
        app()->setLocale('en');

        $conflicting = new RegisteredSlug(
            slug: 'admin',
            owner: 'core:admin_url',
            label: 'validation/route-slug.owners.core_admin_url',
        );

        $this->registry->shouldReceive('findConflict')
            ->with('admin', 'dixlase-pages:route_slug')
            ->andReturn($conflicting);

        $rule = UniqueRouteSlug::for('dixlase-pages:route_slug');
        $message = null;

        $rule->validate('slug', 'admin', function ($msg) use (&$message) {
            $message = $msg;
        });

        $this->assertStringContainsString('admin', $message);
        $this->assertStringContainsString('Admin Panel URL', $message);
    }
}
