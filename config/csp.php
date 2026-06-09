<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

/*
|--------------------------------------------------------------------------
| Content Security Policy — flat configuration facade
|--------------------------------------------------------------------------
|
| The CSP settings are authored as separate files under config/csp/ for
| maintainability (base, directives, domains, admin, reporting). Laravel
| loads a config subdirectory under NESTED keys (csp.base.*, csp.domains.*,
| etc.), but CspBuilder and the rest of the CSP subsystem read a FLAT
| csp.* namespace (csp.trusted_domains, csp.directives, csp.report_uri,
| csp.domain_detection_keywords, csp.front_directives, csp.modes, ...).
|
| This facade bridges the two: it merges the sub-files into the flat csp.*
| namespace the code expects. Without it, csp.trusted_domains and the
| keyword-detection maps resolve to null, so every trusted external origin
| (Cloudflare Turnstile, YouTube, Google Maps, Gravatar, ...) is silently
| dropped from the generated policy.
|
| The individual config/csp/*.php files remain the single source of truth;
| this file only re-exposes them under the flat keys.
|
*/

return array_merge(
    // enabled, mode, admin_mode, modes, report_uri, nonce_length
    require __DIR__.'/csp/base.php',

    // trusted_domains, front_directives, admin_directives,
    // domain_detection_keywords, multi_purpose_keywords
    require __DIR__.'/csp/domains.php',

    // excluded_paths, log_violations, log_channel, blocklist_*
    require __DIR__.'/csp/reporting.php',

    [
        // directive => [allowed sources] map (default-src, script-src, ...)
        'directives' => require __DIR__.'/csp/directives.php',

        // Admin-panel directive overrides (frame-ancestors, frame-src)
        'admin' => require __DIR__.'/csp/admin.php',
    ],
);
