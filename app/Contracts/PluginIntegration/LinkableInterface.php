<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Contracts\PluginIntegration;

/**
 * リンク可能なコンテンツの最小契約
 *
 * プラグイン間連携の基盤として使用します。
 * メニュー、検索、タグ付けなど、複数のプラグインで
 * コンテンツを参照する際の共通インターフェースです。
 */
interface LinkableInterface
{
    /**
     * コンテンツの一意なID（ULID/UUID）を取得
     */
    public function getId(): string;

    /**
     * コンテンツのタイトルを取得
     */
    public function getTitle(): string;

    /**
     * コンテンツのURLを取得
     */
    public function getUrl(): string;

    /**
     * コンテンツのタイプを取得
     *
     * 例: 'post', 'page', 'media', 'product', 'inquiry'
     */
    public function getType(): string;

    /**
     * コンテンツのソース（提供元）を取得
     *
     * - コアの場合: 'core'
     * - プラグインの場合: プラグインスラッグ（例: 'dixlase-blog'）
     */
    public function getSource(): string;

    /**
     * コンテンツのソーステーブル名を取得（オプション）
     *
     * デバッグやデータ整合性チェックに使用
     */
    public function getSourceTable(): ?string;
}
