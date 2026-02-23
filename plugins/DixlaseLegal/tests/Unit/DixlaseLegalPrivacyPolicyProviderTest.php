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

namespace Plugins\DixlaseLegal\Tests\Unit;

use App\Services\LegalPageService;
use Plugins\DixlaseLegal\App\Services\DixlaseLegalPrivacyPolicyProvider;
use Tests\TestCase;

/**
 * PrivacyPolicyProvider ユニットテスト
 */
class DixlaseLegalPrivacyPolicyProviderTest extends TestCase
{
    /**
     * URL が設定されている場合に返却されること
     */
    public function test_returns_url_when_set(): void
    {
        $mockService = $this->createMock(LegalPageService::class);
        $mockService->method('url')
            ->with('privacy-policy')
            ->willReturn('https://example.com/privacy');

        $provider = new DixlaseLegalPrivacyPolicyProvider($mockService);

        $this->assertEquals('https://example.com/privacy', $provider->getPrivacyPolicyUrl());
    }

    /**
     * URL が未設定の場合に null を返すこと
     */
    public function test_returns_null_when_url_not_set(): void
    {
        $mockService = $this->createMock(LegalPageService::class);
        $mockService->method('url')
            ->with('privacy-policy')
            ->willReturn(null);

        $provider = new DixlaseLegalPrivacyPolicyProvider($mockService);

        $this->assertNull($provider->getPrivacyPolicyUrl());
    }

    /**
     * URL が設定済みの場合に有効と判定されること
     */
    public function test_is_enabled_when_url_exists(): void
    {
        $mockService = $this->createMock(LegalPageService::class);
        $mockService->method('exists')
            ->with('privacy-policy')
            ->willReturn(true);

        $provider = new DixlaseLegalPrivacyPolicyProvider($mockService);

        $this->assertTrue($provider->isPrivacyPolicyEnabled());
    }

    /**
     * URL が未設定の場合に無効と判定されること
     */
    public function test_is_disabled_when_url_not_exists(): void
    {
        $mockService = $this->createMock(LegalPageService::class);
        $mockService->method('exists')
            ->with('privacy-policy')
            ->willReturn(false);

        $provider = new DixlaseLegalPrivacyPolicyProvider($mockService);

        $this->assertFalse($provider->isPrivacyPolicyEnabled());
    }

    /**
     * ラベルテキストが取得できること
     */
    public function test_returns_label_text(): void
    {
        $mockService = $this->createMock(LegalPageService::class);
        $mockService->method('getPageTypes')
            ->willReturn([
                'privacy-policy' => [
                    'name' => 'dixlase-legal::admin/legal-pages/page-types.privacy_policy.name',
                    'description' => 'dixlase-legal::admin/legal-pages/page-types.privacy_policy.description',
                    'required' => false,
                    'icon' => 'fas fa-shield-alt',
                ],
            ]);

        $provider = new DixlaseLegalPrivacyPolicyProvider($mockService);

        $label = $provider->getPrivacyPolicyLabel();
        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }
}
