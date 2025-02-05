<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * ポリシーを作るための追加ロジック。
 * -> MakeFileTrait を use し、model指定あり/なしを含む処理をまとめる例
 */
trait MakePolicyTrait
{
    use MakeFileTrait;

    /**
     * ポリシーを作成するメイン処理
     *
     * @param  string       $className   ポリシークラス名 (e.g. "UserPolicy")
     * @param  array        $subDirs
     * @param  bool         $force
     * @param  string|null  $modelOption --model= で指定されたモデルFQCN or 相対パス
     * @return void
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        ?string $modelOption
    ): void {
        // 1) どの stub を使うか (modelあり → policy.stub, なし → policy.plain.stub)
        $stubFile = $modelOption ? 'policy.stub' : 'policy.plain.stub';

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) ポリシー固有の追加プレースホルダ(あとでまとめる)
        //    modelFQCN, userFQCNなどは後で replace する場合はここでも良いが
        //    ここでは一旦空にしておき、makeFiler呼出し直前にマージする設計もできる

        // => ここではとりあえず空でOK
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getPolicyDirectory/getPolicyNamespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getPolicyDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getPolicyNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getPolicyDirectory(array $subDirs): string;
    abstract protected function getPolicyNamespace(array $subDirs): string;
}
