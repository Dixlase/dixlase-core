<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

namespace App\Contracts\PluginIntegration;

use App\DTO\PluginIntegration\SeoMetaDTO;

/**
 * コンテンツ単位のSEOメタ情報の読み書きを提供するインターフェース
 *
 * SEOプラグインが実装を提供し、ページ生成系プラグイン（固定ページ、
 * リーガルページ、ブログ記事など）から利用されます。SEOプラグイン
 * 未インストール時はこの契約の実装はコンテナに登録されないため、
 * 呼び出し側は `app()->has(SeoMetaProviderInterface::class)` で
 * チェックしてから解決するか、optional resolution パターンを使用してください。
 *
 * メタ情報は `(plugin_slug, entity_id)` のペアで一意に識別されます。
 * `plugin_slug` は呼び出し元プラグインのスラッグ（例: `dixlase-pages`）、
 * `entity_id` は各プラグイン内での対象エンティティの識別子
 * （通常はコンテンツモデルのID）を文字列として渡します。
 */
interface SeoMetaProviderInterface
{
    /**
     * 指定プラグインの指定エンティティのメタ情報を取得
     *
     * @param  string  $pluginSlug  呼び出し元プラグインのスラッグ
     * @param  string  $entityId  対象エンティティのID
     * @return SeoMetaDTO|null 未登録の場合はnull
     */
    public function getMeta(string $pluginSlug, string $entityId): ?SeoMetaDTO;

    /**
     * メタ情報を保存（upsert）
     *
     * @param  string  $pluginSlug  呼び出し元プラグインのスラッグ
     * @param  string  $entityId  対象エンティティのID
     * @param  SeoMetaDTO  $meta  保存するメタ情報
     */
    public function saveMeta(string $pluginSlug, string $entityId, SeoMetaDTO $meta): void;

    /**
     * 単一のメタ情報を削除
     *
     * @param  string  $pluginSlug  呼び出し元プラグインのスラッグ
     * @param  string  $entityId  対象エンティティのID
     */
    public function deleteMeta(string $pluginSlug, string $entityId): void;

    /**
     * 指定プラグインの全メタ情報を一括削除
     *
     * プラグインアンインストール時のカスケードクリーンアップに使用します。
     *
     * @param  string  $pluginSlug  対象プラグインのスラッグ
     * @return int 削除された件数
     */
    public function purgeByPlugin(string $pluginSlug): int;

    /**
     * SEOプラグインの設定で、指定プラグインのSEOメタ機能が
     * 有効化されているかを確認
     *
     * 各ページ生成プラグインの編集画面でSEOフィールドを表示するかどうかの
     * 判定に使用します。
     *
     * @param  string  $pluginSlug  対象プラグインのスラッグ
     */
    public function isEnabledForPlugin(string $pluginSlug): bool;
}
