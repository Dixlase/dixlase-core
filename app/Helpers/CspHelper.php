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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
     * 現在のリクエストのCSP nonce値を取得
     *
     * @return string nonce値
     *
     * @example
     * <script nonce="{{ csp_nonce() }}">
     *     // インラインスクリプト
     * </script>
     */
    function csp_nonce(): string
    {
        // リクエスト属性に保存されたnonce値を優先的に使用
        // （CSPミドルウェアが設定した値と一致させるため）
        $request = request();
        if ($request && $request->attributes->has('csp_nonce')) {
            return $request->attributes->get('csp_nonce');
        }

        // フォールバック: CspNonceGeneratorから取得
        return app(CspNonceGenerator::class)->getNonce();
    }
}

if (! function_exists('csp_nonce_attr')) {
    /**
     * CSP nonce属性を取得（属性名込み）
     *
     * @return string nonce="xxx" 形式の文字列
     *
     * @example
     * <script {!! csp_nonce_attr() !!}>
     *     // インラインスクリプト
     * </script>
     */
    function csp_nonce_attr(): string
    {
        return app(CspNonceGenerator::class)->getNonceAttribute();
    }
}

if (! function_exists('csp_meta')) {
    /**
     * CSPをmetaタグとして出力
     *
     * HTTPヘッダーが使えない場合の代替手段。
     * ただし、report-uriなど一部のディレクティブはmetaタグでは動作しない。
     *
     * @return string metaタグHTML
     */
    function csp_meta(): string
    {
        $builder = app(CspBuilder::class);

        if (! $builder->isEnabled()) {
            return '';
        }

        $policy = $builder->build();

        // metaタグではreport-uriは使えないので除去
        $policy = preg_replace('/;\s*report-uri\s+[^;]+/', '', $policy);

        return '<meta http-equiv="Content-Security-Policy" content="'.e($policy).'">';
    }
}

if (! function_exists('csp_add_directive')) {
    /**
     * CSPディレクティブを動的に追加
     *
     * Bladeテンプレートやコントローラーから追加のディレクティブを登録する。
     *
     * @param  string  $directive  ディレクティブ名
     * @param  array|string  $values  値（配列または文字列）
     * @param  string|null  $source  ソース名（デバッグ用）
     *
     * @example
     * // コントローラーで
     * csp_add_directive('script-src', 'https://cdn.example.com');
     *
     * // Bladeで
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
     * script-srcディレクティブに値を追加
     */
    function csp_add_script_src(array|string $values): void
    {
        csp_add_directive('script-src', $values);
    }
}

if (! function_exists('csp_add_style_src')) {
    /**
     * style-srcディレクティブに値を追加
     */
    function csp_add_style_src(array|string $values): void
    {
        csp_add_directive('style-src', $values);
    }
}

if (! function_exists('csp_add_connect_src')) {
    /**
     * connect-srcディレクティブに値を追加
     */
    function csp_add_connect_src(array|string $values): void
    {
        csp_add_directive('connect-src', $values);
    }
}

if (! function_exists('csp_add_img_src')) {
    /**
     * img-srcディレクティブに値を追加
     */
    function csp_add_img_src(array|string $values): void
    {
        csp_add_directive('img-src', $values);
    }
}

if (! function_exists('csp_add_frame_src')) {
    /**
     * frame-srcディレクティブに値を追加
     */
    function csp_add_frame_src(array|string $values): void
    {
        csp_add_directive('frame-src', $values);
    }
}

if (! function_exists('csp_is_enabled')) {
    /**
     * CSPが有効かどうかを確認
     */
    function csp_is_enabled(): bool
    {
        return app(CspBuilder::class)->isEnabled();
    }
}

if (! function_exists('csp_get_mode')) {
    /**
     * CSPモードを取得
     *
     * @return string 'enforce' または 'report-only'
     */
    function csp_get_mode(): string
    {
        return app(CspBuilder::class)->getMode();
    }
}
