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
        $this->licenseStubPath = config('console.license_txt');
        $this->licenseConfigPath = config('console.license_json');
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
    public function getStubContent(string $stubFileName, ?string $defaultPath = null, array $customPaths = [], array $licenseInfo = []): string
    {
        // カスタムパスを優先してスタブを検索
        foreach ($customPaths as $customPath) {
            $fullPath = "{$customPath}/{$stubFileName}";
            if ($this->files->exists($fullPath)) {
                $stub = $this->files->get($fullPath);
                return $this->embedLicensePhp($stub, [], $licenseInfo); // ← ここでライセンスを埋め込む
            }
        }

        $fallbackPath = base_path("stubs/custom/{$stubFileName}");
        if ($this->files->exists($fallbackPath)) {
            $stub = $this->files->get($fallbackPath);
            return $this->embedLicensePhp($stub, [], $licenseInfo); // ← ここでライセンスを埋め込む
        }

        // どのパスからもスタブが見つからない場合は例外をスロー
        throw new \RuntimeException("Stub file not found: {$stubFileName}");
    }

    /**
     * プレースホルダを置換
     */
    public function replacePlaceholders(string $stub, array $placeholders): string
    {
        foreach ($placeholders as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }
        return $stub;
    }


    /**
     * ライセンス情報を取得
     */
    public function getLicenseInfo(): array
    {
        if ($this->files->exists($this->licenseConfigPath)) {
            $content = $this->files->get($this->licenseConfigPath);
            return json_decode($content, true) ?: [];
        }

        return [];
    }

    /**
     * ライセンス名を取得
     */
    public function getLicenseName(): string
    {
        $licenseInfo = $this->getLicenseInfo();
        return $licenseInfo['license'] ?? 'AGPL-3.0';
    }

    /**
     * ライセンスのプレーンテキスト（placeholders差し替え済み）を取得する
     */
    public function getLicensePlain(array $licenseInfo): string
    {
        // licenseInfo から読み込み


        $licensePath = base_path($licenseInfo['template']);

        if (!$this->files->exists($licensePath)) {
            return '';
        }

        // {software}, {year}, {author}, {website} を差し替え
        $licenseText = $this->files->get($licensePath);
        $licensePlaceholders = [
            '{software}' => $licenseInfo['software'] ?? 'MySoftware',
            '{year}'     => date('Y'),
            '{author}'   => $licenseInfo['author'] ?? 'MyName',
            '{website}'  => $licenseInfo['website'] ?? 'https://example.com',
        ];
        return $this->replacePlaceholders($licenseText, $licensePlaceholders);
    }

    /**
     * PHPファイル用のライセンスをdoc-block 形式にする
     */
    public function getLicenseForPhp(array $licenseInfo): string
    {


        $plain = $this->getLicensePlain($licenseInfo);
        if (empty($plain)) {
            return '';
        }

        $lines = explode("\n", trim($plain));
        $formatted = "/**\n";
        foreach ($lines as $line) {
            $formatted .= trim($line) === '' ? " *\n" : " * " . rtrim($line) . "\n";
        }
        $formatted .= " */";

        return $formatted;
    }

    /**
     * Blade 用にライセンスを "{{-- ... --}}" 形式のコメントにする
     */
    public function getLicenseForBlade(array $licenseInfo): string
    {
        $plain = $this->getLicensePlain($licenseInfo);
        if (empty($plain)) {
            return '';
        }

        // Blade用コメントでラップ
        return "{{--\n" . trim($plain) . "\n--}}";
    }


    /**
     * PHPファイルにライセンスを埋め込む
     * -> {{ license }} を getLicenseForPhp() で置換
     */
    public function embedLicensePhp(string $stub, array $placeholders, array $licenseInfo): string
    {
        $licensePhp = $this->getLicenseForPhp($licenseInfo); // doc-block 形式
        // stub 内の "{{ license }}" を置換
        return $this->replacePlaceholders($stub, array_merge($placeholders, [
            '{{ license }}' => $licensePhp
        ]));
    }

    /**
     * Bladeファイルにライセンスを埋め込む
     * -> {{ license }} を getLicenseForBlade() で置換
     */
    public function embedLicenseBlade(string $stub, array $placeholders): string
    {
        $licenseBlade = $this->getLicenseForBlade(); // blade形式
        return $this->replacePlaceholders($stub, array_merge($placeholders, [
            '{{ license }}' => $licenseBlade
        ]));
    }

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
