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

namespace App\Support;

use App\Models\CoreVersionHistory;
use App\Services\Core\CoreUpdater;

/**
 * The installed core version, from one place.
 *
 * `config('app.version')` is not defined in `config/app.php`, so every
 * caller that read it got its hard-coded fallback ('1.0.0', '0.0.0'): the
 * file-integrity baseline recorded 1.0.0 on a 0.3.x install, and the core
 * signing manifest recorded 0.0.0. The VERSION file is what the update and
 * rollback pipeline writes, so it comes first; the version-history ledger
 * covers a tree without the file.
 */
final class CoreVersion
{
    public const UNKNOWN = '0.0.0';

    /**
     * @param  string|null  $basePath  Core root to read VERSION from (defaults to base_path()).
     *                                 When given, only that tree is consulted.
     */
    public static function current(?string $basePath = null): string
    {
        $fromDisk = CoreUpdater::readVersionFromDisk($basePath);
        if ($fromDisk !== null) {
            return $fromDisk;
        }

        if ($basePath !== null) {
            return self::UNKNOWN;
        }

        return CoreVersionHistory::currentVersion() ?? self::UNKNOWN;
    }
}
