<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * モデル作成用トレイト。
 * -> MakeFileTrait を use し、モデル特有の (pivot, morphPivot, --all, etc.) ロジックを追加。
 */
trait MakeModelTrait
{
    use MakeFileTrait;

    /**
     * モデルを作成するメイン処理。
     *
     * @param  string  $className     モデルクラス名 (e.g. "Post")
     * @param  array   $subDirs       サブディレクトリ (["Admin"] など)
     * @param  bool    $force         --force
     * @param  bool    $pivot         --pivot
     * @param  bool    $morphPivot    --morph-pivot
     * @return string  $qualifiedFqcn 実際のモデルのFQCNを返して後続処理に使える
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        bool $pivot = false,
        bool $morphPivot = false
    ): string {
        // 1) モデル用 stub判定
        //   model.stub, model.pivot.stub, model.morph-pivot.stub
        $stubFile = 'model.stub';
        if ($morphPivot) {
            $stubFile = 'model.morph-pivot.stub';
        } elseif ($pivot) {
            $stubFile = 'model.pivot.stub';
        }

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) モデル固有プレースホルダ (たとえば factory 用)
        //    まずは空配列にし、必要に応じて buildFactoryReplacements(...) などで追加
        $extraPlaceholders = [
            // e.g. '{{ factory }}' => 'some code',
        ];

        // 4) makeFiler
        //    => "MakeFileTrait::makeFiler(...)"
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);

        // 5) 最終FQCN = namespace + class
        $fqcn = $this->getNamespace($subDirs) . '\\' . $className;
        return $fqcn;
    }

    /**
     * モデル用ディレクトリ/名前空間。
     * (B)パターン: getDirectory/getNamespace => getModelDirectory/getModelNamespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getModelDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getModelNamespace($subDirs);
    }

    /**
     * サブクラスに実装させる抽象メソッド
     */
    abstract protected function getModelDirectory(array $subDirs): string;
    abstract protected function getModelNamespace(array $subDirs): string;
}
