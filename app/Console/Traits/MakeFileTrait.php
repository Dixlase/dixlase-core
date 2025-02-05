<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * どんな「ファイル作成」コマンドにも共通する基礎ロジックをまとめる Trait
 */
trait MakeFileTrait
{
    /**
     * 実際にファイルを作成するメイン処理。
     *
     * @param  string  $className   作成するクラス名 (e.g. "MyClass")
     * @param  array   $subDirs     サブディレクトリ ["Admin", "Nested"]
     * @param  array   $options     オプション { force: bool, ... }
     * @param  string  $stubFile    選択されたスタブファイル名
     * @return void
     */
    protected function makeFiler(
        string $className,
        array $subDirs,
        array $options,
        string $stubFile,
        array $extraPlaceholders = []
    ): void {
        // 1) 出力先ディレクトリ / 名前空間 (サブクラスで実装)
        $namespace       = $this->getNamespace($subDirs);
        $targetDirectory = $this->getDirectory($subDirs);
        $filePath        = "{$targetDirectory}/{$className}.php";

        // --force
        $force = $options['force'] ?? false;
        if (! $force) {
            $this->fileGenerator->prepareFilePath($filePath, "[{$className}] already exists.");
        } else {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // 2) スタブファイル読み込み
        $stubContent = $this->loadStubFile($stubFile);

        // base placeholders
        $basePlaceholders = [
            '{{ rootNamespace }}' => $this->getRootNamespace(),
            '{{ namespace }}'     => $namespace,
            '{{ class }}'         => $className,
        ];

        $allPlaceholders = array_merge($basePlaceholders, $extraPlaceholders);

        $finalContent = $this->fileGenerator->embedLicense($stubContent, $allPlaceholders);

        $this->fileGenerator->generateFile(
            $filePath,
            $finalContent
        );
        $this->info("File [{$className}] created at [{$filePath}].");
    }

    /**
     * スタブファイルを読み込み (stubs/custom 優先 → デフォルト)
     *
     * @param  string  $stubFile
     * @return string
     */
    protected function loadStubFile(string $stubFile): string
    {
        $customStubPaths = [base_path('stubs/custom')];

        // 例: "vendor/laravel/framework/src/Illuminate/Routing/Console/stubs/{$stubFile}" 等
        // ここはファイルの種類によって変わるのでサブクラスや呼び出し側で固定してもOK
        $defaultStubPath = base_path("vendor/laravel/framework/src/Illuminate/Routing/Console/stubs/{$stubFile}");

        return $this->fileGenerator->getStubContent($stubFile, $defaultStubPath, $customStubPaths);
    }

    protected function getRootNamespace(): string
    {
        return app()->getNamespace();
    }


    /**
     * 出力先ディレクトリ (抽象)
     */
    abstract protected function getDirectory(array $subDirs): string;

    /**
     * 名前空間 (抽象)
     */
    abstract protected function getNamespace(array $subDirs): string;
}
