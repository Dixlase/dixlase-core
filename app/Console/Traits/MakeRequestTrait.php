<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * FormRequest を作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeRequestTrait
{
    use MakeFileTrait;

    /**
     * リクエストクラスを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) リクエスト用 stubファイル (request.stub)
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) リクエスト固有のプレースホルダ (無いなら空でOK)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * request.stub 固定 (将来的に --api とかで切り替えたいならここで拡張可)
     */
    protected function resolveStubFile(): string
    {
        return 'request.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getRequestDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getRequestDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getRequestNamespace($subDirs);
    }

    /**
     * サブクラスが実装
     */
    abstract protected function getRequestDirectory(array $subDirs): string;
    abstract protected function getRequestNamespace(array $subDirs): string;
}
