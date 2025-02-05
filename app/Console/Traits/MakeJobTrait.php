<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * ジョブを作成するための追加ロジック。
 * -> MakeFileTrait を use して継承的に発展させる例
 */
trait MakeJobTrait
{
    use MakeFileTrait;

    /**
     * ジョブを作成するメイン処理。
     * MakeFileTrait::makeFiler() を呼ぶ前後で、
     * ジョブ固有の stub選択 (sync or queued) / 追加置換を加える。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     * @param  bool    $sync    --sync
     * @return void
     */
    protected function makeFile(string $className, array $subDirs, bool $force, bool $sync): void
    {
        // 1) ジョブ用 stubファイルを決定 (sync/queued)
        $stubFile = $sync ? 'job.stub' : 'job.queued.stub';

        // 2) options をまとめる
        $options = [
            'force' => $force,
        ];

        // 3) ジョブ固有の追加プレースホルダ (必要なければ空配列)
        // ここでは何もない場合、たとえば queued jobに追加するものがあれば足す
        $extraPlaceholders = [
            // e.g. '{{ additional }}' => 'some-value'
        ];

        // 4) makeFiler を呼んで基本のファイル生成フローを実行
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * MakeFileTrait が要求する抽象メソッド:
     *   - getDirectory(array $subDirs)
     *   - getNamespace(array $subDirs)
     * ここでは (B)パターンを踏襲し、サブクラスの "getJobDirectory/Namespace" を呼ぶ
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getJobDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getJobNamespace($subDirs);
    }

    /**
     * サブクラスにて実装:
     *   abstract protected function getJobDirectory(array $subDirs): string;
     *   abstract protected function getJobNamespace(array $subDirs): string;
     */
    abstract protected function getJobDirectory(array $subDirs): string;
    abstract protected function getJobNamespace(array $subDirs): string;
}
