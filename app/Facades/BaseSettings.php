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

namespace App\Facades;

use App\Helpers\EnvHelper;
use App\Models\BaseSetting;
use Exception;
use Illuminate\Support\Facades\Facade;

class BaseSettings extends Facade
{
    public static function get(string $key, $default = null)
    {
        $envKey = EnvHelper::toEnvKey($key);
        if (in_array($envKey, EnvHelper::getEnvMap())) {
            return env($envKey, $default);
        }

        return BaseSetting::getValue($key, $default);
    }

    public static function set(string $key, $value): void
    {
        // .env 設定の場合は例外を投げる
        if (EnvHelper::isEnvKey($key)) {
            throw new Exception("{$key} は .env 設定のため、BaseSettings では変更できません");
        }

        BaseSetting::setValue($key, $value);
    }
}
