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

declare(strict_types=1);

namespace App\Contracts\Plugin;

use App\DTO\Api\ApiResourceCollection;
use App\DTO\Api\ApiResourceDTO;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * APIリソースプロバイダーインターフェース
 *
 * プラグインがREST API経由でコンテンツリソースを公開する際に実装します。
 * DixlaseApi プラグインがこのインターフェースの実装を自動発見し、
 * 統一的なAPI エンドポイントとして提供します。
 */
interface ApiResourceProviderInterface extends PluginCapabilityInterface
{
    /**
     * リソースタイプ識別子を取得
     *
     * @return string 例: 'pages', 'posts'
     */
    public function getResourceType(): string;

    /**
     * リソースの表示名を取得
     *
     * @return string 例: 'ページ', 'ブログ記事'
     */
    public function getResourceLabel(): string;

    /**
     * リソース一覧を取得（ページネーション対応）
     *
     * @param  int  $page  ページ番号
     * @param  int  $perPage  ページあたりの件数
     * @param  array<string, mixed>  $filters  フィルター条件
     */
    public function listResources(int $page = 1, int $perPage = 20, array $filters = []): ApiResourceCollection;

    /**
     * スラッグでリソースを取得
     *
     * @param  string  $slug  リソースのスラッグ
     */
    public function findBySlug(string $slug): ?ApiResourceDTO;

    /**
     * IDでリソースを取得
     *
     * @param  string  $id  リソースID
     */
    public function findById(string $id): ?ApiResourceDTO;

    /**
     * 利用可能なフィルターキーを返す
     *
     * @return array<string, string> キー => 説明 の連想配列
     */
    public function getAvailableFilters(): array;
}
