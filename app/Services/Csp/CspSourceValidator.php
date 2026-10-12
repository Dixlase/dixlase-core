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

namespace App\Services\Csp;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Validates CSP source expressions supplied by extensions
 *
 * Everything a plugin or theme contributes to the policy (manifest `csp`
 * section, CspPolicyProvider, csp_add_directive(), RegistersCspPolicy)
 * passes through CspPolicyRegistry, and the registry checks each value here
 * before accepting it. Only shapes that name a concrete origin are allowed;
 * anything that relaxes the policy for the whole site is rejected.
 *
 * Accepted:
 * - Host sources: `https://cdn.example.com`, `cdn.example.com`,
 *   `https://*.example.com`, optional port (`:8443`, `:*`) and path
 *   (schemes: http, https, ws, wss)
 * - `'self'`
 * - `'nonce'` (core placeholder, replaced by the per-request nonce) and
 *   `'sha256-…'` / `'sha384-…'` / `'sha512-…'` hashes in script/style directives
 * - `data:` in img-src / font-src / media-src, `blob:` in img-src /
 *   media-src / worker-src
 *
 * Rejected (non-exhaustive): `'unsafe-inline'`, `'unsafe-eval'`,
 * `'unsafe-hashes'`, `'wasm-unsafe-eval'`, `'strict-dynamic'`, `'none'`,
 * literal `'nonce-…'` values, a bare `*`, wildcard-only hosts
 * (`https://*`, `*.com`), scheme-only sources such as `https:` or `data:`
 * in script-src, values containing whitespace, `;` or `,` (directive
 * smuggling), and directives an extension has no business widening
 * (`frame-ancestors`, `base-uri`, `object-src`, `script-src-attr`, …).
 *
 * The admin's own settings (trusted domains, custom directives, mode) do
 * not go through the registry and are therefore never filtered here; the
 * admin deny list in CspBuilder still applies on top of what passes.
 */
class CspSourceValidator
{
    /**
     * Directives an extension may add sources to
     *
     * @var array<int, string>
     */
    public const ALLOWED_DIRECTIVES = [
        'script-src',
        'script-src-elem',
        'style-src',
        'style-src-elem',
        'img-src',
        'font-src',
        'connect-src',
        'media-src',
        'frame-src',
        'child-src',
        'worker-src',
        'manifest-src',
        'form-action',
    ];

    /**
     * Directives where the 'nonce' placeholder and hash sources are meaningful
     *
     * @var array<int, string>
     */
    private const SCRIPT_STYLE_DIRECTIVES = [
        'script-src',
        'script-src-elem',
        'style-src',
        'style-src-elem',
    ];

    /**
     * Scheme-only sources an extension may use, per directive
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_SCHEME_SOURCES = [
        'data:' => ['img-src', 'font-src', 'media-src'],
        'blob:' => ['img-src', 'media-src', 'worker-src'],
    ];

    /**
     * Host source: optional scheme, optional `*.` wildcard, host, optional
     * port and path
     */
    private const HOST_SOURCE_PATTERN = '~^(?:(?:https?|wss?)://)?(\*\.)?([a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)*)(?::(?:[0-9]{1,5}|\*))?(?:/[^\s;,\'"]*)?$~i';

    /**
     * Validate a directive name
     *
     * @return string|null Rejection reason, or null when the directive is allowed
     */
    public function directiveRejection(string $directive): ?string
    {
        return in_array(strtolower(trim($directive)), self::ALLOWED_DIRECTIVES, true)
            ? null
            : 'directive_not_allowed';
    }

    /**
     * Validate one source expression for a directive
     *
     * @return string|null Rejection reason, or null when the value is allowed
     */
    public function rejection(string $directive, mixed $value): ?string
    {
        $directive = strtolower(trim($directive));

        if (($reason = $this->directiveRejection($directive)) !== null) {
            return $reason;
        }

        if (! is_string($value)) {
            return 'not_a_string';
        }

        // Whitespace, ';' or ',' would let a single "value" smuggle extra
        // sources or whole directives into the header.
        if ($value === '' || preg_match('/[\s;,\x00-\x1F\x7F]/', $value) === 1) {
            return 'malformed';
        }

        $lower = strtolower($value);

        if (str_starts_with($value, "'")) {
            return $this->keywordRejection($directive, $lower);
        }

        if ($value === '*') {
            return 'wildcard_any';
        }

        // Scheme-only source (`data:`, `https:`, ...) — allows every URL of
        // that scheme, so only a few harmless combinations are accepted.
        if (preg_match('/^[a-z][a-z0-9+.-]*:$/', $lower) === 1) {
            $allowedIn = self::ALLOWED_SCHEME_SOURCES[$lower] ?? [];

            return in_array($directive, $allowedIn, true) ? null : 'scheme_source';
        }

        if (preg_match(self::HOST_SOURCE_PATTERN, $value, $m) !== 1) {
            return str_contains($value, '*') ? 'wildcard_any' : 'malformed';
        }

        // `*.com` / `https://*.localhost` would cover a whole TLD.
        if ($m[1] !== '' && ! str_contains($m[2], '.')) {
            return 'wildcard_any';
        }

        return null;
    }

    /**
     * Split directives into accepted values and rejected entries
     *
     * @param  array<mixed, mixed>  $directives  directive => values
     * @return array{accepted: array<string, array<int, string>>, rejected: array<int, array{directive: string, value: string, reason: string}>}
     */
    public function filter(array $directives): array
    {
        $accepted = [];
        $rejected = [];

        foreach ($directives as $directive => $values) {
            $directive = strtolower(trim((string) $directive));

            foreach ((array) $values as $value) {
                $reason = $this->rejection($directive, $value);

                if ($reason === null) {
                    $accepted[$directive][] = $value;

                    continue;
                }

                $rejected[] = [
                    'directive' => $directive,
                    'value' => is_scalar($value) ? (string) $value : get_debug_type($value),
                    'reason' => $reason,
                ];
            }
        }

        return ['accepted' => $accepted, 'rejected' => $rejected];
    }

    /**
     * Validate a quoted keyword source
     */
    private function keywordRejection(string $directive, string $lower): ?string
    {
        if ($lower === "'self'") {
            return null;
        }

        $scriptOrStyle = in_array($directive, self::SCRIPT_STYLE_DIRECTIVES, true);

        if ($lower === "'nonce'") {
            return $scriptOrStyle ? null : 'keyword_not_allowed';
        }

        if (preg_match("/^'sha(256|384|512)-[A-Za-z0-9+\/_-]+={0,2}'$/i", $lower) === 1) {
            return $scriptOrStyle ? null : 'keyword_not_allowed';
        }

        // A nonce written into a manifest is fixed and public, so it would
        // authorise any inline script that copies it.
        if (str_starts_with($lower, "'nonce-")) {
            return 'static_nonce';
        }

        if (str_contains($lower, 'unsafe')) {
            return 'unsafe_keyword';
        }

        // 'strict-dynamic' is governed by the admin's CSP mode; 'none' from
        // an extension would wipe the directive for every other source.
        return 'keyword_not_allowed';
    }
}
