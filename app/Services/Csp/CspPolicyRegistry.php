<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

namespace App\Services\Csp;

use App\Contracts\CspPolicyProvider;

/**
 * @api プラグイン/テーマから直接DIで使用可能な安定APIです
 *
 * CSP Policy Registry
 *
 * プラグイン・テーマからのCSPポリシーを収集・管理するレジストリ。
 */
class CspPolicyRegistry
{
    /**
     * 登録されたポリシープロバイダー
     *
     * @var array<string, CspPolicyProvider>
     */
    protected array $providers = [];

    /**
     * 直接登録されたディレクティブ
     *
     * @var array<string, array<string>>
     */
    protected array $directives = [];

    /**
     * ポリシープロバイダーを登録
     *
     * @param  string  $name  プロバイダー名（プラグイン/テーマ名）
     * @param  CspPolicyProvider  $provider  プロバイダーインスタンス
     */
    public function registerProvider(string $name, CspPolicyProvider $provider): void
    {
        $this->providers[$name] = $provider;
    }

    /**
     * ポリシープロバイダーを登録解除
     */
    public function unregisterProvider(string $name): void
    {
        unset($this->providers[$name]);
    }

    /**
     * ディレクティブを直接追加
     *
     * @param  string  $directive  ディレクティブ名
     * @param  array<string>  $values  値の配列
     * @param  string|null  $source  ソース名（デバッグ用）
     */
    public function addDirective(string $directive, array $values, ?string $source = null): void
    {
        if (! isset($this->directives[$directive])) {
            $this->directives[$directive] = [];
        }

        foreach ($values as $value) {
            if (! in_array($value, $this->directives[$directive], true)) {
                $this->directives[$directive][] = $value;
            }
        }
    }

    /**
     * 複数のディレクティブを一括追加
     *
     * @param  array<string, array<string>>  $directives
     * @param  string|null  $source  ソース名（デバッグ用）
     */
    public function addDirectives(array $directives, ?string $source = null): void
    {
        foreach ($directives as $directive => $values) {
            $this->addDirective($directive, $values, $source);
        }
    }

    /**
     * 登録されたすべてのディレクティブを収集
     *
     * @return array<string, array<string>>
     */
    public function collectDirectives(): array
    {
        $collected = $this->directives;

        // プロバイダーからディレクティブを収集
        foreach ($this->providers as $name => $provider) {
            $providerDirectives = $provider->getCspDirectives();

            foreach ($providerDirectives as $directive => $values) {
                if (! isset($collected[$directive])) {
                    $collected[$directive] = [];
                }

                foreach ($values as $value) {
                    if (! in_array($value, $collected[$directive], true)) {
                        $collected[$directive][] = $value;
                    }
                }
            }
        }

        return $collected;
    }

    /**
     * 登録されたプロバイダー一覧を取得
     *
     * @return array<string>
     */
    public function getProviderNames(): array
    {
        return array_keys($this->providers);
    }

    /**
     * レジストリをクリア
     */
    public function clear(): void
    {
        $this->providers = [];
        $this->directives = [];
    }
}
