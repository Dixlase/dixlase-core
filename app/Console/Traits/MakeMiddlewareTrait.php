<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * ミドルウェアを作るための追加ロジック。
 * -> MakeFileTrait を use して継承的に発展させる例
 */
trait MakeMiddlewareTrait
{
    use MakeFileTrait;

    /**
     * ミドルウェアを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) ミドルウェア用 stub ファイル (今は常に "middleware.stub" でOK)
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) ミドルウェア固有の追加プレースホルダ (特になければ空配列でOK)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * stubファイル名を決定
     * 例: "middleware.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'middleware.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace でサブクラスの getMiddlewareDirectory/Namespace を呼ぶ
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getMiddlewareDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getMiddlewareNamespace($subDirs);
    }

    /**
     * サブクラスで実装: getMiddlewareDirectory/Namespace
     */
    abstract protected function getMiddlewareDirectory(array $subDirs): string;
    abstract protected function getMiddlewareNamespace(array $subDirs): string;
}
