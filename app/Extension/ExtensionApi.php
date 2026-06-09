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

namespace App\Extension;

/**
 * Extension API contract version constants.
 *
 * Shared by plugins and themes — both face the same Contract+DTO
 * surface from core, so both share this single version.
 *
 * Bump policy:
 *   - MINOR (0.1 → 0.2): backwards-compatible additions to PLUGIN-API.md surface
 *   - MAJOR (0.x → 1.0): breaking changes to the extension-facing surface
 *
 * Extensions declare their target version in plugin.json / theme.json:
 *
 *   "requires": { "dixlase_api": "^0.1" }
 *
 * v0.1.0 enforcement: advisory only (warning log + health deduction).
 * v0.2.0 enforcement: hard refusal to register incompatible extensions.
 * v1.0.0 enforcement: also drives WASM PHP interpreter image selection.
 */
final class ExtensionApi
{
    /**
     * The contract version this core exposes to extensions right now.
     *
     * Bump this when PLUGIN-API.md changes the extension-facing surface.
     */
    public const CURRENT_VERSION = '0.1.0';

    /**
     * Versions an extension may declare and still be loaded.
     *
     * Used by ExtensionCompatibilityChecker. Keep additive only —
     * loosening this range is the only acceptable in-version change.
     */
    public const SUPPORTED_RANGE = '^0.1';

    /**
     * The manifest path where extensions declare their target version.
     *
     * Resolved as $manifest['requires']['dixlase_api'].
     */
    public const MANIFEST_KEY = 'dixlase_api';
}
