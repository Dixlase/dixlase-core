<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * シーダーを作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeSeederTrait
{
    use MakeFileTrait;

    /**
     * シーダーを作成するメイン処理
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) シーダー用 stubファイル (seeder.stub)
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) シーダー固有の追加プレースホルダ (なければ空配列)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * seeder.stub を返す (将来拡張したい場合はここに分岐可)
     */
    protected function resolveStubFile(): string
    {
        return 'seeder.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getSeederDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getSeederDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getSeederNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getSeederDirectory(array $subDirs): string;
    abstract protected function getSeederNamespace(array $subDirs): string;
}
