<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core only. Do not reference from plugins/themes
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

namespace App\Services\Extension;

use App\Models\Plugin;
use App\Models\Theme;

/**
 * Resolve an extension's human display name from its manifest.
 *
 * The plugin.json / theme.json `name` is the canonical display name
 * ("Dixlase SEO"), whereas the DB `name` column can hold a directory-style
 * value ("DixlaseSEO"). Flashes and other operator-facing text use this so a
 * plugin/theme is named the way its author declared it, not by slug or dir.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
final class ExtensionDisplayName
{
    /**
     * @param  'plugin'|'theme'  $kind
     */
    public static function for(string $kind, string $slug): string
    {
        $model = $kind === 'theme'
            ? Theme::where('slug', $slug)->first()
            : Plugin::where('slug', $slug)->first();

        $directory = $model?->directory;
        if (is_string($directory) && $directory !== '') {
            $manifest = base_path(
                ($kind === 'theme' ? 'themes/' : 'plugins/').$directory.'/'.($kind === 'theme' ? 'theme.json' : 'plugin.json'),
            );
            $decoded = json_decode((string) @file_get_contents($manifest), true);
            if (is_array($decoded) && ! empty($decoded['name']) && is_string($decoded['name'])) {
                return $decoded['name'];
            }
        }

        // Fall back to the DB name, then the slug.
        $dbName = $model?->name;

        return (is_string($dbName) && $dbName !== '') ? $dbName : $slug;
    }
}
