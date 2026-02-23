<?php

/**
 * This file is part of Dixlase Legal.
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

namespace Plugins\DixlaseLegal\App\Services;

use App\Contracts\PluginIntegration\PrivacyPolicyProviderInterface;
use App\Services\LegalPageService;

/**
 * プライバシーポリシープロバイダーの実装
 *
 * LegalPageService を利用してプライバシーポリシーの情報を提供する。
 * 他のプラグイン（DixlaseInquiry 等）がこのインターフェース経由で
 * プライバシーポリシー URL を取得できるようにする。
 */
class DixlaseLegalPrivacyPolicyProvider implements PrivacyPolicyProviderInterface
{
    public function __construct(
        private LegalPageService $legalPageService,
    ) {}

    /**
     * プライバシーポリシーのURLを取得
     */
    public function getPrivacyPolicyUrl(): ?string
    {
        return $this->legalPageService->url('privacy-policy');
    }

    /**
     * プライバシーポリシーが有効かどうか
     */
    public function isPrivacyPolicyEnabled(): bool
    {
        return $this->legalPageService->exists('privacy-policy');
    }

    /**
     * プライバシーポリシーのラベルテキストを取得
     */
    public function getPrivacyPolicyLabel(): string
    {
        $types = $this->legalPageService->getPageTypes();

        if (isset($types['privacy-policy']['name'])) {
            return __($types['privacy-policy']['name']);
        }

        return __('dixlase-legal::admin/legal-pages/page-types.privacy_policy.name');
    }
}
