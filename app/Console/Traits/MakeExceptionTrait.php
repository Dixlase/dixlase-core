<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * カスタム Exception を作成するためのTrait.
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeExceptionTrait
{
    use MakeFileTrait;

    /**
     * 例外クラスを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) exception.stub
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダがあれば定義 (ここでは空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * デフォルト "exception.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'exception.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getExceptionDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getExceptionDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getExceptionNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getExceptionDirectory(array $subDirs): string;
    abstract protected function getExceptionNamespace(array $subDirs): string;
}
