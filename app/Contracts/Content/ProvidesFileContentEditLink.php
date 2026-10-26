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

/**
 * Where a record with file-stored content is reviewed and published
 *
 * The dashboard lists the records whose files hold changes waiting to be
 * published. A row is only useful when it leads to the screen where the diff
 * and the Publish button are, and only the owner knows that screen.
 *
 * Implementing this is optional. Without it the record is still listed, by
 * its model and key, without a link.
 *
 * Implement it alongside {@see PublishesFileContent}; it is a separate
 * interface so that adding this did not change that one.
 */
interface ProvidesFileContentEditLink
{
    /**
     * A name a reader recognises the record by, such as its title
     */
    public function fileContentLabel(): string;

    /**
     * The URL of the screen that shows the record's file changes, or null
     * when it has none
     *
     * Whether the viewer may open it is decided by the route the URL points
     * at, so return the URL regardless of who is asking.
     */
    public function fileContentEditUrl(): ?string;
}
