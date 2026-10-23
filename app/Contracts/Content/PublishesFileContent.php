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

namespace App\Contracts\Content;

use App\Enums\ContentPublishMode;
use Illuminate\Database\Eloquent\Builder;

/**
 * Content that can be stored as files and is published through the admin
 *
 * A model implementing this contract keeps its published copy in database
 * columns and, when it uses file storage, a working copy in files. The shared
 * {@see \App\Services\Content\FileContentPublisher} then decides what the site
 * renders, finds changes made to the files outside the admin, publishes them,
 * and writes the files back after a revision restore.
 *
 * Requirements on the model:
 * - The table carries the two hash columns added by
 *   {@see \App\Support\Content\FileContentColumns::add()}
 * - Each part named by {@see fileContentParts()} is a database column holding
 *   the published copy of that part
 *
 * Implement {@see \App\Contracts\Revisionable} as well to have a publish
 * recorded as a revision.
 */
interface PublishesFileContent
{
    /**
     * Whether this record currently keeps its content in files
     *
     * Database-stored records are rendered from their columns and are
     * otherwise ignored by the shared code.
     */
    public function usesFileStorage(): bool;

    /**
     * The publish mode that applies to this record, from its owner's settings
     */
    public function fileContentPublishMode(): ContentPublishMode;

    /**
     * The parts that have a file, mapped to the column holding each part's
     * published copy
     *
     * e.g. `['content' => 'content', 'custom_css' => 'custom_css']`. A part
     * whose file is absent counts as empty.
     *
     * @return array<string, string>
     */
    public function fileContentParts(): array;

    /**
     * The path of a part's file on {@see fileContentDisk()}
     */
    public function fileContentPath(string $part): string;

    /**
     * The filesystem disk the files live on
     */
    public function fileContentDisk(): string;

    /**
     * The prefix of the audit actions written for this record
     *
     * e.g. 'front_page' gives `front_page.file_changed` and
     * `front_page.published_from_file`.
     */
    public function fileContentAuditPrefix(): string;

    /**
     * A query over the records that use file storage
     *
     * Used by the scheduled scan and the dashboard to look at every record of
     * the model.
     *
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function fileStoredQuery(): Builder;
}
