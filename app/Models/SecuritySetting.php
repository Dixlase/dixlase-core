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

namespace App\Models;

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Models\Traits\UsesSettingRepositoryTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * セキュリティ設定モデル
 *
 * @deprecated 静的メソッドは非推奨です。SecuritySettingRepositoryを使用してください。
 */
class SecuritySetting extends Model
{
    use UsesSettingRepositoryTrait;

    protected $table = 'security_settings';

    protected $fillable = [
        'name',
        'value',
    ];

    /**
     * {@inheritDoc}
     */
    protected static function getRepositoryInterface(): string
    {
        return SecuritySettingRepositoryInterface::class;
    }
}
