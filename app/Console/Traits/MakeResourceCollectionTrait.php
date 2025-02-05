<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * ResourceCollection 用APIリソースクラスを作成するためのTrait.
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeResourceCollectionTrait
{
    use MakeFileTrait;

    /**
     * コレクション用リソースクラスを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force
    ): void {
        // 1) stubファイル => resource-collection.stub
        $stubFile = 'resource-collection.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダ (特になければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getResourceCollectionDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getResourceCollectionDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getResourceCollectionNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getResourceCollectionDirectory(array $subDirs): string;
    abstract protected function getResourceCollectionNamespace(array $subDirs): string;
}
