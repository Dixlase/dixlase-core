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

namespace App\Repositories;

use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Models\BaseSetting;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 基本設定リポジトリ実装
 */
class BaseSettingRepository extends AbstractSettingRepository implements BaseSettingRepositoryInterface
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->cachePrefix = 'base_setting:';
        $this->cacheAllKey = 'base_settings_all';
        $this->cacheTtl = 10;
    }

    /**
     * {@inheritDoc}
     */
    protected function getModelClass(): string
    {
        return BaseSetting::class;
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
     * 配列をJSON文字列に変換
     *
     * {@inheritDoc}
     */
    protected function transformValueForStorage(mixed $value): mixed
    {
        return is_array($value) ? json_encode($value) : (string) $value;
    }

    /**
     * JSON文字列を配列に変換
     *
     * {@inheritDoc}
     */
    protected function transformValueFromStorage(mixed $value): mixed
    {
        $decoded = json_decode($value, true);

        return $decoded ?? $value;
    }
}
