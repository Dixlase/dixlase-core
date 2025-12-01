<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

use App\Contracts\Multilingual;

if (!function_exists('multilingual')) {
    /**
     * 多言語サービスのインスタンスを取得
     * 
     * 多言語プラグインが有効な場合は本物の実装を返し、
     * 無効な場合はダミー実装（単一言語モード）を返します。
     * 
     * テーマやプラグインは常にこのヘルパーを通じて多言語機能にアクセスします。
     * これにより、多言語プラグインの有無に関わらず同じコードで動作します。
     *
     * @return \App\Contracts\Multilingual
     * 
     * @example
     * // 多言語が有効かチェック
     * if (multilingual()->isEnabled()) {
     *     // 多言語モード
     * }
     * 
     * // 翻訳された値を取得
     * $title = multilingual()->getTranslated($page, 'title');
     * 
     * // 言語切替URLを生成
     * $url = multilingual()->switchUrl('en');
     */
    function multilingual(): Multilingual
    {
        return app(Multilingual::class);
    }
}

if (!function_exists('shortcode_parse')) {
    /**
     * ショートコードをパースして実行
     *
     * @param string $content パース対象のコンテンツ
     * @return string パース後のコンテンツ
     */
    function shortcode_parse($content)
    {
        if (!app()->bound('shortcode')) {
            return $content;
        }
        
        return app('shortcode')->parse($content);
    }
}
