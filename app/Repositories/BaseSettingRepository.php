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

use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Models\BaseSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 基本設定リポジトリ実装
 */
class BaseSettingRepository implements BaseSettingRepositoryInterface
{
    /**
     * キャッシュキーのプレフィックス
     */
    protected const CACHE_PREFIX = 'base_setting:';

    /**
     * 全設定のキャッシュキー
     */
    protected const CACHE_ALL_KEY = 'base_settings_all';

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
            function () {
                $settings = [];
                foreach (BaseSetting::all() as $setting) {
                    $settings[$setting->name] = $this->decodeValue($setting->value);
                }
                return $settings;
            }
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
            function () use ($name, $default) {
                $setting = BaseSetting::where('name', $name)->first();
                return $setting ? $this->decodeValue($setting->value) : $default;
            }
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
    public function set(string $name, mixed $value): BaseSetting
    {
        $encodedValue = $this->encodeValue($value);
        
        $setting = BaseSetting::where('name', $name)->first();

        if ($setting) {
            $setting->value = $encodedValue;
            $setting->save();
        } else {
            $setting = BaseSetting::create([
                'name' => $name,
                'value' => $encodedValue,
            ]);
        }

        $this->clearCache($name);

        return $setting;
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
            Log::error('Failed to set multiple base settings', [
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
        return BaseSetting::where('name', $name)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $name): bool
    {
        $result = BaseSetting::where('name', $name)->delete();

        if ($result) {
            $this->clearCache($name);
        }

        return (bool) $result;
    }

    /**
     * {@inheritDoc}
     */
    public function findWithRelations(string $name): ?BaseSetting
    {
        return BaseSetting::with('defaultOgpImage')
            ->where('name', $name)
            ->first();
    }

    /**
     * キャッシュをクリア
     *
     * @param string|null $name 特定の名前のみクリアする場合は指定
     * @return void
     */
    protected function clearCache(?string $name = null): void
    {
        if ($name !== null) {
            Cache::forget(self::CACHE_PREFIX . $name);
        }
        
        Cache::forget(self::CACHE_ALL_KEY);
    }

    /**
     * すべてのキャッシュをクリア
     *
     * @return void
     */
    protected function clearAllCache(): void
    {
        Cache::forget(self::CACHE_ALL_KEY);
        
        // 個別キャッシュもクリア
        $allSettings = BaseSetting::pluck('name');
        foreach ($allSettings as $name) {
            Cache::forget(self::CACHE_PREFIX . $name);
        }
    }

    /**
     * 値をデコード（JSON文字列を配列に変換）
     *
     * @param mixed $value
     * @return mixed
     */
    protected function decodeValue(mixed $value): mixed
    {
        $decoded = json_decode($value, true);
        return $decoded ?? $value;
    }

    /**
     * 値をエンコード（配列をJSON文字列に変換）
     *
     * @param mixed $value
     * @return string
     */
    protected function encodeValue(mixed $value): string
    {
        return is_array($value) ? json_encode($value) : (string) $value;
    }
}
