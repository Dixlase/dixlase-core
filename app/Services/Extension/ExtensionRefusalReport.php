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

namespace App\Services\Extension;

/**
 * Why the current request's extension install was refused, in words an
 * operator can act on.
 *
 * The admin panel installs extensions through Artisan::call(). Its console
 * buffer is the wrong thing to show when the command refuses: it can be
 * empty or a bare `{}`, and it can carry paths and other internals that
 * belong in the log. The check that refuses records its reason here, and
 * the controller that invoked the command reads it back for the flash
 * message. ExtensionAssetBuildReport does the same for failed builds.
 *
 * Registered as a scoped binding, so each request starts empty.
 */
class ExtensionRefusalReport
{
    private ?string $reason = null;

    public function record(string $reason): void
    {
        $this->reason = $reason;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}
