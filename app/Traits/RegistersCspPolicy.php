<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Traits;

use App\Services\Csp\CspExtensionLoader;

/**
 * CSPポリシー登録トレイト
 *
 * プラグイン・テーマのServiceProviderでこのトレイトを使用することで、
 * plugin.json/theme.jsonに定義されたCSP設定を自動的にCspPolicyRegistryに登録できます。
 *
 * @example
 * class MyPluginServiceProvider extends ServiceProvider
 * {
 *     use RegistersCspPolicy;
 *
 *     public function boot()
 *     {
 *         $this->registerCspFromJson('plugin', 'my-plugin');
 *     }
 * }
 */
trait RegistersCspPolicy
{
    /**
     * plugin.json/theme.jsonからCSP設定を読み込み、レジストリに登録
     *
     * @param  string  $type  'plugin' または 'theme'
     * @param  string  $slug  プラグイン/テーマのスラッグ
     * @return array 登録されたディレクティブ
     */
    protected function registerCspFromJson(string $type, string $slug): array
    {
        try {
            $loader = app(CspExtensionLoader::class);

            if ($type === 'plugin') {
                return $loader->loadPlugin($slug);
            } else {
                return $loader->loadTheme($slug);
            }
        } catch (\Exception $e) {
            // Failed to register CSP
        }

        return [];
    }

    /**
     * CSPディレクティブを直接登録
     *
     * plugin.json/theme.jsonを使用せず、コードから直接CSPディレクティブを登録する場合に使用
     *
     * @param  array  $directives  ディレクティブ配列
     * @param  string|null  $source  ソース名（デバッグ用）
     */
    protected function registerCspDirectives(array $directives, ?string $source = null): void
    {
        try {
            $registry = app(\App\Services\Csp\CspPolicyRegistry::class);
            if (! empty($directives)) {
                $registry->addDirectives($directives, $source);
            }
        } catch (\Exception $e) {
            // Failed to register directives
        }
    }
}
