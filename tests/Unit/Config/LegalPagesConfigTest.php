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
     * コアの設定ファイルがデフォルトで空配列を返すことを検証
     *
     * ページ種別はプラグイン（DixlaseLegal 等）から提供される。
     * コアは空のレジストリを維持する。
     */
    public function test_config_returns_empty_array_by_default(): void
    {
        $config = config('admin.legal-pages');

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }

    /**
     * プラグインが追加したページ種別が必須キーを持つことを検証
     */
    public function test_page_types_have_required_keys_when_provided(): void
    {
        $requiredKeys = ['name', 'description', 'required', 'icon'];

        // プラグインからページ種別が追加された場合のシミュレーション
        config(['admin.legal-pages' => [
            'test-page' => [
                'name' => 'Test Page',
                'description' => 'A test page.',
                'required' => false,
                'icon' => 'fas fa-file',
            ],
        ]]);

        $config = config('admin.legal-pages');

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
}
