<?php
/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
 * Dixlase ヘルパークラス
 * 
 * CSPに対応したスクリプト・スタイルの出力を提供します。
 * プラグイン・テーマ開発者はこのヘルパーを使用することで、
 * CSPモードに関係なく安全にインラインコードを出力できます。
 */
class Dixlase
{
    /**
     * CSP対応のインラインスクリプトを出力
     * 
     * @param string $code JavaScriptコード
     * @param array $options オプション（defer, async, type等）
     * @return HtmlString
     */
    public static function script(string $code, array $options = []): HtmlString
    {
        $nonce = self::getNonce();
        $attributes = self::buildAttributes($options, $nonce, 'script');
        
        $code = trim($code);
        
        return new HtmlString("<script{$attributes}>\n{$code}\n</script>");
    }

    /**
     * CSP対応のインラインスタイルを出力
     * 
     * @param string $css CSSコード
     * @param array $options オプション
     * @return HtmlString
     */
    public static function style(string $css, array $options = []): HtmlString
    {
        $nonce = self::getNonce();
        $attributes = self::buildAttributes($options, $nonce, 'style');
        
        $css = trim($css);
        
        return new HtmlString("<style{$attributes}>\n{$css}\n</style>");
    }

    /**
     * CSP対応の外部スクリプトタグを出力
     * 
     * @param string $src スクリプトURL
     * @param array $options オプション（defer, async, integrity等）
     * @return HtmlString
     */
    public static function scriptSrc(string $src, array $options = []): HtmlString
    {
        $attributes = self::buildSrcAttributes($options, $src);
        
        return new HtmlString("<script{$attributes}></script>");
    }

    /**
     * CSP対応の外部スタイルシートタグを出力
     * 
     * @param string $href スタイルシートURL
     * @param array $options オプション（integrity, media等）
     * @return HtmlString
     */
    public static function styleSrc(string $href, array $options = []): HtmlString
    {
        $rel = $options['rel'] ?? 'stylesheet';
        unset($options['rel']);
        
        $attributes = self::buildSrcAttributes($options, $href, 'href');
        
        return new HtmlString("<link rel=\"{$rel}\"{$attributes}>");
    }

    /**
     * 現在のnonceを取得
     * 
     * @return string
     */
    public static function getNonce(): string
    {
        return app(CspNonceGenerator::class)->getNonce();
    }

    /**
     * nonce属性のみを取得（カスタム用途向け）
     * 
     * @return string
     */
    public static function nonceAttribute(): string
    {
        return 'nonce="' . self::getNonce() . '"';
    }

    /**
     * 現在のCSPモードを取得
     * 
     * @return string 'development', 'standard', 'strict'
     */
    public static function getCspMode(): string
    {
        try {
            return \App\Models\SecuritySetting::get('csp_mode', 'development');
        } catch (\Exception $e) {
            return config('csp.mode', 'development');
        }
    }

    /**
     * 現在のCSPモード設定を取得
     * 
     * @return array
     */
    public static function getCspModeConfig(): array
    {
        $mode = self::getCspMode();
        return config("csp.modes.{$mode}", config('csp.modes.development'));
    }

    /**
     * CSPが有効かどうか
     * 
     * @return bool
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
     * 厳格モードかどうか
     * 
     * @return bool
     */
    public static function isStrictMode(): bool
    {
        return self::getCspMode() === 'strict';
    }

    /**
     * 開発モードかどうか
     * 
     * @return bool
     */
    public static function isDevelopmentMode(): bool
    {
        return self::getCspMode() === 'development';
    }

    /**
     * nonceが必要かどうか
     * 
     * @return bool
     */
    public static function requiresNonce(): bool
    {
        $config = self::getCspModeConfig();
        return $config['require_nonce'] ?? false;
    }

    /**
     * インラインスクリプトが許可されているか
     * 
     * @return bool
     */
    public static function allowsInlineScripts(): bool
    {
        $config = self::getCspModeConfig();
        return $config['allow_inline_scripts'] ?? false;
    }

    /**
     * インラインプラグインがブロックされるか
     * 
     * @return bool
     */
    public static function blocksInlinePlugins(): bool
    {
        $config = self::getCspModeConfig();
        return $config['block_inline_plugins'] ?? false;
    }

    /**
     * 属性文字列を構築
     */
    protected static function buildAttributes(array $options, string $nonce, string $type): string
    {
        $attrs = [];
        
        // nonceは常に付与
        $attrs[] = "nonce=\"{$nonce}\"";
        
        if ($type === 'script') {
            // defer/asyncオプション
            if (!empty($options['defer'])) {
                $attrs[] = 'defer';
            }
            if (!empty($options['async'])) {
                $attrs[] = 'async';
            }
            // typeオプション（module等）
            if (!empty($options['type'])) {
                $attrs[] = 'type="' . e($options['type']) . '"';
            }
        }
        
        // カスタム属性
        foreach ($options as $key => $value) {
            if (in_array($key, ['defer', 'async', 'type', 'nonce'])) {
                continue;
            }
            if ($value === true) {
                $attrs[] = e($key);
            } elseif ($value !== false && $value !== null) {
                $attrs[] = e($key) . '="' . e($value) . '"';
            }
        }
        
        return $attrs ? ' ' . implode(' ', $attrs) : '';
    }

    /**
     * 外部リソース用の属性文字列を構築
     */
    protected static function buildSrcAttributes(array $options, string $url, string $urlAttr = 'src'): string
    {
        $attrs = [];
        
        // URL
        $attrs[] = "{$urlAttr}=\"" . e($url) . '"';
        
        // defer/async（scriptのみ）
        if ($urlAttr === 'src') {
            if (!empty($options['defer'])) {
                $attrs[] = 'defer';
            }
            if (!empty($options['async'])) {
                $attrs[] = 'async';
            }
        }
        
        // integrity（SRI）
        if (!empty($options['integrity'])) {
            $attrs[] = 'integrity="' . e($options['integrity']) . '"';
            $attrs[] = 'crossorigin="anonymous"';
        }
        
        // crossorigin
        if (!empty($options['crossorigin']) && empty($options['integrity'])) {
            $attrs[] = 'crossorigin="' . e($options['crossorigin']) . '"';
        }
        
        // その他の属性
        foreach ($options as $key => $value) {
            if (in_array($key, ['defer', 'async', 'integrity', 'crossorigin', 'src', 'href'])) {
                continue;
            }
            if ($value === true) {
                $attrs[] = e($key);
            } elseif ($value !== false && $value !== null) {
                $attrs[] = e($key) . '="' . e($value) . '"';
            }
        }
        
        return ' ' . implode(' ', $attrs);
    }
}
