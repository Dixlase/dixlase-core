<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\DTO\Extension;

use App\Enums\ExtensionCompatibilityStatus;
use JsonSerializable;

/**
 * Outcome of an ExtensionCompatibilityChecker::check() call.
 *
 * Carries enough context for a warning log line, an admin badge, and a
 * health-issue record without re-reading the manifest.
 */
final readonly class CompatibilityResult implements JsonSerializable
{
    public function __construct(
        public ExtensionCompatibilityStatus $status,
        public ?string $declared,
        public string $coreVersion,
        public string $message,
    ) {}

    public function isCompatible(): bool
    {
        return $this->status->isCompatible();
    }

    /**
     * @return array{status: string, declared: ?string, core_version: string, message: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status->value,
            'declared' => $this->declared,
            'core_version' => $this->coreVersion,
            'message' => $this->message,
        ];
    }
}
