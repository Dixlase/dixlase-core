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

namespace App\Services;

/**
 * システム警告バナーレジストリ
 *
 * 複数機能が独立して警告バナーを登録できる Provider パターンのサービス。
 * register() で警告判定関数を登録し、getActiveBanners() で評価結果を取得する。
 *
 * バナー配列のシェイプ:
 *  - level: 'error' | 'warning' | 'info'
 *  - icon: Font Awesome クラス
 *  - title: 見出し文字列
 *  - message: 説明文
 *  - actions: array<int, array{label:string, url:string, style:string, method:string}>
 */
class SystemWarningService
{
    /** @var array<int, callable():(array<int, array<string, mixed>>|null)> */
    protected array $providers = [];

    /**
     * 警告判定プロバイダを登録する
     *
     * @param  callable():(array<int, array<string, mixed>>|null)  $provider
     */
    public function register(callable $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * 現在アクティブな警告バナー一覧を取得する
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveBanners(): array
    {
        $banners = [];
        foreach ($this->providers as $provider) {
            $result = $provider();
            if (is_array($result) && $result !== []) {
                $banners = array_merge($banners, $result);
            }
        }

        return $banners;
    }
}
