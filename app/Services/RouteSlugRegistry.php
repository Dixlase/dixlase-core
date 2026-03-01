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

namespace App\Services;

use App\Contracts\RouteSlugProvider;
use App\DTO\RouteSlug\RegisteredSlug;
use App\Repositories\BaseSettingRepository;

/**
 * @api プラグイン/テーマから直接DIで使用可能な安定APIです
 *
 * ルートスラッグレジストリ
 *
 * システム全体のトップレベルURLスラッグを収集・管理するレジストリ。
 * プラグインが登録したプロバイダー、コア設定、予約パスを統合し、
 * スラッグの重複チェックを行う。
 */
class RouteSlugRegistry
{
    /**
     * 登録されたスラッグプロバイダー
     *
     * @var array<string, RouteSlugProvider>
     */
    protected array $providers = [];

    /**
     * メモ化されたスラッグキャッシュ
     *
     * @var array<RegisteredSlug>|null
     */
    protected ?array $cachedSlugs = null;

    /**
     * スラッグプロバイダーを登録
     *
     * @param  string  $name  プロバイダー名（プラグインスラッグ等）
     * @param  RouteSlugProvider  $provider  プロバイダーインスタンス
     */
    public function registerProvider(string $name, RouteSlugProvider $provider): void
    {
        $this->providers[$name] = $provider;
        $this->clearCache();
    }

    /**
     * スラッグプロバイダーを登録解除
     */
    public function unregisterProvider(string $name): void
    {
        unset($this->providers[$name]);
        $this->clearCache();
    }

    /**
     * 全登録済みスラッグを取得
     *
     * @return array<RegisteredSlug>
     */
    public function getAllSlugs(): array
    {
        if ($this->cachedSlugs !== null) {
            return $this->cachedSlugs;
        }

        $slugs = [];

        // 1. システム予約パスを追加
        $reserved = config('admin.reserved-slugs.reserved', []);
        foreach ($reserved as $path) {
            $slugs[] = RegisteredSlug::reserved($path);
        }

        // 2. コア動的スラッグ（admin_url）を追加
        $slugs = array_merge($slugs, $this->getCoreAdminSlug());

        // 3. プロバイダーからスラッグを収集
        foreach ($this->providers as $provider) {
            $providerSlugs = $provider->getRouteSlugs();
            foreach ($providerSlugs as $slug) {
                $slugs[] = $slug;
            }
        }

        $this->cachedSlugs = $slugs;

        return $slugs;
    }

    /**
     * スラッグの競合を検索
     *
     * @param  string  $slug  チェック対象のスラッグ
     * @param  string|null  $excludeOwner  除外するオーナー（自身のスラッグを除外）
     * @return RegisteredSlug|null 競合するスラッグ。競合なしの場合はnull
     */
    public function findConflict(string $slug, ?string $excludeOwner = null): ?RegisteredSlug
    {
        $normalizedSlug = mb_strtolower(trim($slug));

        foreach ($this->getAllSlugs() as $registered) {
            if (mb_strtolower($registered->slug) === $normalizedSlug) {
                if ($excludeOwner !== null && $registered->owner === $excludeOwner) {
                    continue;
                }

                return $registered;
            }
        }

        return null;
    }

    /**
     * スラッグが使用可能か判定
     *
     * @param  string  $slug  チェック対象のスラッグ
     * @param  string|null  $excludeOwner  除外するオーナー
     */
    public function isAvailable(string $slug, ?string $excludeOwner = null): bool
    {
        return $this->findConflict($slug, $excludeOwner) === null;
    }

    /**
     * 登録されたプロバイダー名一覧を取得
     *
     * @return array<string>
     */
    public function getProviderNames(): array
    {
        return array_keys($this->providers);
    }

    /**
     * メモ化キャッシュをクリア
     */
    public function clearCache(): void
    {
        $this->cachedSlugs = null;
    }

    /**
     * コアの管理画面URLスラッグを取得
     *
     * @return array<RegisteredSlug>
     */
    protected function getCoreAdminSlug(): array
    {
        try {
            $repo = app(BaseSettingRepository::class);
            $adminUrl = $repo->get('admin_url', config('admin.url.admin_url', 'admin'));
        } catch (\Exception $e) {
            $adminUrl = config('admin.url.admin_url', 'admin');
        }

        return [
            new RegisteredSlug(
                slug: $adminUrl,
                owner: 'core:admin_url',
                label: 'validation/route-slug.owners.core_admin_url',
            ),
        ];
    }
}
