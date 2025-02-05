<?php

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * カスタムバリデーター（または独自バリデーションルール）を作るための追加ロジック。
 * -> MakeFileTrait を use してファイル生成を共通化。
 */
trait MakeValidatorTrait
{
    use MakeFileTrait;

    /**
     * バリデーターを作成するメイン処理。
     *
     * @param  string  $className
     * @param  array   $subDirs
     * @param  bool    $force
     */
    protected function makeFile(string $className, array $subDirs, bool $force): void
    {
        // 1) validator.stub (rule.stub と呼ぶこともある)
        $stubFile = $this->resolveStubFile();

        // 2) options
        $options = [
            'force' => $force,
        ];

        // 3) バリデーター固有の追加プレースホルダ (なければ空)
        $extraPlaceholders = [];

        // 4) makeFiler
        $this->makeFiler($className, $subDirs, $options, $stubFile, $extraPlaceholders);
    }

    /**
     * validator.stub を返す
     * もしくは `rule.stub` と呼んでもOKです
     */
    protected function resolveStubFile(): string
    {
        return 'validator.stub';
    }

    /**
     * (B)パターン: getDirectory/getNamespace => getValidatorDirectory/Namespace
     */
    protected function getDirectory(array $subDirs): string
    {
        return $this->getValidatorDirectory($subDirs);
    }

    protected function getNamespace(array $subDirs): string
    {
        return $this->getValidatorNamespace($subDirs);
    }

    /**
     * サブクラスで実装
     */
    abstract protected function getValidatorDirectory(array $subDirs): string;
    abstract protected function getValidatorNamespace(array $subDirs): string;
}
