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
 * Which of a record's file-stored parts may carry a script
 *
 * Writing a script into the site is an ADMIN-and-above capability in the
 * admin panel. The file save mode of {@see PublishesFileContent} would let a
 * change reach the site without passing that limit, because `storage/` is
 * writable by the application by design — it can be writable where the code
 * directories are not. So under that mode the shared code took in only the
 * parts that cannot carry a script (#744).
 *
 * Since #786 the file save mode takes effect only in a local environment,
 * where it takes every part in, and nowhere else, where nothing is taken in.
 * What this declares therefore no longer changes what goes live; the
 * contract is kept so that existing implementations keep working, and so a
 * screen can say which parts carry a script.
 *
 * Implementing this is optional. Without it,
 * {@see \App\Services\Content\FileContentPublisher::scriptCapableParts()}
 * falls back to a rule on the part's name, which covers the conventional
 * `custom_css` and `custom_js` parts. Implement it when a record has a part
 * the name does not describe — most often a body whose editor type decides
 * the answer, as an HTML body can carry `<script>` while a Markdown or GUI
 * body cannot.
 *
 * Implement it alongside {@see PublishesFileContent}; it is a separate
 * interface so that adding this did not change that one.
 */
interface DeclaresScriptCapableParts
{
    /**
     * The names of the parts that may carry a script
     *
     * Names come from the keys of {@see PublishesFileContent::fileContentParts()}.
     * A name that is not a part of this record is ignored. Return an empty
     * array to say that no part of this record can carry a script — that is a
     * statement, not an omission, and it switches the name-based default off.
     *
     * @return list<string>
     */
    public function scriptCapableFileContentParts(): array;
}
