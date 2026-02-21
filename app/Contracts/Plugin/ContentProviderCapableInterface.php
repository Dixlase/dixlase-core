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

namespace App\Contracts\Plugin;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * コンテンツ提供機能を宣言するインターフェース
 *
 * 他のプラグインにコンテンツを提供するプラグインが実装します。
 * 既存の LinkableProviderInterface を拡張し、
 * PluginServiceResolver で権限チェック付きの解決を可能にします。
 */
interface ContentProviderCapableInterface extends PluginCapabilityInterface
{
    /**
     * 提供するコンテンツの種類を取得
     *
     * @return array<string> 例: ['page', 'post']
     */
    public function getContentTypes(): array;

    /**
     * 指定したコンテンツタイプを提供しているか
     */
    public function providesContentType(string $type): bool;
}
