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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

use App\Contracts\LegalPage\LegalPageServiceInterface;
use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * 法務ページレジストリサービス
 *
 * コアとプラグインの法務ページ種別を統合管理し、
 * dls_site_settings テーブルに URL を保存する。
 */
class LegalPageService implements LegalPageServiceInterface
{
    /** @var string 設定キーのプレフィックス */
    private const SETTING_PREFIX = 'legal_page_url:';

    /** @var array<string, array{name: string, description: string, required: bool, icon: string, required_by?: list<string>}>|null */
    private ?array $mergedPageTypes = null;

    public function __construct(
        private SiteSettingRepositoryInterface $settingRepository,
    ) {}

    /**
     * コア + プラグインの統合ページ種別一覧を取得
     *
     * @return array<string, array{name: string, description: string, required: bool, icon: string, required_by?: list<string>}>
     */
    public function getPageTypes(): array
    {
        if ($this->mergedPageTypes !== null) {
            return $this->mergedPageTypes;
        }

        $coreTypes = config('admin.legal-pages', []);

        // プラグインのオーバーライドを読み込み
        $pluginOverrides = $this->loadPluginOverrides();

        // マージ処理
        $merged = $coreTypes;

        foreach ($pluginOverrides as $slug => $override) {
            if (isset($merged[$slug])) {
                // 既存エントリのオーバーライド
                if (! empty($override['required'])) {
                    $merged[$slug]['required'] = true;
                    $merged[$slug]['required_by'] = array_merge(
                        $merged[$slug]['required_by'] ?? [],
                        $override['required_by'] ?? []
                    );
                }
                // name/description/icon はプラグイン側で上書き可能
                if (isset($override['name'])) {
                    $merged[$slug]['name'] = $override['name'];
                }
                if (isset($override['description'])) {
                    $merged[$slug]['description'] = $override['description'];
                }
                if (isset($override['icon'])) {
                    $merged[$slug]['icon'] = $override['icon'];
                }
            } else {
                // プラグイン独自の新規ページ種別
                $merged[$slug] = $override;
            }
        }

        $this->mergedPageTypes = $merged;

        return $this->mergedPageTypes;
    }

    /**
     * 指定ページ種別が必須かどうかを判定
     */
    public function isRequired(string $slug): bool
    {
        $types = $this->getPageTypes();

        return $types[$slug]['required'] ?? false;
    }

    /**
     * 指定ページ種別の URL が設定済みかどうかを判定
     */
    public function exists(string $slug): bool
    {
        $url = $this->settingRepository->get(self::SETTING_PREFIX.$slug);

        return $url !== null && $url !== '';
    }

    /**
     * 指定ページ種別の URL を取得
     */
    public function url(string $slug): ?string
    {
        $value = $this->settingRepository->get(self::SETTING_PREFIX.$slug);

        return ($value !== null && $value !== '') ? $value : null;
    }

    /**
     * 必須だが URL 未設定のページ種別一覧を取得
     *
     * @return array<string, array{name: string, description: string, required: bool, icon: string}>
     */
    public function missingRequired(): array
    {
        $missing = [];

        foreach ($this->getPageTypes() as $slug => $type) {
            if (! empty($type['required']) && ! $this->exists($slug)) {
                $missing[$slug] = $type;
            }
        }

        return $missing;
    }

    /**
     * 指定ページ種別の URL を設定（null で削除）
     */
    public function setUrl(string $slug, ?string $url): void
    {
        $key = self::SETTING_PREFIX.$slug;

        if ($url === null || $url === '') {
            $this->settingRepository->delete($key);
        } else {
            $this->settingRepository->set($key, $url);
        }
    }

    /**
     * 有効プラグインの法務ページ設定を読み込み
     *
     * @return array<string, array<string, mixed>>
     */
    private function loadPluginOverrides(): array
    {
        $overrides = [];

        try {
            $plugins = DB::table('plugins')
                ->whereNotNull('enabled_at')
                ->get();
        } catch (\Exception $e) {
            Log::warning('LegalPageService: プラグインテーブルの読み込みに失敗: '.$e->getMessage());

            return [];
        }

        foreach ($plugins as $plugin) {
            $configPath = base_path("plugins/{$plugin->directory}/config/admin/legal-pages.php");

            if (! File::exists($configPath)) {
                continue;
            }

            try {
                $pluginConfig = require $configPath;

                if (! is_array($pluginConfig)) {
                    Log::warning("LegalPageService: 不正な legal-pages.php 形式: {$plugin->slug}");

                    continue;
                }

                foreach ($pluginConfig as $slug => $pageConfig) {
                    if (! is_array($pageConfig)) {
                        continue;
                    }

                    // required_by にプラグイン名を追加
                    if (! empty($pageConfig['required'])) {
                        $pageConfig['required_by'] = [$plugin->name];
                    }

                    if (isset($overrides[$slug])) {
                        // 複数プラグインから同じ slug が要求された場合
                        if (! empty($pageConfig['required'])) {
                            $overrides[$slug]['required'] = true;
                            $overrides[$slug]['required_by'] = array_merge(
                                $overrides[$slug]['required_by'] ?? [],
                                $pageConfig['required_by']
                            );
                        }
                    } else {
                        $overrides[$slug] = $pageConfig;
                    }
                }
            } catch (\Exception $e) {
                Log::error("LegalPageService: プラグイン {$plugin->slug} の設定読み込み失敗: {$e->getMessage()}");
            }
        }

        return $overrides;
    }
}
