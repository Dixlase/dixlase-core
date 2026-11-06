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

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\MediaSetting;

/**
 * The file types and size the media library accepts (#902)
 *
 * Read from the media settings, with the defaults the upload form has always
 * used. Shared by the admin upload request and {@see MediaImporter}, so a
 * file refused on one path is refused on the other.
 *
 * @api
 */
class MediaUploadLimits
{
    /** @var list<string> */
    public const DEFAULT_FILE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

    public const DEFAULT_MAX_FILE_SIZE_KB = 2048;

    /**
     * Allowed file extensions, lower case
     *
     * @return list<string>
     */
    public function allowedFileTypes(): array
    {
        $value = MediaSetting::where('name', 'allowed_file_types')->value('value');
        $types = $value ? json_decode((string) $value, true) : null;

        if (! is_array($types) || $types === []) {
            return self::DEFAULT_FILE_TYPES;
        }

        return array_values(array_map(fn ($type): string => strtolower((string) $type), $types));
    }

    /**
     * Largest accepted file, in kilobytes
     */
    public function maxFileSizeKb(): int
    {
        $value = MediaSetting::where('name', 'max_file_size')->value('value');

        return $value ? (int) $value : self::DEFAULT_MAX_FILE_SIZE_KB;
    }
}
