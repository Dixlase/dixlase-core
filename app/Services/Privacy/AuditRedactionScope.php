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

namespace App\Services\Privacy;

/**
 * The erasure run that is currently redacting audit rows.
 *
 * The provider contract carries no erasure id, so UserPrivacyEraser binds
 * one of these in the container for the length of a run, and the core
 * provider stamps it on every audit row it redacts. The same id is the
 * `request_id` of the erasure's own audit row, which is how verification
 * tells a recorded redaction from an unexplained edit (#478).
 *
 * Nothing is bound outside a run. A provider called directly redacts rows
 * with no id, and verification reports those rows as unrecorded.
 */
final readonly class AuditRedactionScope
{
    public function __construct(public string $id) {}

    /**
     * The id of the running erasure, or null outside one.
     */
    public static function currentId(): ?string
    {
        return app()->bound(self::class) ? app(self::class)->id : null;
    }
}
