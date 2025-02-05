<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

trait MakeEnumTrait
{
    use MakeFileTrait;

    /**
     * Enumクラスを作成するメイン処理
     *
     * @param  string       $className
     * @param  array        $subDirs
     * @param  bool         $force
     * @param  string|null  $backedType  --backed=xxx の値 (例: "string" / "int" etc.)
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        ?string $backedType = null
    ): void {
        // 1) stubファイルを決定
        //    --backed=があれば "enum.backed.stub", なければ "enum.stub"
        $isBacked = ! is_null($backedType);
        $stubFile = $isBacked ? 'enum.backed.stub' : 'enum.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) バッキングありなら追加プレースホルダ {{ type }} => $backedType
        $extraPlaceholders = [];
        if ($isBacked) {
            $extraPlaceholders['{{ type }}'] = $backedType;
        }

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getEnumDirectory/Namespace
     *   => サブクラスで実装
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getEnumDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getEnumNamespace($subDirs);
    }

    /**
     * サブクラスに実装してもらう
     */
    abstract protected function getEnumDirectory(array $subDirs): string;
    abstract protected function getEnumNamespace(array $subDirs): string;
}
