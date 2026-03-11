<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Contracts\PluginIntegration;

use App\DTO\PluginIntegration\LinkableDTO;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * リンク可能なコンテンツを提供するプラグインの契約
 *
 * メニュープラグインなどが他のプラグインからコンテンツを
 * 取得するための共通インターフェースです。
 */
interface LinkableProviderInterface
{
    /**
     * プロバイダーの識別子を取得
     *
     * @return string 例: 'dixlase-pages', 'dixlase-blog'
     */
    public function getProviderKey(): string;

    /**
     * プロバイダーの表示名を取得
     *
     * @return string 例: 'ページ', 'ブログ記事'
     */
    public function getProviderLabel(): string;

    /**
     * プロバイダーのアイコンクラスを取得（オプション）
     *
     * @return string|null 例: 'fas fa-file-alt'
     */
    public function getProviderIcon(): ?string;

    /**
     * このプロバイダーが現在利用可能かどうか
     */
    public function isAvailable(): bool;

    /**
     * 利用可能なコンテンツのリストを取得
     *
     * @param  int  $limit  取得件数の上限（デフォルト: 100）
     * @return LinkableDTO[]
     */
    public function getAvailableItems(int $limit = 100): array;

    /**
     * 検索クエリに基づいてコンテンツを検索
     *
     * @param  string  $query  検索クエリ
     * @param  int  $limit  取得件数の上限（デフォルト: 20）
     * @return LinkableDTO[]
     */
    public function searchItems(string $query, int $limit = 20): array;

    /**
     * 特定のIDからコンテンツを取得
     *
     * @param  string  $id  コンテンツID
     */
    public function getItemById(string $id): ?LinkableDTO;
}
