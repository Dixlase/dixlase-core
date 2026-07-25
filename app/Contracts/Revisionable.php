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

namespace App\Contracts;

/**
 * Each plugin/theme has its own revision table and Eloquent model, and by
 * implementing this interface, the common `RevisionService` can
 * handle history recording, restoration, auto-deletion, and protection in a unified manner
 *
 * Table naming convention:
 * - Core: `{entity}_revisions` (e.g. `front_page_revisions`)
 * - plugin: `dls_plg_{slug}_{entity}_revisions`
 * - theme: `dls_thm_{slug}_{entity}_revisions`
 *
 * Each revision table must have the following columns:
 * - id (bigint, PK)
 * - {foreignKey} (bigint, FK to parent content, CASCADE DELETE)
 * - snapshot (json, snapshot of `revisionableFields()`)
 * - type (string, 'auto' | 'manual' | 'restore_backup')
 * - note (string, nullable)
 * - is_protected (boolean, default false)
 * - created_by (bigint, nullable FK to members)
 * - created_at (timestamp)
 */
interface Revisionable
{
    /**
     * Returns the fully qualified class name of the Eloquent model that stores revisions
     *
     * e.g. `\App\Models\FrontPageRevision::class`
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public function revisionModel(): string;

    /**
     * Returns the foreign key column name in the revision table that references the parent content
     *
     * e.g. 'front_page_id'
     */
    public function revisionForeignKey(): string;

    /**
     * List of content attribute names to include in the snapshot
     *
     * Only the columns listed here will be saved in the `snapshot` JSON, and
     * only the same columns will be written back during restoration
     *
     * @return list<string>
     */
    public function revisionableFields(): array;
}
