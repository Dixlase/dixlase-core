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

declare(strict_types=1);

namespace App\Services\Site;

use App\Contracts\Site\SiteContextInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Site-aware storage helper.
 *
 * Provides Filesystem instances rooted at the multisite-aware storage
 * tree. Resolved at call time so the current site is always reflected,
 * regardless of whether config is cached.
 *
 *   storage/app/
 *   ├── public/sites/{site_id}/      via private/public()
 *   ├── private/sites/{site_id}/     via private()
 *   └── private/global/              via global()
 *
 * Existing code that uses Storage::disk('local'), Storage::disk('public'),
 * or storage_path() directly continues to work; this helper is the
 * forward-looking API that plugins and themes should adopt.
 */
class SiteStorage
{
    /**
     * Private filesystem rooted at storage/app/private/sites/{site_id}/.
     */
    public static function private(?int $siteId = null): Filesystem
    {
        $siteId ??= app(SiteContextInterface::class)->currentSiteId();

        return Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/private/sites/'.$siteId),
            'throw' => false,
        ]);
    }

    /**
     * Public filesystem rooted at storage/app/public/sites/{site_id}/.
     */
    public static function public(?int $siteId = null): Filesystem
    {
        $siteId ??= app(SiteContextInterface::class)->currentSiteId();

        return Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/public/sites/'.$siteId),
            'url' => config('app.url').'/storage/sites/'.$siteId,
            'visibility' => 'public',
            'throw' => false,
        ]);
    }

    /**
     * Network-wide filesystem rooted at storage/app/private/global/.
     */
    public static function global(): Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/private/global'),
            'throw' => false,
        ]);
    }

    /**
     * Absolute path to the current site's private root.
     */
    public static function privatePath(?int $siteId = null): string
    {
        $siteId ??= app(SiteContextInterface::class)->currentSiteId();

        return storage_path('app/private/sites/'.$siteId);
    }

    /**
     * Absolute path to the current site's public root.
     */
    public static function publicPath(?int $siteId = null): string
    {
        $siteId ??= app(SiteContextInterface::class)->currentSiteId();

        return storage_path('app/public/sites/'.$siteId);
    }

    /**
     * Absolute path to the network-wide global root.
     */
    public static function globalPath(): string
    {
        return storage_path('app/private/global');
    }
}
