<?php

/*
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * SPDX whitelist and AGPL-3.0 compatibility table used by
 * LicenseValidator and LicenseCompatibilityChecker.
 *
 * Update note: every entry here is a public commitment. Once an SPDX
 * identifier is published in the accepted list, removing it will break
 * extensions already in circulation. Add entries readily; remove them
 * only with a deprecation cycle.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | SPDX Identifier Format
    |--------------------------------------------------------------------------
    |
    | Pattern used to verify that a value in plugin.json `license` looks like
    | a syntactically valid SPDX identifier (or expression / LicenseRef).
    | This is a syntax check only; semantic validity (membership in the SPDX
    | License List) is checked separately against `accepted`.
    |
    | Examples that pass:
    |   - "MIT"
    |   - "GPL-3.0-or-later"
    |   - "Apache-2.0 WITH LLVM-exception"
    |   - "(MIT OR Apache-2.0)"
    |   - "LicenseRef-Dixlase-Commercial"
    |
    | Examples that fail:
    |   - "GPLv3" (not SPDX form)
    |   - "MIT License" (free text)
    |   - "" (empty)
    |
    */
    'spdx_pattern' => '/^[A-Za-z0-9\-\.\+]+(?:\s+(?:WITH|AND|OR)\s+[A-Za-z0-9\-\.\+]+)*$|^LicenseRef-[A-Za-z0-9\-\.]+$|^\([A-Za-z0-9\-\.\+\s\(\)]+\)$/',

    /*
    |--------------------------------------------------------------------------
    | Accepted Licenses for Plugins and Themes
    |--------------------------------------------------------------------------
    |
    | SPDX identifiers (or LicenseRef-* entries) that the Dixlase project
    | accepts in plugin.json / theme.json. Each is labelled with how it
    | relates to the AGPL-3.0 core:
    |
    |   compatibility:
    |     - 'gpl_compatible'   = redistributable as part of an AGPL-3.0 work
    |                            without a separate dual-license arrangement
    |     - 'compatible_via_exception' = relies on the Dixlase Plugin and
    |                            Theme Exception (see LICENSE-EXCEPTIONS) for
    |                            distribution under non-GPL terms
    |     - 'commercial_escape' = LicenseRef pointing to a separate commercial
    |                            agreement (dual license escape hatch)
    |
    |   tier: 'permissive' | 'weak_copyleft' | 'strong_copyleft' | 'commercial'
    |
    | The list intentionally stays small for Phase 1. Additions go through
    | the same process as adding a new accepted SPDX expression to a
    | distribution channel: discuss in an issue, document the rationale,
    | then add.
    |
    */
    'accepted' => [

        // ---- Strong copyleft (GPL family) ----
        'AGPL-3.0-or-later' => ['compatibility' => 'gpl_compatible', 'tier' => 'strong_copyleft'],
        'AGPL-3.0-only' => ['compatibility' => 'gpl_compatible', 'tier' => 'strong_copyleft'],
        'GPL-3.0-or-later' => ['compatibility' => 'gpl_compatible', 'tier' => 'strong_copyleft'],
        'GPL-3.0-only' => ['compatibility' => 'gpl_compatible', 'tier' => 'strong_copyleft'],

        // GPL-2.0-or-later upgrades cleanly to GPL-3, so it is GPL-compatible.
        // GPL-2.0-only is intentionally NOT accepted because it cannot be
        // combined with AGPL-3.0 code.
        'GPL-2.0-or-later' => ['compatibility' => 'gpl_compatible', 'tier' => 'strong_copyleft'],

        // ---- Weak copyleft ----
        'LGPL-3.0-or-later' => ['compatibility' => 'gpl_compatible', 'tier' => 'weak_copyleft'],
        'LGPL-3.0-only' => ['compatibility' => 'gpl_compatible', 'tier' => 'weak_copyleft'],
        'LGPL-2.1-or-later' => ['compatibility' => 'gpl_compatible', 'tier' => 'weak_copyleft'],
        'MPL-2.0' => ['compatibility' => 'gpl_compatible', 'tier' => 'weak_copyleft'],

        // ---- Permissive ----
        'MIT' => ['compatibility' => 'gpl_compatible', 'tier' => 'permissive'],
        'BSD-2-Clause' => ['compatibility' => 'gpl_compatible', 'tier' => 'permissive'],
        'BSD-3-Clause' => ['compatibility' => 'gpl_compatible', 'tier' => 'permissive'],
        'Apache-2.0' => ['compatibility' => 'gpl_compatible', 'tier' => 'permissive'],
        'ISC' => ['compatibility' => 'gpl_compatible', 'tier' => 'permissive'],
        'Zlib' => ['compatibility' => 'gpl_compatible', 'tier' => 'permissive'],
        'Unlicense' => ['compatibility' => 'gpl_compatible', 'tier' => 'permissive'],
        'CC0-1.0' => ['compatibility' => 'gpl_compatible', 'tier' => 'permissive'],

        // ---- Commercial / dual-license escape ----
        // A plugin distributed under "LicenseRef-Dixlase-Commercial" is
        // expected to be paired with a commercial agreement with the
        // publisher. This entry simply marks the identifier as a known
        // alias so it does not trip the unknown_license warning.
        'LicenseRef-Dixlase-Commercial' => ['compatibility' => 'commercial_escape', 'tier' => 'commercial'],

    ],

    /*
    |--------------------------------------------------------------------------
    | Explicitly Refused Licenses
    |--------------------------------------------------------------------------
    |
    | Identifiers that look like SPDX but are known to be incompatible with
    | the AGPL-3.0 core and have no Dixlase escape hatch. Listing them here
    | lets the install guard explain why the install is refused, rather
    | than reporting a generic "unknown license".
    |
    */
    'refused' => [
        'GPL-2.0-only' => 'GPL-2.0-only cannot be relicensed under GPL-3.0 or AGPL-3.0; the Dixlase core is AGPL-3.0 and would force such a relicensing.',
        'LGPL-2.1-only' => 'LGPL-2.1-only cannot be relicensed under LGPL-3.0; choose LGPL-2.1-or-later or LGPL-3.0-only / -or-later instead.',
        'Apache-1.0' => 'Apache-1.0 is incompatible with the GPL family; use Apache-2.0 instead.',
        'Apache-1.1' => 'Apache-1.1 is incompatible with the GPL family; use Apache-2.0 instead.',
        'SSPL-1.0' => 'SSPL is not OSI-approved and is incompatible with the AGPL-3.0 core; use AGPL-3.0-or-later if you want a copyleft network license.',
        'BUSL-1.1' => 'BUSL is a source-available license, not an OSI-approved open-source license; it cannot be combined with the AGPL-3.0 core.',
        'proprietary' => 'Plain "proprietary" is too vague. If you ship a paid extension, use LicenseRef-Dixlase-Commercial (or your own LicenseRef-*) and document the terms in a LICENSE file shipped with the plugin.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Install Guard Default Behavior
    |--------------------------------------------------------------------------
    |
    | What `dls:plugin:install` does when the manifest's license is:
    |
    |   - missing  → 'fail' (always; no soft fallback in v0.1)
    |   - refused  → 'fail' unless --force is passed
    |   - unknown  → 'warn' (proceed with a printed notice)
    |   - accepted → 'pass'
    |
    | Configurable so that a hardened deployment can flip 'unknown' to
    | 'fail' without forking the command.
    |
    */
    'install_guard' => [
        'on_missing' => 'fail',
        'on_refused' => 'fail',
        'on_unknown' => 'warn',
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Score Deductions
    |--------------------------------------------------------------------------
    |
    | Used by PluginHealthScorer::evaluateLicenseMetadata(). Override here
    | rather than in PluginHealthStatus when an operator wants to tighten
    | the gate without changing core code.
    |
    */
    'health_deductions' => [
        'missing_license' => -10,
        'invalid_license_spdx' => -5,
        'unknown_license' => -3,
        'license_refused' => -25,
    ],

];
