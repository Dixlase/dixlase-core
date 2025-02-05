<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * Eloquent Cast (CastsAttributes) 作成のための Trait.
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeCastTrait
{
    use MakeFileTrait;

    /**
     * Castクラスを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force
    ): void {
        // 1) cast.stub
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
     * デフォルト "cast.stub"
     */
    protected function resolveStubFile(): string
    {
        return 'cast.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getCastDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getCastDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getCastNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getCastDirectory(array $subDirs): string;
    abstract protected function getCastNamespace(array $subDirs): string;
}
