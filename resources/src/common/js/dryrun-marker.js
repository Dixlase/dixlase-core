/*
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

/*
 * Dry-run release marker for Core update E2E verification.
 *
 * Baked into every common JS bundle so a sandbox that just applied a
 * Core update can prove `public/assets/build/` was actually re-emitted
 * from the release ZIP — not just left stale from the pre-update state.
 * Sandbox check:
 *   grep -l DIXLASE_CORE_DRYRUN_MARKER public/assets/build/js/*.js
 * post-update should list at least one file; pre-update should be
 * empty (a previous release did not carry this marker).
 *
 * Also written to `window.__DIXLASE_CORE_DRYRUN_MARKER__` so a
 * console-inspection check in a live browser session works too:
 *   window.__DIXLASE_CORE_DRYRUN_MARKER__ === 'v0.3.0-dryrun-5'
 *
 * The window write is what forces vite to keep the string constant
 * rather than tree-shaking it out. Remove this marker (source file +
 * app.js import) after the dry-run cycle finishes.
 */
export const DIXLASE_CORE_DRYRUN_MARKER = 'v0.3.0-dryrun-5';

if (typeof window !== 'undefined') {
    window.__DIXLASE_CORE_DRYRUN_MARKER__ = DIXLASE_CORE_DRYRUN_MARKER;
}
