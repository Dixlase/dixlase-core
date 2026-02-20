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

namespace Tests\Unit\Config;

use Tests\TestCase;

class LegalPagesConfigTest extends TestCase
{
    /**
     * 設定ファイルが4つのデフォルトページ種別を持つことを検証
     */
    public function test_config_has_four_default_page_types(): void
    {
        $config = config('admin.legal-pages');

        $this->assertIsArray($config);
        $this->assertCount(4, $config);
        $this->assertArrayHasKey('privacy-policy', $config);
        $this->assertArrayHasKey('terms-of-service', $config);
        $this->assertArrayHasKey('site-policy', $config);
        $this->assertArrayHasKey('cookie-policy', $config);
    }

    /**
     * すべてのページ種別が必須キーを持つことを検証
     */
    public function test_all_page_types_have_required_keys(): void
    {
        $config = config('admin.legal-pages');
        $requiredKeys = ['name', 'description', 'required', 'icon'];

        foreach ($config as $slug => $type) {
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $type,
                    "ページ種別 '{$slug}' にキー '{$key}' がありません"
                );
            }
        }
    }

    /**
     * すべてのページ種別がデフォルトで required: false であることを検証
     */
    public function test_all_page_types_default_to_not_required(): void
    {
        $config = config('admin.legal-pages');

        foreach ($config as $slug => $type) {
            $this->assertFalse(
                $type['required'],
                "ページ種別 '{$slug}' の required がデフォルトで false ではありません"
            );
        }
    }
}
