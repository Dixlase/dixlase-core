<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * サービスクラスを作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeServiceTrait
{
    use MakeFileTrait;

    /**
     * サービスクラスを作成するメイン処理
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) service.stub など
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) サービス固有の追加プレースホルダ (なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * デフォルトは "service.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'service.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getServiceDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getServiceDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getServiceNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getServiceDirectory(array $subDirs): string;
    abstract protected function getServiceNamespace(array $subDirs): string;
}
