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

declare(strict_types=1);

namespace App\DTO\PluginIntegration;

/**
 * One extension's contribution to the runtime-data protection registry.
 *
 * Carries the extension's identity (name + type) alongside its declared
 * protected tables and storage paths so deploy tools can render a
 * per-extension audit line ("DixlaseLegal → 1 table, 0 storage paths")
 * rather than just a flat count. Immutable by design — the registry is
 * built once at boot and shared across every deploy invocation for the
 * request lifecycle.
 *
 * The `extensionType` distinguishes plugin vs theme so future policy
 * work (e.g. "themes should not carry user-runtime tables") can act on
 * it without another lookup.
 */
final class DeployProtectionSource
{
    /**
     * @param  list<string>  $tables         Table names to protect from sync overwrite (e.g. `dls_plg_dixlase_legal_cookie_consents`).
     * @param  list<string>  $storagePaths   Paths relative to `storage/app/private/` to protect from sync overwrite (e.g. `inquiries/attachments/`).
     */
    public function __construct(
        public readonly string $extensionName,
        public readonly string $extensionType,
        public readonly array $tables,
        public readonly array $storagePaths,
    ) {}
}
