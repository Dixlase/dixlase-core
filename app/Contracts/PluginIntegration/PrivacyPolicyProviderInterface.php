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

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * プライバシーポリシープロバイダーの契約
 *
 * 法務プラグインなどがこのインターフェースを実装して
 * ServiceContainerに登録することで、他のプラグインが
 * プライバシーポリシー情報を取得できるようになります。
 */
interface PrivacyPolicyProviderInterface
{
    /**
     * プライバシーポリシーのURLを取得
     */
    public function getPrivacyPolicyUrl(): ?string;

    /**
     * プライバシーポリシーが有効かどうか
     */
    public function isPrivacyPolicyEnabled(): bool;

    /**
     * プライバシーポリシーのラベルテキストを取得
     */
    public function getPrivacyPolicyLabel(): string;
}
