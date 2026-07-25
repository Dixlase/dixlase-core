<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use App\Models\MediaSetting;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Media settings repository implementation
 */
class MediaSettingRepository extends AbstractSettingRepository implements MediaSettingRepositoryInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cachePrefix = 'media_setting:';
        $this->cacheAllKey = 'media_settings_all';
        $this->cacheTtl = 10;
    }

    /**
     * {@inheritDoc}
     */
    protected function getModelClass(): string
    {
        return MediaSetting::class;
    }

    /**
     * Convert array to JSON string
     *
     * {@inheritDoc}
     */
    protected function transformValueForStorage(mixed $value): mixed
    {
        return is_array($value) ? json_encode($value) : (string) $value;
    }

    /**
     * Convert JSON string to array
     *
     * {@inheritDoc}
     */
    protected function transformValueFromStorage(mixed $value): mixed
    {
        $decoded = json_decode($value, true);

        return $decoded ?? $value;
    }
}
