<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * Artisanコマンドクラスを作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeCommandTrait
{
    use MakeFileTrait;

    /**
     * Artisanコマンドクラスを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) command.stub など
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) Artisanコマンド特有のプレースホルダ(なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * デフォルト "command.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'command.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getCommandDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getCommandDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getCommandNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getCommandDirectory(array $subDirs): string;
    abstract protected function getCommandNamespace(array $subDirs): string;
}
