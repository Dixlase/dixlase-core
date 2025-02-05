<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * APIリソースを作るためのTrait。
 * -> MakeFileTrait を use し、APIリソース（JsonResource等）生成を共通化
 */
trait MakeApiResourceTrait
{
    use MakeFileTrait;

    /**
     * APIリソースクラスを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) リソース用 stubファイル
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) 追加プレースホルダがあればここに
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * デフォルト "api-resource.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'api-resource.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getApiResourceDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getApiResourceDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getApiResourceNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getApiResourceDirectory(array $subDirs): string;
    abstract protected function getApiResourceNamespace(array $subDirs): string;
}
