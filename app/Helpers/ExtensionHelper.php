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

namespace App\Helpers;

use App\Services\Csp\CspNonceGenerator;
use Illuminate\Support\HtmlString;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Helper class for extension developers
 *
 * Provides public API for plugin and theme developers.
 *
 * CSP functionality:
 * - Output scripts and styles compatible with CSP
 * - Get and set CSP mode
 * - Generate nonces and build HTML attributes
 *
 * By using this helper, plugin and theme developers can
 * safely output inline code regardless of CSP mode.
 *
 * Usage examples:
 * - ExtensionHelper::script('console.log("Hello");')
 * - ExtensionHelper::getCspMode()
 */
class ExtensionHelper
{
    /**
     * Output inline script compatible with CSP
     *
     * @param  string  $code  JavaScript code
     * @param  array  $options  Options (defer, async, type, etc.)
     */
    public static function script(string $code, array $options = []): HtmlString
    {
        $nonce = self::getNonce();
        $attributes = self::buildAttributes($options, $nonce, 'script');

        $code = trim($code);

        return new HtmlString("<script{$attributes}>\n{$code}\n</script>");
    }

    /**
     * Output inline style compatible with CSP
     *
     * @param  string  $css  CSS code
     * @param  array  $options  Options
     */
    public static function style(string $css, array $options = []): HtmlString
    {
        $nonce = self::getNonce();
        $attributes = self::buildAttributes($options, $nonce, 'style');

        $css = trim($css);

        return new HtmlString("<style{$attributes}>\n{$css}\n</style>");
    }

    /**
     * Output external script tag compatible with CSP
     *
     * @param  string  $src  Script URL
     * @param  array  $options  Options (defer, async, integrity, etc.)
     */
    public static function scriptSrc(string $src, array $options = []): HtmlString
    {
        $attributes = self::buildSrcAttributes($options, $src);

        return new HtmlString("<script{$attributes}></script>");
    }

    /**
     * Output external stylesheet tag compatible with CSP
     *
     * @param  string  $href  Stylesheet URL
     * @param  array  $options  Options (integrity, media, etc.)
     */
    public static function styleSrc(string $href, array $options = []): HtmlString
    {
        $rel = $options['rel'] ?? 'stylesheet';
        unset($options['rel']);

        $attributes = self::buildSrcAttributes($options, $href, 'href');

        return new HtmlString("<link rel=\"{$rel}\"{$attributes}>");
    }

    /**
     * Get current nonce
     */
    public static function getNonce(): string
    {
        return app(CspNonceGenerator::class)->getNonce();
    }

    /**
     * Get only nonce attribute (for custom use)
     */
    public static function nonceAttribute(): string
    {
        return 'nonce="'.self::getNonce().'"';
    }

    /**
     * Get current CSP mode
     *
     * @return string 'development', 'standard', 'strict'
     */
    public static function getCspMode(): string
    {
        try {
            $modeValue = \App\Models\SecuritySetting::get('csp_mode', (string) \App\Enums\CspMode::default()->value);
            $mode = \App\Enums\CspMode::fromValue($modeValue);

            return $mode ? $mode->toString() : \App\Enums\CspMode::default()->toString();
        } catch (\Exception $e) {
            return config('csp.mode', 'standard');
        }
    }

    /**
     * Get current CSP mode Enum
     */
    public static function getCspModeEnum(): \App\Enums\CspMode
    {
        try {
            $modeValue = \App\Models\SecuritySetting::get('csp_mode', (string) \App\Enums\CspMode::default()->value);

            return \App\Enums\CspMode::fromValue($modeValue) ?? \App\Enums\CspMode::default();
        } catch (\Exception $e) {
            return \App\Enums\CspMode::default();
        }
    }

    /**
     * Get current CSP mode settings
     */
    public static function getCspModeConfig(): array
    {
        $mode = self::getCspMode();

        return config("csp.modes.{$mode}", config('csp.modes.standard'));
    }

    /**
     * Whether CSP is enabled
     */
    public static function isCspEnabled(): bool
    {
        try {
            return (bool) \App\Models\SecuritySetting::get('csp_enabled', true);
        } catch (\Exception $e) {
            return config('csp.enabled', true);
        }
    }

    /**
     * Whether strict mode
     */
    public static function isStrictMode(): bool
    {
        return self::getCspMode() === 'strict';
    }

    /**
     * Whether development mode
     */
    public static function isDevelopmentMode(): bool
    {
        return self::getCspMode() === 'development';
    }

    /**
     * Whether nonce is required
     */
    public static function requiresNonce(): bool
    {
        $config = self::getCspModeConfig();

        return $config['require_nonce'] ?? false;
    }

    /**
     * Whether inline scripts are allowed
     */
    public static function allowsInlineScripts(): bool
    {
        $config = self::getCspModeConfig();

        return $config['allow_inline_scripts'] ?? false;
    }

    /**
     * Whether inline plugins are blocked
     */
    public static function blocksInlinePlugins(): bool
    {
        $config = self::getCspModeConfig();

        return $config['block_inline_plugins'] ?? false;
    }

    /**
     * Build attribute string
     */
    protected static function buildAttributes(array $options, string $nonce, string $type): string
    {
        $attrs = [];

        // Always add nonce
        $attrs[] = "nonce=\"{$nonce}\"";

        if ($type === 'script') {
            // defer/async option
            if (! empty($options['defer'])) {
                $attrs[] = 'defer';
            }
            if (! empty($options['async'])) {
                $attrs[] = 'async';
            }
            // type option (module, etc.)
            if (! empty($options['type'])) {
                $attrs[] = 'type="'.e($options['type']).'"';
            }
        }

        // Custom attributes
        foreach ($options as $key => $value) {
            if (in_array($key, ['defer', 'async', 'type', 'nonce'])) {
                continue;
            }
            if ($value === true) {
                $attrs[] = e($key);
            } elseif ($value !== false && $value !== null) {
                $attrs[] = e($key).'="'.e($value).'"';
            }
        }

        return $attrs ? ' '.implode(' ', $attrs) : '';
    }

    /**
     * Build attribute string for external resources
     */
    protected static function buildSrcAttributes(array $options, string $url, string $urlAttr = 'src'): string
    {
        $attrs = [];

        // URL
        $attrs[] = "{$urlAttr}=\"".e($url).'"';

        // defer/async (script only)
        if ($urlAttr === 'src') {
            if (! empty($options['defer'])) {
                $attrs[] = 'defer';
            }
            if (! empty($options['async'])) {
                $attrs[] = 'async';
            }
        }

        // integrity（SRI）
        if (! empty($options['integrity'])) {
            $attrs[] = 'integrity="'.e($options['integrity']).'"';
            $attrs[] = 'crossorigin="anonymous"';
        }

        // crossorigin
        if (! empty($options['crossorigin']) && empty($options['integrity'])) {
            $attrs[] = 'crossorigin="'.e($options['crossorigin']).'"';
        }

        // Other attributes
        foreach ($options as $key => $value) {
            if (in_array($key, ['defer', 'async', 'integrity', 'crossorigin', 'src', 'href'])) {
                continue;
            }
            if ($value === true) {
                $attrs[] = e($key);
            } elseif ($value !== false && $value !== null) {
                $attrs[] = e($key).'="'.e($value).'"';
            }
        }

        return ' '.implode(' ', $attrs);
    }
}
