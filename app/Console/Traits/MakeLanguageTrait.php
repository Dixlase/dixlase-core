<?php

/**
 * This file is part of MySoftware.
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

        ];
    }

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Initialize language command with optional prompts for missing parameters
     *
     * @param string|null $classPath The class path (can be null for prompting)
     * @param string|null $pluginName The plugin name (optional)
     * @param string|null $lang The language code (optional)
     * @return array|false Returns command data array or false on failure
     */
    public function initializeLanguageCommand(?string $classPath = null, ?string $pluginName = null, ?string $lang = null)
    {
        // プラグインルートの場合はファイルタイプ選択をスキップ
        $commandName = $this->getName();

        if ($commandName === 'make:plugin:lang') {
            $fileType = 'plugin';
        } elseif ($commandName === 'make:custom:lang') {
            $fileType = 'custom';
        } else {
            $this->error('Unsupported command for language file creation');
            return false;
        }

        // Prompt for class name if not provided
        if (empty($classPath)) {
            $classPath = $this->ask(__('class.enter_class_name'));
            if (empty($classPath)) {
                $this->error(__('class.class_name_required'));
                return false;
            }
        }

        // プラグイン名
        if ($fileType === 'plugin') {
            if (!$pluginName) {
                $pluginName = $this->choosePlugin();
                if (!$pluginName) {
                    return false;
                }
            }
        }

        // 言語コードの選択
        if (!$lang) {
            $lang = $this->chooseLanguage();
            if (!$lang) {
                return false;
            }
        }

        // パス情報の分解
        try {
            [$className, $subDirs] = $this->parseClassPath($classPath);
        } catch (\Exception $e) {
            $this->error('Invalid class path: ' . $e->getMessage());
            return false;
        }
        //サブディレクトリに言語のパスを追加
        array_unshift($subDirs, $lang);
        $pluginName = Str::studly($pluginName);

    
        // ライセンス情報の取得
        $licenseInfo = $this->getPluginLicenseInfo($pluginName) ?? [];


        return [
            'fileType' => $fileType,
            'className' => $className,
            'pluginName' => $pluginName,
            'subDirs' => $subDirs,
            'licenseInfo' => $licenseInfo,
            'lang' => $lang,
            'category' => 'lang'
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

        // スタブの取得
        $stub = $this->renderStub($options['lang']);
        
        // ファイル生成
        return $this->makeFiler(
            $className,
            $fileType,
            'lang',
            $options,
            $subDirs,
            $stub,
            $pluginName,
            [],
            $this->getFileTypeLicenseInfo($fileType, $pluginName)
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



    /**
     * 言語ファイルを作成する
     *
     * @param string $langCode 言語コード (例: "en", "ja")
     * @param string $fileName ファイル名 (例: "messages")
     * @param array $options オプション
     */
    protected function makeLanguageFile(string $langCode, string $fileName, array $options): void
    {
        // 言語ファイルの拡張子を `.php` に統一
        if (!str_ends_with($fileName, '.php')) {
            $fileName .= '.php';
        }

        // 言語ファイルの保存パスを取得
        $filePath = $this->getDirectory([$langCode]) . '/' . $fileName;

        try {
            $this->fileGenerator->prepareFilePath($filePath, "Language file [{$fileName}] already exists. Use --force to overwrite.");
        } catch (\RuntimeException $e) {
            if (!($options['force'] ?? false)) {
                $this->error($e->getMessage());
                return;
            }
            File::delete($filePath);
        }

        // スタブファイルを取得
        $stubFile = $this->resolveStubFile($langCode);
        $stubContent = $this->fileGenerator->getStubContent($stubFile);

        // プレースホルダー置換
        $placeholders = [
            '{{ license }}' => $this->fileGenerator->getLicenseContent(),
            '{{ langCode }}' => $langCode,
            '{{ fileName }}' => Str::studly($fileName),
        ];

        $finalContent = $this->fileGenerator->replacePlaceholders($stubContent, $placeholders);
        $this->fileGenerator->generateFile($filePath, $finalContent);

        $this->info("Language file created: {$filePath}");
    }

    /**
     * 言語ファイルのスタブを選択
     */
    protected function resolveStubFile(string $langCode): string
    {
        return "messages.{$langCode}.stub";
    }

    /**
     * 言語ファイルの保存先ディレクトリを取得（抽象）
     */
    abstract protected function getDirectory(array $subDirs): string;

    /**
     * 言語ファイルにはネームスペースは不要
     */
    protected function getNamespace(array $subDirs): string
    {
        return '';
    }
}
