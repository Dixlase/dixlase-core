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

namespace App\Contracts\LegalPage;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * 法務ページレジストリサービスの契約
 *
 * コアとプラグインの法務ページ種別を統合管理し、
 * URL の取得・設定・必須チェック等を提供します。
 */
interface LegalPageServiceInterface
{
    /**
     * コア + プラグインの統合ページ種別一覧を取得
     *
     * @return array<string, array{name: string, description: string, required: bool, icon: string, required_by?: list<string>}>
     */
    public function getPageTypes(): array;

    /**
     * 指定ページ種別が必須かどうかを判定
     */
    public function isRequired(string $slug): bool;

    /**
     * 指定ページ種別の URL が設定済みかどうかを判定
     */
    public function exists(string $slug): bool;

    /**
     * 指定ページ種別の URL を取得
     */
    public function url(string $slug): ?string;

    /**
     * 必須だが URL 未設定のページ種別一覧を取得
     *
     * @return array<string, array{name: string, description: string, required: bool, icon: string}>
     */
    public function missingRequired(): array;

    /**
     * 指定ページ種別の URL を設定（null で削除）
     */
    public function setUrl(string $slug, ?string $url): void;
}
