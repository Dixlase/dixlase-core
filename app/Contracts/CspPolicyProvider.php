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

namespace App\Contracts;

/**
 * CSP Policy Provider Interface
 * 
 * プラグイン・テーマがCSPポリシーを提供するためのインターフェース。
 * このインターフェースを実装することで、拡張機能が必要とする
 * 外部リソースをCSPに追加できる。
 * 
 * @example
 * class MyPluginServiceProvider implements CspPolicyProvider
 * {
 *     public function getCspDirectives(): array
 *     {
 *         return [
 *             'script-src' => ['https://cdn.example.com'],
 *             'style-src' => ['https://fonts.googleapis.com'],
 *             'connect-src' => ['https://api.example.com'],
 *         ];
 *     }
 * }
 */
interface CspPolicyProvider
{
    /**
     * CSPディレクティブを取得
     * 
     * @return array<string, array<string>> ディレクティブ名 => 値の配列
     * 
     * 使用可能なディレクティブ:
     * - default-src: デフォルトのフォールバック
     * - script-src: スクリプトソース
     * - style-src: スタイルソース
     * - img-src: 画像ソース
     * - font-src: フォントソース
     * - connect-src: 接続先（XHR, fetch, WebSocket等）
     * - media-src: メディアソース（audio, video）
     * - object-src: オブジェクトソース（plugin, embed, object）
     * - frame-src: フレームソース
     * - frame-ancestors: フレーム祖先
     * - form-action: フォーム送信先
     * - base-uri: ベースURI
     * - manifest-src: マニフェストソース
     * - worker-src: ワーカーソース
     */
    public function getCspDirectives(): array;
}
