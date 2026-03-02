<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

use App\Contracts\RouteSlugProvider;
use App\DTO\RouteSlug\RegisteredSlug;
use App\Repositories\BaseSettingRepository;
use App\Services\RouteSlugRegistry;
use Mockery;
use Tests\TestCase;

/**
 * RouteSlugRegistry ユニットテスト
 */
class RouteSlugRegistryTest extends TestCase
{
    private RouteSlugRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        // 予約スラッグ設定をセット
        config(['admin.reserved-slugs.reserved' => ['api', 'login', 'storage']]);

        // BaseSettingRepository をモックして admin_url を返す
        $mockRepo = Mockery::mock(BaseSettingRepository::class);
        $mockRepo->shouldReceive('get')
            ->with('admin_url', Mockery::any())
            ->andReturn('admin');
        $this->app->instance(BaseSettingRepository::class, $mockRepo);

        $this->registry = new RouteSlugRegistry();
    }

    /**
     * getAllSlugs が予約パスとコアスラッグを含むことを検証
     */
    public function test_get_all_slugs_includes_reserved_and_core(): void
    {
        $slugs = $this->registry->getAllSlugs();
        $slugValues = array_map(fn (RegisteredSlug $s) => $s->slug, $slugs);

        $this->assertContains('api', $slugValues);
        $this->assertContains('login', $slugValues);
        $this->assertContains('storage', $slugValues);
        $this->assertContains('admin', $slugValues);
    }

    /**
     * 予約パスが isReserved=true であることを検証
     */
    public function test_reserved_slugs_have_is_reserved_flag(): void
    {
        $slugs = $this->registry->getAllSlugs();

        foreach ($slugs as $slug) {
            if ($slug->slug === 'api') {
                $this->assertTrue($slug->isReserved);
                $this->assertSame('system:reserved', $slug->owner);

                return;
            }
        }

        $this->fail('予約パス "api" が見つかりませんでした');
    }

    /**
     * コアの admin_url が isReserved=false であることを検証
     */
    public function test_core_admin_slug_is_not_reserved(): void
    {
        $slugs = $this->registry->getAllSlugs();

        foreach ($slugs as $slug) {
            if ($slug->owner === 'core:admin_url') {
                $this->assertFalse($slug->isReserved);
                $this->assertSame('admin', $slug->slug);

                return;
            }
        }

        $this->fail('コアの admin_url スラッグが見つかりませんでした');
    }

    /**
     * registerProvider で追加したプロバイダーのスラッグが含まれることを検証
     */
    public function test_register_provider_adds_slugs(): void
    {
        $provider = Mockery::mock(RouteSlugProvider::class);
        $provider->shouldReceive('getRouteSlugs')
            ->andReturn([
                new RegisteredSlug(
                    slug: 'pages',
                    owner: 'dixlase-pages:route_slug',
                    label: 'Pages Directory',
                ),
            ]);

        $this->registry->registerProvider('dixlase-pages', $provider);

        $slugValues = array_map(
            fn (RegisteredSlug $s) => $s->slug,
            $this->registry->getAllSlugs()
        );
        $this->assertContains('pages', $slugValues);
    }

    /**
     * unregisterProvider で削除したプロバイダーのスラッグが含まれないことを検証
     */
    public function test_unregister_provider_removes_slugs(): void
    {
        $provider = Mockery::mock(RouteSlugProvider::class);
        $provider->shouldReceive('getRouteSlugs')
            ->andReturn([
                new RegisteredSlug(
                    slug: 'pages',
                    owner: 'dixlase-pages:route_slug',
                    label: 'Pages Directory',
                ),
            ]);

        $this->registry->registerProvider('dixlase-pages', $provider);
        $this->registry->unregisterProvider('dixlase-pages');

        $slugValues = array_map(
            fn (RegisteredSlug $s) => $s->slug,
            $this->registry->getAllSlugs()
        );
        $this->assertNotContains('pages', $slugValues);
    }

    /**
     * findConflict が予約パスとの競合を検出することを検証
     */
    public function test_find_conflict_detects_reserved_path(): void
    {
        $conflict = $this->registry->findConflict('api');

        $this->assertNotNull($conflict);
        $this->assertTrue($conflict->isReserved);
        $this->assertSame('api', $conflict->slug);
    }

    /**
     * findConflict がコアスラッグとの競合を検出することを検証
     */
    public function test_find_conflict_detects_core_slug(): void
    {
        $conflict = $this->registry->findConflict('admin');

        $this->assertNotNull($conflict);
        $this->assertSame('core:admin_url', $conflict->owner);
    }

    /**
     * findConflict がプロバイダースラッグとの競合を検出することを検証
     */
    public function test_find_conflict_detects_provider_slug(): void
    {
        $provider = Mockery::mock(RouteSlugProvider::class);
        $provider->shouldReceive('getRouteSlugs')
            ->andReturn([
                new RegisteredSlug(
                    slug: 'inquiry',
                    owner: 'dixlase-inquiry:inquiry_url_slug',
                    label: 'Inquiry URL',
                ),
            ]);

        $this->registry->registerProvider('dixlase-inquiry', $provider);

        $conflict = $this->registry->findConflict('inquiry');
        $this->assertNotNull($conflict);
        $this->assertSame('dixlase-inquiry:inquiry_url_slug', $conflict->owner);
    }

    /**
     * findConflict が excludeOwner で自身を除外することを検証
     */
    public function test_find_conflict_excludes_own_owner(): void
    {
        $conflict = $this->registry->findConflict('admin', 'core:admin_url');

        $this->assertNull($conflict);
    }

    /**
     * findConflict が大文字小文字を区別しないことを検証
     */
    public function test_find_conflict_is_case_insensitive(): void
    {
        $conflict = $this->registry->findConflict('API');
        $this->assertNotNull($conflict);
        $this->assertSame('api', $conflict->slug);

        $conflict = $this->registry->findConflict('Admin');
        $this->assertNotNull($conflict);
        $this->assertSame('admin', $conflict->slug);
    }

    /**
     * isAvailable が使用可能なスラッグで true を返すことを検証
     */
    public function test_is_available_returns_true_for_unused_slug(): void
    {
        $this->assertTrue($this->registry->isAvailable('my-unique-slug'));
    }

    /**
     * isAvailable が使用中のスラッグで false を返すことを検証
     */
    public function test_is_available_returns_false_for_used_slug(): void
    {
        $this->assertFalse($this->registry->isAvailable('api'));
        $this->assertFalse($this->registry->isAvailable('admin'));
    }

    /**
     * isAvailable が excludeOwner 付きで自身を除外することを検証
     */
    public function test_is_available_excludes_own_owner(): void
    {
        $this->assertTrue($this->registry->isAvailable('admin', 'core:admin_url'));
    }

    /**
     * getProviderNames が登録済みプロバイダー名を返すことを検証
     */
    public function test_get_provider_names_returns_registered_names(): void
    {
        $provider = Mockery::mock(RouteSlugProvider::class);
        $provider->shouldReceive('getRouteSlugs')->andReturn([]);

        $this->registry->registerProvider('dixlase-pages', $provider);
        $this->registry->registerProvider('dixlase-inquiry', $provider);

        $names = $this->registry->getProviderNames();
        $this->assertContains('dixlase-pages', $names);
        $this->assertContains('dixlase-inquiry', $names);
    }

    /**
     * clearCache がメモ化キャッシュをリセットすることを検証
     */
    public function test_clear_cache_resets_memoized_slugs(): void
    {
        // 初回取得でキャッシュされる
        $slugs1 = $this->registry->getAllSlugs();

        // 新しいプロバイダーを追加（registerProvider 内で clearCache される）
        $provider = Mockery::mock(RouteSlugProvider::class);
        $provider->shouldReceive('getRouteSlugs')
            ->andReturn([
                new RegisteredSlug(
                    slug: 'new-slug',
                    owner: 'test:new',
                    label: 'New Slug',
                ),
            ]);
        $this->registry->registerProvider('test', $provider);

        $slugs2 = $this->registry->getAllSlugs();

        $this->assertGreaterThan(count($slugs1), count($slugs2));
    }

    /**
     * findConflict が空文字やスペースを正しく処理することを検証
     */
    public function test_find_conflict_handles_whitespace(): void
    {
        $conflict = $this->registry->findConflict(' api ');
        $this->assertNotNull($conflict);
        $this->assertSame('api', $conflict->slug);
    }

    /**
     * 競合なしのスラッグで findConflict が null を返すことを検証
     */
    public function test_find_conflict_returns_null_for_no_conflict(): void
    {
        $conflict = $this->registry->findConflict('completely-unique-slug');
        $this->assertNull($conflict);
    }
}
