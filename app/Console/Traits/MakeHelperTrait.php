<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * ヘルパーファイルを作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeHelperTrait
{
    use MakeFileTrait;

    /**
     * ヘルパーファイルを作成するメイン処理
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) helper用 stubファイル (helper.stub)
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) ヘルパー特有のプレースホルダ(なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * helper.stub を返す
     */
    protected function resolveStubFile(): string
    {
        return 'helper.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getHelperDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getHelperDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getHelperNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getHelperDirectory(array $subDirs): string;
    abstract protected function getHelperNamespace(array $subDirs): string;
}
