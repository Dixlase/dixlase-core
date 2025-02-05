<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * ブロードキャスト用のチャンネルクラスを作成するための Trait.
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeChannelTrait
{
    use MakeFileTrait;

    /**
     * チャンネルクラスを作成するメイン処理
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) channel.stub
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダ (不要なら空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * デフォルト "channel.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'channel.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getChannelDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getChannelDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getChannelNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getChannelDirectory(array $subDirs): string;
    abstract protected function getChannelNamespace(array $subDirs): string;
}
