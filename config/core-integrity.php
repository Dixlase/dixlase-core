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

return [

    /*
    |--------------------------------------------------------------------------
    | Core signing identity
    |--------------------------------------------------------------------------
    | key_id is set on the manifest at signing time and looked up for
    | verification. Default is a Core-dedicated key (decision h); it can be
    | switched to reuse the plugin/authority key without code changes.
    */
    'key_id' => env('DLS_CORE_SIGNING_KEY_ID', 'dixlase-core-2026'),
    'algorithm' => 'ed25519',
    'hash_algorithm' => 'sha256',

    // Manifest + detached signature live at the core root, parallel to the
    // plugin signature.sig convention. Both are excluded from the hashed set.
    'manifest_file' => 'core-manifest.json',
    'signature_file' => 'core-signature.sig',

    /*
    |--------------------------------------------------------------------------
    | Offline-pinned public keys (root trust anchor)
    |--------------------------------------------------------------------------
    | Core is the root of trust and must verify at install time, possibly with
    | no network and no DixlasePublicKeys plugin present. Pin the current public
    | key(s) here ("pin the root, fetch the leaves"). The verifier checks these
    | first, then falls back to AuthorityPublicKeyResolver for online rotation.
    | Format: 'key_id' => 'base64:...' (or plain base64).
    */
    'pinned_public_keys' => [
        // 'dixlase-core-2026' => 'base64:REPLACE_WITH_CORE_PUBLIC_KEY',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hash target — curated Dixlase first-party code
    |--------------------------------------------------------------------------
    | Each entry is a path relative to the core root. Directories are hashed
    | recursively; individual files are hashed if present. This set must equal
    | what ships in the release ZIP minus runtime-mutable paths, so the manifest
    | matches a deployed install (see .claude/plans/core-signing.md §5).
    |
    | vendor/ is intentionally NOT hashed (non-deterministic across composer/PHP
    | versions); composer.lock is hashed instead. Built assets (public/assets)
    | are excluded too (vite output differs in dev).
    */
    'include' => [
        'app',
        'bootstrap/app.php',
        'bootstrap/providers.php',
        'config',
        'database',
        'lang',
        'resources',
        'routes',
        'stubs',
        'artisan',
        'composer.json',
        'composer.lock',
        'public/index.php',
        'public/setup-required.php',
        'public/robots.txt',
        'public/favicon.ico',
        'public/.htaccess',
    ],

    /*
    | Substring/glob patterns excluded from the hash even inside included dirs.
    | The manifest + signature files are listed defensively (they live at the
    | root, outside the included dirs, so they are never walked anyway).
    */
    'exclude_patterns' => [
        '.git',
        '.github',
        '.DS_Store',
        '__MACOSX',
        'Thumbs.db',
        'desktop.ini',
        '.gitignore',
        '.gitkeep',
        'core-manifest.json',
        'core-signature.sig',
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification result cache (seconds)
    |--------------------------------------------------------------------------
    | Hashing the curated set is sub-second but still should not run on every
    | admin page load. The verifier caches its result and recomputes on demand
    | / after an update.
    */
    'cache_ttl_seconds' => (int) env('DLS_CORE_INTEGRITY_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Allow removing/waiving the core signature (dangerous)
    |--------------------------------------------------------------------------
    | Gate for the eventual admin danger-zone + core-scope waiver/remove. Off in
    | production; enable on dev/customized installs. (Phase 3 wiring.)
    */
    'allow_unsign' => (bool) env('DLS_CORE_ALLOW_UNSIGN', false),

];
