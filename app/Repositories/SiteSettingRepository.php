<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Models\SiteSetting;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 基本設定リポジトリ実装
 */
class SiteSettingRepository extends AbstractSettingRepository implements SiteSettingRepositoryInterface
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->cachePrefix = 'base_setting:';
        $this->cacheAllKey = 'site_settings_all';
        $this->cacheTtl = 10;
    }

    /**
     * {@inheritDoc}
     */
    protected function getModelClass(): string
    {
        return SiteSetting::class;
    }

    /**
     * {@inheritDoc}
     */
    public function findWithRelations(string $name): ?SiteSetting
    {
        return SiteSetting::with('defaultOgpImage')
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
