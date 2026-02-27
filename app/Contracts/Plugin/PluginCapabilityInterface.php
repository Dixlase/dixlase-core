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

namespace App\Contracts\Plugin;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * プラグイン機能宣言の基底インターフェース
 *
 * プラグインが特定の機能を提供する場合に実装する基底インターフェースです。
 * 各機能固有のインターフェース（MailCapableInterface 等）はこれを継承します。
 *
 * プラグインの ServiceProvider でサービスコンテナにタグ付き登録することで、
 * PluginServiceResolver が自動的に発見・解決します。
 */
interface PluginCapabilityInterface
{
    /**
     * プラグインのスラッグを取得
     *
     * @return string 例: 'dixlase-inquiry'
     */
    public function getPluginSlug(): string;

    /**
     * この機能が現在利用可能かどうか
     *
     * プラグインの設定状態や依存関係により、
     * 機能が一時的に無効になる場合があります。
     */
    public function isCapabilityAvailable(): bool;
}
