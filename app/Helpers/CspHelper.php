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

use App\Services\Csp\CspBuilder;
use App\Services\Csp\CspNonceGenerator;
use App\Services\Csp\CspPolicyRegistry;

if (! function_exists('csp_nonce')) {
    /**
     * Get the CSP nonce value for the current request
     *
     * @return string nonce value
     *
     * @example
     * <script nonce="{{ csp_nonce() }}">
     *     // Inline script
     * </script>
     */
    function csp_nonce(): string
    {
        // Prioritize using the nonce value stored in request attributes
        // (to match the value set by CSP middleware)
        $request = request();
        if ($request && $request->attributes->has('csp_nonce')) {
            return $request->attributes->get('csp_nonce');
        }

        // Fallback: get from CspNonceGenerator
        return app(CspNonceGenerator::class)->getNonce();
    }
}

if (! function_exists('csp_nonce_attr')) {
    /**
     * Get CSP nonce attribute (including attribute name)
     *
     * @return string String in nonce="xxx" format
     *
     * @example
     * <script {!! csp_nonce_attr() !!}>
     *     // Inline script
     * </script>
     */
    function csp_nonce_attr(): string
    {
        return app(CspNonceGenerator::class)->getNonceAttribute();
    }
}

if (! function_exists('csp_meta')) {
    /**
     * Output CSP as meta tag
     *
     * Alternative when HTTP headers cannot be used.
     * However, some directives such as report-uri do not work in meta tags.
     *
     * @return string meta tag HTML
     */
    function csp_meta(): string
    {
        $builder = app(CspBuilder::class);

        if (! $builder->isEnabled()) {
            return '';
        }

        $policy = $builder->build();

        // Remove report-uri as it cannot be used in meta tags
        $policy = preg_replace('/;\s*report-uri\s+[^;]+/', '', $policy);

        return '<meta http-equiv="Content-Security-Policy" content="'.e($policy).'">';
    }
}

if (! function_exists('csp_add_directive')) {
    /**
     * Dynamically add CSP directive
     *
     * Register additional directives from Blade templates or controllers.
     *
     * @param  string  $directive  Directive name
     * @param  array|string  $values  Value (array or string)
     * @param  string|null  $source  Source name (for debugging)
     *
     * @example
     * // In controller
     * csp_add_directive('script-src', 'https://cdn.example.com');
     *
     * // In Blade
     *
     * @php csp_add_directive('connect-src', ['https://api.example.com']) @endphp
     */
    function csp_add_directive(string $directive, array|string $values, ?string $source = null): void
    {
        $values = is_array($values) ? $values : [$values];
        app(CspPolicyRegistry::class)->addDirective($directive, $values, $source);
    }
}

if (! function_exists('csp_add_script_src')) {
    /**
     * Add a value to the script-src directive
     */
    function csp_add_script_src(array|string $values): void
    {
        csp_add_directive('script-src', $values);
    }
}

if (! function_exists('csp_add_style_src')) {
    /**
     * Add a value to the style-src directive
     */
    function csp_add_style_src(array|string $values): void
    {
        csp_add_directive('style-src', $values);
    }
}

if (! function_exists('csp_add_connect_src')) {
    /**
     * Add a value to the connect-src directive
     */
    function csp_add_connect_src(array|string $values): void
    {
        csp_add_directive('connect-src', $values);
    }
}

if (! function_exists('csp_add_img_src')) {
    /**
     * Add a value to the img-src directive
     */
    function csp_add_img_src(array|string $values): void
    {
        csp_add_directive('img-src', $values);
    }
}

if (! function_exists('csp_add_frame_src')) {
    /**
     * Add a value to the frame-src directive
     */
    function csp_add_frame_src(array|string $values): void
    {
        csp_add_directive('frame-src', $values);
    }
}

if (! function_exists('csp_is_enabled')) {
    /**
     * Check if CSP is enabled
     */
    function csp_is_enabled(): bool
    {
        return app(CspBuilder::class)->isEnabled();
    }
}

if (! function_exists('csp_get_mode')) {
    /**
     * Get CSP mode
     *
     * @return string 'enforce' or 'report-only'
     */
    function csp_get_mode(): string
    {
        return app(CspBuilder::class)->getMode();
    }
}
