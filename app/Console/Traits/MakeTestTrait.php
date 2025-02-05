<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * テストファイルを作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeTestTrait
{
    use MakeFileTrait;

    /**
     * テストファイルを作成するメイン処理。
     *
     * @param  string  $className   テストクラス名
     * @param  array   $subDirs     サブディレクトリ (["Admin", ...] など)
     * @param  bool    $force       --force
     * @param  bool    $isUnit      --unit (true => Unit test, false => Feature test)
     * @param  bool    $usingPest   Pestを使うかどうか
     * @return void
     */
    protected function makeFile(
        string $className,
        array $subDirs,
        bool $force,
        bool $isUnit,
        bool $usingPest
    ): void {
        // 1) stubファイル名を決定
        //    "test.stub" / "test.unit.stub" (PHPUnit)
        //    "pest.stub" / "pest.unit.stub" (Pest)
        $stubFile = $this->determineStubFile($isUnit, $usingPest);

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) テスト固有プレースホルダ (なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * Pest / PHPUnit と unit / feature で stub を切り替える
     */
    protected function determineStubFile(bool $isUnit, bool $usingPest): string
    {
        // suffix
        $suffix = $isUnit ? '.unit.stub' : '.stub';

        if ($usingPest) {
            // pest.stub / pest.unit.stub
            return 'pest' . $suffix;
        } else {
            // test.stub / test.unit.stub
            return 'test' . $suffix;
        }
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getTestDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getTestDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getTestNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getTestDirectory(array $subDirs): string;
    abstract protected function getTestNamespace(array $subDirs): string;
}
