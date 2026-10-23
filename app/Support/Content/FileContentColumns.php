<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Support\Content;

use Illuminate\Database\Schema\Blueprint;

/**
 * The columns a table needs to publish file-stored content
 *
 * Use from a migration so that every table gets the same columns the same
 * way:
 *
 *   Schema::table('plg_example_pages', function (Blueprint $table) {
 *       FileContentColumns::add($table);
 *   });
 *
 * See {@see \App\Contracts\Content\PublishesFileContent}.
 */
final class FileContentColumns
{
    /**
     * SHA-256 of the published copy held in the database
     */
    public const PUBLISHED_HASH = 'content_hash';

    /**
     * SHA-256 of the files the last time they were looked at
     */
    public const FILE_HASH = 'content_file_hash';

    /**
     * Add both hash columns
     */
    public static function add(Blueprint $table): void
    {
        $table->char(self::PUBLISHED_HASH, 64)->nullable();
        $table->char(self::FILE_HASH, 64)->nullable();
    }

    /**
     * Drop both hash columns
     */
    public static function drop(Blueprint $table): void
    {
        $table->dropColumn([self::PUBLISHED_HASH, self::FILE_HASH]);
    }
}
