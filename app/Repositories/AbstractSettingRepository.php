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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 設定リポジトリ抽象ベースクラス
 * 
 * すべての設定系リポジトリの共通実装を提供します。
 */
abstract class AbstractSettingRepository
{
    /**
     * キャッシュキーのプレフィックス
     * 
     * @var string
     */
    protected string $cachePrefix;

    /**
     * 全設定のキャッシュキー
     * 
     * @var string
     */
    protected string $cacheAllKey;

    /**
     * キャッシュの有効期限（分）
     * 
     * @var int
     */
    protected int $cacheTtl = 10;

    /**
     * 設定のキーカラム名（'key' または 'name'）
     * 
     * @var string
     */
    protected string $keyColumn = 'name';

    /**
     * Eloquentモデルクラス名を取得
     * 
     * @return string
     */
    abstract protected function getModelClass(): string;

    /**
     * 値を保存前に変換（オーバーライド可能）
     * 
     * @param mixed $value
     * @return mixed
     */
    protected function transformValueForStorage(mixed $value): mixed
    {
        return $value;
    }

    /**
     * 値を取得後に変換（オーバーライド可能）
     * 
     * @param mixed $value
     * @return mixed
     */
    protected function transformValueFromStorage(mixed $value): mixed
    {
        return $value;
    }

    /**
     * すべての設定を取得
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(
            $this->cacheAllKey,
            now()->addMinutes($this->cacheTtl),
            function () {
                $modelClass = $this->getModelClass();
                $settings = $modelClass::pluck('value', $this->keyColumn)->toArray();
                
                // 値を変換
                return array_map(
                    fn($value) => $this->transformValueFromStorage($value),
                    $settings
                );
            }
        );
    }

    /**
     * 特定のキーの値を取得
     *
     * @param string $name 設定名
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return Cache::remember(
            $this->cachePrefix . $name,
            now()->addMinutes($this->cacheTtl),
            function () use ($name, $default) {
                $modelClass = $this->getModelClass();
                $value = $modelClass::where($this->keyColumn, $name)->value('value');
                
                if ($value === null) {
                    return $default;
                }
                
                return $this->transformValueFromStorage($value);
            }
        );
    }

    /**
     * 複数のキーの値を一括取得
     *
     * @param array<string> $names 設定名の配列
     * @param mixed $default デフォルト値
     * @return array<string, mixed>
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
     * 設定値を保存
     *
     * @param string $name 設定名
     * @param mixed $value 設定値
     * @return Model
     */
    public function set(string $name, mixed $value): Model
    {
        $modelClass = $this->getModelClass();
        
        $transformedValue = $this->transformValueForStorage($value);
        
        $record = $modelClass::updateOrCreate(
            [$this->keyColumn => $name],
            ['value' => $transformedValue]
        );

        $this->clearCache($name);

        return $record;
    }

    /**
     * 複数の設定値を一括保存
     *
     * @param array<string, mixed> $settings 設定の配列
     * @return bool
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
            Log::error('Failed to set multiple settings', [
                'repository' => static::class,
                'error' => $e->getMessage(),
                'settings' => array_keys($settings)
            ]);

            return false;
        }
    }

    /**
     * 設定が存在するか確認
     *
     * @param string $name 設定名
     * @return bool
     */
    public function has(string $name): bool
    {
        $modelClass = $this->getModelClass();
        return $modelClass::where($this->keyColumn, $name)->exists();
    }

    /**
     * 設定を削除
     *
     * @param string $name 設定名
     * @return bool
     */
    public function delete(string $name): bool
    {
        $modelClass = $this->getModelClass();
        $result = $modelClass::where($this->keyColumn, $name)->delete();

        if ($result) {
            $this->clearCache($name);
        }

        return (bool) $result;
    }

    /**
     * キャッシュをクリア
     *
     * @param string|null $name 特定のキーのみクリアする場合は指定
     * @return void
     */
    public function clearCache(?string $name = null): void
    {
        if ($name !== null) {
            Cache::forget($this->cachePrefix . $name);
        }
        
        Cache::forget($this->cacheAllKey);
    }

    /**
     * すべてのキャッシュをクリア
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        $this->clearCache();
    }
}
