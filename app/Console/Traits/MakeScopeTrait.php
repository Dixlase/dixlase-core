<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * Eloquent Scope (Global Scope) を作成するための Trait.
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeScopeTrait
{
    use MakeFileTrait;

    /**
     * スコープクラスを作成するメイン処理
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) scope.stub
        $stubFile = 'scope.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダ（なければ空）
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getScopeDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getScopeDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getScopeNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getScopeDirectory(array $subDirs): string;
    abstract protected function getScopeNamespace(array $subDirs): string;
}
