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

namespace App\Console\Traits;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * テーマ用ファイル作成コマンドの共通機能を提供するトレイト
 */
trait MakeThemeCommandTrait
{
    use ThemeManagementTrait;

    /**
     * テーマコマンドの共通引数を取得します
     *
     * @param bool $includeScope スコープ引数を含めるかどうか
     * @return string 共通引数のシグネチャ文字列
     */
    protected function getThemeCommandSignature(bool $includeScope = false): string
    {
        $signature = "{className? : The class name (e.g. User)} {themeName? : The theme name (e.g. MyTheme)}";
        
        if ($includeScope) {
            $signature .= " {scope?}";
        }
        
        return $signature;
    }

    /**
     * テーマファイルの生成処理を実行します
     *
     * @param string|null $classPath クラスパス（オプション）
     * @param string|null $themeName テーマ名（オプション）
     * @param string $category ファイルカテゴリ
     * @param array $options 追加オプション
     * @param bool $needsScope スコープの選択が必要かどうか
     * @param string|null $scope 事前に指定されたスコープ（オプション）
     * @return bool 成功したかどうか
     */
    protected function generateThemeFile(
        ?string $classPath = null,
        ?string $themeName = null,
        string $category = null,
        array $options = [],
        bool $needsScope = false,
        ?string $scope = null,
    ): bool {

        $fileType = 'theme';

        // クラス名が指定されていない場合は入力を求める
        if (empty($classPath)) {
            $classPath = $this->askForClassName();
            if ($classPath === false) {
                return false;
            }
        }

        // テーマ名がコマンドで指定されていれば、それを使用する
        if (!empty($this->argument('themeName'))) {
            $themeName = $this->argument('themeName');
            $availableThemes = $this->getAvailableThemeNames();
            // テーマディレクトリに指定されたテーマ名がなければエラーを返す
            if (!in_array($themeName, $availableThemes)) {
                $this->error(__('command.theme.not_found', ['name' => $themeName]));
                return false;
            }
        } else {
            // テーマ名の指定がなければテーマ選択
            $themeName = $this->chooseTheme();
            if (empty($themeName)) {
                $this->error(__('command.theme.not_found'));
                return false;
            }
        }

        // スコープが必要な場合は選択
        if ($needsScope && empty($scope)) {
            $scope = $this->chooseScope();
            if (empty($scope)) {
                $this->error(__('command.scope.not_selected'));
                return false;
            }
        }

        // ファイル生成処理を実行
        return $this->generateFile(
            $classPath,
            $themeName,
            $fileType,
            $category,
            $options,
            $scope
        );
    }

    /**
     * 利用可能なテーマ名のリストを取得します
     *
     * @return array テーマ名の配列
     */
    protected function getAvailableThemeNames(): array
    {
        $themesPath = base_path('themes');
        
        if (!is_dir($themesPath)) {
            return [];
        }
        
        $themes = [];
        $directories = scandir($themesPath);
        
        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $themePath = $themesPath . '/' . $dir;
            if (is_dir($themePath) && file_exists($themePath . '/theme.json')) {
                $themes[] = $dir;
            }
        }
        
        return $themes;
    }

    /**
     * テーマを選択します
     *
     * @return string|null 選択されたテーマ名、またはnull
     */
    protected function chooseTheme(): ?string
    {
        $themes = $this->getAvailableThemeNames();
        
        if (empty($themes)) {
            $this->error(__('command.theme.no_themes_found'));
            return null;
        }
        
        $themeName = $this->choice(
            __('command.theme.select_theme'),
            $themes,
            0
        );
        
        return $themeName;
    }
}
