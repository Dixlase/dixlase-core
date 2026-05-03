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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

namespace App\Services;

/**
 * System warning banner registry
 *
 * A Provider pattern service that allows multiple features to independently register warning banners.
 * Register warning check functions with register() and retrieve evaluation results with getActiveBanners().
 *
 * Banner array shape:
 *  - level: 'error' | 'warning' | 'info'
 *  - icon: Font Awesome class
 *  - title: heading text
 *  - message: description text
 *  - actions: array<int, array{label:string, url:string, style:string, method:string}>
 */
class SystemWarningService
{
    /** @var array<int, callable():(array<int, array<string, mixed>>|null)> */
    protected array $providers = [];

    /**
     * Register a warning check provider
     *
     * @param  callable():(array<int, array<string, mixed>>|null)  $provider
     */
    public function register(callable $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * Retrieve list of currently active warning banners
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveBanners(): array
    {
        $banners = [];
        foreach ($this->providers as $provider) {
            $result = $provider();
            if (is_array($result) && $result !== []) {
                $banners = array_merge($banners, $result);
            }
        }

        return $banners;
    }
}
