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

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;



/**
 * 言語ファイルを作成する共通ロジック
 */
trait MakeLanguageTrait
{

    use MakeFileTrait;
    use MakeLicenseTrait;

    /**
     * 言語ファイル作成の共通オプション定義
     *
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [
            '{lang? : The language code (e.g. en, ja)}',
            '{--placeholders= : JSON encoded placeholders for stub replacement}',
            '{--license-info= : JSON encoded license information}',
        ];
    }


    protected function chooseLanguage(): string
    {
        $languages = config('language.languages');
        $defaultLanguage = config('language.default');
        $languageCodes = array_keys($languages);
        $languageNames = array_values($languages);
        
        // Create 1-based indexed choices
        $choices = [];
        foreach ($languageNames as $index => $name) {
            $choices[$index + 1] = $name;
        }
        
        // Get default choice index (1-based)
        $defaultChoice = array_search($defaultLanguage, $languageCodes) + 1;
        
        // Show choices with 1-based indexing
        $choice = $this->choice(
            'Select a language:',
            $choices,
            $defaultChoice
        );
        
        // Map the selected name back to language code
        $selectedIndex = array_search($choice, $choices);
        return $languageCodes[$selectedIndex - 1];
    }

    protected function makeFile($className, $fileType, $options, $subDirs, $pluginName = null)
    {
        //言語コードの取得
        $lang = null;
        if(isset($options['lang']) && $options['lang']){
            $lang = $options['lang'];
        }

        // 言語コードの選択
        if (!$lang) {
            $lang = $this->chooseLanguage();
            if (!$lang) {
                return false;
            }
        }

        // 言語コードに基づいてサブディレクトリを追加
        $translations = config('language.translations', []);
        $langDir = $translations[strtolower($lang)] ?? strtolower($lang);

        // 言語ディレクトリをサブディレクトリの先頭に追加
        array_unshift($subDirs, $langDir);

        // スタブの取得
        $stub = $this->renderStub($lang);
        
        // プレースホルダーの取得（JSON形式で渡された場合はデコード）
        $additionalPlaceholders = [];
        if (isset($options['placeholders']) && !empty($options['placeholders'])) {
            $decoded = json_decode($options['placeholders'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $additionalPlaceholders = $decoded;
            }
        }
        
        // ライセンスキーからライセンス情報を取得
        $finalLicenseInfo = $this->getFileTypeLicenseInfo($fileType, $pluginName);
        if (isset($options['license-info']) && !empty($options['license-info'])) {
            $licenseKey = $options['license-info'];
            // プラグイン情報を取得
            $pluginInfo = [];
            if (!empty($pluginName)) {
                $licenseInfoFile = base_path("plugins/{$pluginName}/license-info.json");
                if (File::exists($licenseInfoFile)) {
                    $pluginInfo = json_decode(File::get($licenseInfoFile), true) ?? [];
                }
            }
            $finalLicenseInfo = $this->getLicenseInfoFromKey($licenseKey, $pluginInfo);
        }
        
        // ファイル生成
        return $this->makeFiler(
            $className,
            $fileType,
            'lang',
            $options,
            $subDirs,
            $stub,
            $pluginName,
            $additionalPlaceholders,
            $finalLicenseInfo
        );

    }

    /**
     * 言語用のスタブを選択して取得
     *
     * @param  string   $lang
     * @return string
     */
    protected function renderStub(string $lang): string
    {
        // 言語用のスタブを選択
        $stubName = 'language.' . $lang  . '.stub';
        // スタブファイルのパスを取得
        $stubPath = config('command.custom_stub_directory') . '/' . $stubName;

        // スタブファイルの内容を取得
        return File::get($stubPath);
    }
}
