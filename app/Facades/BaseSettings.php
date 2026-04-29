<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Facades;

use App\Helpers\EnvHelper;
use App\Models\BaseSetting;
use Exception;
use Illuminate\Support\Facades\Facade;

/**
 * BaseSettings Facade — convenience accessor for reading core base settings.
 * Plugins/themes may use BaseSettings::get('site_name') etc. to read core
 * configuration without instantiating the underlying model directly.
 */
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
