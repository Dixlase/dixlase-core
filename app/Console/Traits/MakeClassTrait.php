<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * 汎用クラスを作成するためのTrait.
 * -> MakesFileTrait を use してファイル生成を共通化。
 */
trait MakeClassTrait
{
    use MakesFileTrait;

    /**
     * 汎用クラス (invokable可) を作成するメイン処理
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @param  bool    $isInvokable  --invokable がtrueなら
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        bool $isInvokable
    ): void {
        // 1) stubファイルを決定
        //    --invokable なら "class.invokable.stub"、
        //    そうでなければ "class.stub"
        $stubFile = $isInvokable ? 'class.invokable.stub' : 'class.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダ (なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getClassDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getClassDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getClassNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getClassDirectory(array $subDirs): string;
    abstract protected function getClassNamespace(array $subDirs): string;
}
