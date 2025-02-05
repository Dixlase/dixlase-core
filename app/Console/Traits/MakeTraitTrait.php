<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * 単に"Trait"を作成するための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化する。
 */
trait MakeTraitTrait
{
    use MakeFileTrait;

    /**
     * トレイトを作成するメイン処理
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) trait用 stubファイル (例: trait.stub)
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) トレイト固有のプレースホルダ(なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * trait.stub を返す (将来別途--finalなど拡張したいならここで分岐)
     */
    protected function resolveStubFile(): string
    {
        return 'trait.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getTraitDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getTraitDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getTraitNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getTraitDirectory(array $subDirs): string;
    abstract protected function getTraitNamespace(array $subDirs): string;
}
