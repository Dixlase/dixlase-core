<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * カスタム ValidationRule を作成するための Trait.
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeRuleTrait
{
    use MakeFileTrait;

    /**
     * ルールクラスを作成するメイン処理
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) rule.stub
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダが必要なら定義 (ここでは空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * デフォルト "rule.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'rule.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getRuleDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getRuleDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getRuleNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getRuleDirectory(array $subDirs): string;
    abstract protected function getRuleNamespace(array $subDirs): string;
}
