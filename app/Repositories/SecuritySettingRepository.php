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

/**
 * セキュリティ設定リポジトリ実装
 */
class SecuritySettingRepository extends AbstractSettingRepository implements SecuritySettingRepositoryInterface
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->cachePrefix = 'security_setting:';
        $this->cacheAllKey = 'security_settings_all';
        $this->cacheTtl = 10;
    }

    /**
     * {@inheritDoc}
     */
    protected function getModelClass(): string
    {
        return SecuritySetting::class;
    }

    /**
     * boolean値を'1'/'0'に変換
     * 
     * {@inheritDoc}
     */
    protected function transformValueForStorage(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        return $value;
    }
}
