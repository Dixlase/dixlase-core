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

namespace App\Repositories;

use App\Contracts\Repositories\MemberSettingRepositoryInterface;
use App\Models\MemberSetting;
use Illuminate\Support\Facades\Cache;

/**
 * メンバー設定リポジトリ実装
 */
class MemberSettingRepository implements MemberSettingRepositoryInterface
{
    /**
     * キャッシュキーのプレフィックス
     */
    protected const CACHE_PREFIX = 'member_setting:';

    /**
     * 全設定のキャッシュキー
     */
    protected const CACHE_ALL_KEY = 'members_settings_all';

    /**
     * キャッシュの有効期限（分）
     */
    protected const CACHE_TTL = 10;

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return Cache::remember(
            self::CACHE_ALL_KEY,
            now()->addMinutes(self::CACHE_TTL),
            fn() => MemberSetting::pluck('value', 'key')->toArray()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX . $key,
            now()->addMinutes(self::CACHE_TTL),
            fn() => MemberSetting::where('key', $key)->value('value') ?? $default
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $settings = [];
        
        foreach ($keys as $key) {
            $settings[$key] = $this->get($key, $default);
        }
        
        return $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): MemberSetting
    {
        $record = MemberSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        $this->clearCache($key);

        return $record;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                MemberSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            $this->clearAllCache();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to set multiple member settings', [
                'error' => $e->getMessage(),
                'settings' => array_keys($settings)
            ]);

            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return MemberSetting::where('key', $key)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $result = MemberSetting::where('key', $key)->delete();

        if ($result) {
            $this->clearCache($key);
        }

        return (bool) $result;
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(?string $key = null): void
    {
        if ($key !== null) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
        
        Cache::forget(self::CACHE_ALL_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function clearAllCache(): void
    {
        Cache::forget(self::CACHE_ALL_KEY);
        
        // 個別キャッシュもクリア（パターンマッチング）
        // Note: Redisなどを使用している場合は、より効率的な方法を検討
        $allSettings = MemberSetting::pluck('key');
        foreach ($allSettings as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }
}
