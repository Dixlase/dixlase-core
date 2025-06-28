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

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class FileGenerator
{
    protected Filesystem $files;
    protected string $licenseStubPath;
    protected string $licenseConfigPath;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;

        // ライセンス関連の共通パスを定義
        $this->licenseStubPath = config('command.license_txt');
        $this->licenseConfigPath = config('command.license_json');
    }


    /**
     * ファイルパスの準備: ディレクトリ作成とファイル存在チェック
     *
     * @param string $filePath ファイルのフルパス
     * @param string $errorMessage エラー時のメッセージ
     * @throws \RuntimeException ファイルが既に存在する場合に例外をスロー
     */
    public function prepareFilePath(string $filePath, string $errorMessage): void
    {
        $directory = dirname($filePath);

        // ディレクトリを作成
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // ファイルが既に存在する場合はエラー
        if ($this->files->exists($filePath)) {
            throw new \RuntimeException($errorMessage);
        }
    }

    /**
     * スタブファイルを取得
     */
    public function getStubContent(string $stubTemplate, array $placeholders = []): string
    {

        //dump($placeholders['license']); // ← ここで内容を確認


        $content = $this->replacePlaceholders($stubTemplate, $placeholders);
        return $content;
    }


    /**
     * ライセンスのプレースホルダーを整形する
     */
    protected function formatLicenseTemplate(array $license): string
    {
        if (empty($license) || !isset($license['template'])) {
            return '';
        }

        /*
        $templatePath = base_path('resources/licenses/' . $licenseInfo['text']);
        if (!file_exists($templatePath)) {
            return '';
        }

        $text = file_get_contents($templatePath);
        */

        $replacements = [
            '{software}' => $licenseInfo['software'] ?? '',
            '{author}'   => $licenseInfo['author'] ?? '',
            '{website}'  => $licenseInfo['website'] ?? '',
            '{year}'     => date('Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $license['template']);
    }



    /**
     * プレースホルダを置換
     */
    /*
    public function replacePlaceholders(string $stub, array $placeholders): string
    {
        foreach ($placeholders as $search => $replace) {
            $stub = str_replace('{{ ' . $search . ' }}', $replace, $stub);
        }
        return $stub;
    }
        */






    /**
     * ファイルを生成
     */
    public function generateFile(string $filePath, string $content): void
    {
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($this->files->exists($filePath)) {
            throw new \RuntimeException("File already exists: {$filePath}");
        }

        $this->files->put($filePath, $content);
    }

    /**
     * ファイル名からネームスペースを生成
     */
    public function generateNamespace(string $fileName, string $baseNamespace): string
    {
        // プラグイン名をキャメルケース化
        $formattedFileName = Str::of($fileName)
            ->replace(['-', '_', ' '], ' ') // ハイフン、アンダースコア、スペースを空白に置換
            ->title()                      // 各単語の頭文字を大文字に
            ->replace(' ', '');            // 空白を削除

        return "{$baseNamespace}\\{$formattedFileName}";
    }

    /**
     * テーマ名、プラグイン名を正規化 (slug化)
     *
     * @param string $name
     * @return string
     */
    public function sanitizeName(string $name): string
    {
        // 半角スペースをハイフンに変換
        $name = str_replace(' ', '-', $name);
        // 英数・ハイフン・アンダースコア以外を除去
        $name = preg_replace('/[^A-Za-z0-9-_]/', '', $name);
        // 小文字化
        return strtolower($name);
    }

    /**
     * 入力されたクラス名を「サブディレクトリ」「クラス名」に分解する
     *
     * 例:
     *   "Admin/AdminPagesPluginController" => [["Admin"], "AdminPagesPluginController"]
     *   "Api/V2/MyController" => [["Api", "V2"], "MyController"]
     *   "MyController" => [[], "MyController"]
     */
    public function parseClassName(string $input): array
    {
        // バックスラッシュもフォワードスラッシュに揃える
        $path = str_replace('\\', '/', $input);

        // "/" で分割
        $parts = explode('/', $path);

        // 最後の要素がクラス名、それ以外はサブディレクトリとみなす
        $className = array_pop($parts);
        $subDirs   = $parts; // 例) ["Admin"] など

        return [$subDirs, $className];
    }
}
