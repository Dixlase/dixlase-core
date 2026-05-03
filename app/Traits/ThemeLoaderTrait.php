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

namespace App\Traits;

use App\Contracts\Repositories\ThemeRepositoryInterface;
use Illuminate\Support\Str;

/**
 * テーマリソースローディング機構
 */
trait ThemeLoaderTrait
{
    /**
     * ThemeRepositoryInterface の遅延解決
     */
    protected function resolveThemeRepository(): ThemeRepositoryInterface
    {
        return app(ThemeRepositoryInterface::class);
    }

    /**
     * テーマの言語ファイルを読み込む
     *
     * @param  string  $themePath  テーマのベースパス
     * @param  string  $customThemePath  カスタムテーマのベースパス
     * @param  string  $namespace  言語ファイルの名前空間
     */
    protected function loadThemeTranslations(string $themePath, string $customThemePath, string $namespace): void
    {
        // カスタムパスを優先
        $paths = array_filter([$customThemePath.'/lang', $themePath.'/lang']);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->loadTranslationsFrom($path, $namespace);
            }
        }
    }

    /**
     * テーマのビューを読み込む
     *
     * @param  string  $themePath  テーマのベースパス
     * @param  string  $customThemePath  カスタムテーマのベースパス
     * @param  string  $namespace  ビューの名前空間
     */
    protected function loadThemeViews(string $themePath, string $customThemePath, string $namespace): void
    {
        $paths = array_filter([$customThemePath.'/resources/views', $themePath.'/resources/views']);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                \Illuminate\Support\Facades\View::addNamespace($namespace, $path);
            }
        }
    }

    /**
     * テーマの設定ファイルを読み込む
     *
     * @param  string  $themePath  テーマのベースパス
     * @param  string  $customThemePath  カスタムテーマのベースパス
     * @param  string  $themeSlug  テーマのスラッグ
     */
    protected function loadThemeConfig(string $themePath, string $customThemePath, string $themeSlug): void
    {
        // カスタムパスを優先
        $customConfigPath = $customThemePath.'/config';
        $coreConfigPath = $themePath.'/config';

        $configPaths = [];
        if (is_dir($customConfigPath)) {
            $configPaths[] = $customConfigPath;
        }
        if (is_dir($coreConfigPath)) {
            $configPaths[] = $coreConfigPath;
        }

        foreach ($configPaths as $configPath) {
            foreach (glob($configPath.'/*.php') as $configFile) {
                $configName = basename($configFile, '.php');
                $key = "theme.{$themeSlug}.{$configName}";

                // 既に設定されていなければマージ
                if (! config()->has($key)) {
                    config([$key => require $configFile]);
                }
            }
        }
    }

    /**
     * テーマの名前空間を生成
     *
     * @param  string  $themeDirectory  テーマのディレクトリ名
     */
    protected function getThemeNamespace(string $themeDirectory): string
    {
        return Str::kebab($themeDirectory);
    }

    /**
     * 有効なテーマIDを取得
     */
    public function getEnabledTheme(): int
    {
        return $this->resolveThemeRepository()->getEnabledThemeId();
    }

    /**
     * 現在有効なテーマのディレクトリ名を取得
     */
    public function getEnabledThemeDirectory(): string
    {
        return $this->resolveThemeRepository()->getEnabledThemeDirectory();
    }

    /**
     * テーマアセットの完全URLを生成
     */
    public function themeAsset(string $path): string
    {
        $themeDirectory = $this->getEnabledThemeDirectory();

        return asset("themes/{$themeDirectory}/{$path}");
    }
}
