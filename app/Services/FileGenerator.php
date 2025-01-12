<?php

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
        $this->licenseStubPath = base_path('license.txt');
        $this->licenseConfigPath = base_path('license-info.json');
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
    public function getStubContent(string $stubFileName, ?string $defaultPath = null, array $customPaths = []): string
    {
        // カスタムパスを優先してスタブを検索
        foreach ($customPaths as $customPath) {
            $fullPath = "{$customPath}/{$stubFileName}";
            if ($this->files->exists($fullPath)) {
                return $this->files->get($fullPath);
            }
        }

        // デフォルトパスが指定されている場合はそこから取得
        if ($defaultPath && $this->files->exists($defaultPath)) {
            return $this->files->get($defaultPath);
        }

        // デフォルトパスがない場合でも、プロジェクト内の stubs ディレクトリを探索
        $fallbackPath = base_path("stubs/{$stubFileName}");
        if ($this->files->exists($fallbackPath)) {
            return $this->files->get($fallbackPath);
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
     * ライセンス内容を取得してプレースホルダを置換
     */
    public function getLicenseContent(): string
    {
        // ライセンステンプレートを取得
        $licenseText = $this->files->exists($this->licenseStubPath)
            ? $this->files->get($this->licenseStubPath)
            : '';

        // ライセンス情報を取得
        $licenseInfo = $this->getLicenseInfo();

        // プレースホルダを置換
        $licensePlaceholders = [
            '{software}' => $licenseInfo['software'] ?? 'UnknownSoftware',
            '{year}'     => date('Y'),
            '{company}'  => $licenseInfo['company'] ?? 'UnknownCompany',
            '{website}'  => $licenseInfo['website'] ?? 'https://example.com',
        ];

        return $this->replacePlaceholders($licenseText, $licensePlaceholders);
    }


    /**
     * スタブにライセンス情報を埋め込む
     */
    public function embedLicense(string $stub, array $placeholders): string
    {
        $licenseText = $this->files->exists($this->licenseStubPath)
            ? $this->files->get($this->licenseStubPath)
            : '';

        $licenseInfo = $this->getLicenseInfo();

        $licensePlaceholders = [
            '{software}' => $licenseInfo['software'] ?? 'UnknownSoftware',
            '{year}'     => date('Y'),
            '{company}'  => $licenseInfo['company'] ?? 'UnknownCompany',
            '{website}'  => $licenseInfo['website'] ?? 'https://example.com',
        ];

        $licenseText = $this->replacePlaceholders($licenseText, $licensePlaceholders);

        return $this->replacePlaceholders($stub, array_merge($placeholders, ['{{ license }}' => $licenseText]));
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
}
