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

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Cache;

/**
 * セキュリティ設定リポジトリ実装
 */
class SecuritySettingRepository implements SecuritySettingRepositoryInterface
{
    /**
     * キャッシュキーのプレフィックス
     */
    protected const CACHE_PREFIX = 'security_setting:';

    /**
     * 全設定のキャッシュキー
     */
    protected const CACHE_ALL_KEY = 'security_settings_all';

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
            fn() => SecuritySetting::pluck('value', 'name')->toArray()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX . $name,
            now()->addMinutes(self::CACHE_TTL),
            fn() => SecuritySetting::where('name', $name)->value('value') ?? $default
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(array $names, mixed $default = null): array
    {
        $settings = [];
        
        foreach ($names as $name) {
            $settings[$name] = $this->get($name, $default);
        }
        
        return $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $name, mixed $value): SecuritySetting
    {
        // Convert boolean to string for consistent storage
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        $record = SecuritySetting::updateOrCreate(
            ['name' => $name],
            ['value' => $value]
        );

        $this->clearCache($name);

        return $record;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple(array $settings): bool
    {
        try {
            foreach ($settings as $name => $value) {
                $this->set($name, $value);
            }

            $this->clearAllCache();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to set multiple security settings', [
                'error' => $e->getMessage(),
                'settings' => array_keys($settings)
            ]);

            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        return SecuritySetting::where('name', $name)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $name): bool
    {
        $result = SecuritySetting::where('name', $name)->delete();

        if ($result) {
            $this->clearCache($name);
        }

        return (bool) $result;
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(?string $name = null): void
    {
        if ($name !== null) {
            Cache::forget(self::CACHE_PREFIX . $name);
        }
        
        Cache::forget(self::CACHE_ALL_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function clearAllCache(): void
    {
        Cache::forget(self::CACHE_ALL_KEY);
        
        // 個別キャッシュもクリア
        $allSettings = SecuritySetting::pluck('name');
        foreach ($allSettings as $name) {
            Cache::forget(self::CACHE_PREFIX . $name);
        }
    }
}
