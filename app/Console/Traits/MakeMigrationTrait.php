<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Console\Traits;

use Illuminate\Support\Str;

/**
 * マイグレーションを作成するための追加ロジック。
 * -> MakeFileTrait を use して継承
 *
 * Laravel標準 make:migration に準拠しながら、
 * plugin/custom 用にフォルダを変えるイメージ
 */
trait MakeMigrationTrait
{
    use MakeFileTrait;

    /**
     * マイグレーションを作成するメイン処理。
     *
     * @param  string       $migrationName   create_users_table など
     * @param  bool         $forceOption
     * @param  string|null  $createOption    --create=
     * @param  string|null  $tableOption     --table=
     * @param  string|null  $customPath      --path=
     * @param  bool         $realpath        --realpath
     * @param  bool         $fullpath        --fullpath
     * @return void
     */
    protected function makeMigration(
        string $migrationName,
        bool $forceOption,
        ?string $createOption,
        ?string $tableOption,
        ?string $customPath,
        bool $realpath,
        bool $fullpath
    ): void {
        // 1) タイムスタンプ付きファイル名
        $timestamp = date('Y_m_d_His');
        $fileName  = $timestamp . '_' . $migrationName . '.php';

        // 2) 出力先ディレクトリ
        //    --path オプションがあれば使う。なければ getMigrationDirectory() でデフォルト
        $targetDirectory = $this->determineMigrationDirectory($customPath, $realpath);

        // 3) 最終ファイルパス
        $filePath = rtrim($targetDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        // 4) stubファイル決定
        $stubFile = $this->decideStubFile($createOption, $tableOption);

        // 5) options
        $options = [
            'force' => $forceOption,
        ];

        // 6) 追加プレースホルダ
        $className = Str::studly($migrationName);
        $extraPlaceholders = [
            '{{ class }}' => $className,
            '{{ table }}' => $createOption ?: $tableOption, // create優先
        ];

        // 7) makeFiler
        //    ここでは "subDirs" を使わずに空配列
        //    あるいは subDirs を受け取って組み立てたいなら適宜
        $this->makeFiler($className, [], $options, $stubFile, $extraPlaceholders, $targetDirectory, $filePath);

        // 8) 出力
        if ($fullpath) {
            $this->info($filePath);
        } else {
            $this->info("Migration [{$fileName}] created successfully at [{$targetDirectory}].");
        }
    }

    /**
     * --path オプションが指定された場合、そのまま使用。
     * --realpath があれば絶対パスとして扱い、それ以外は base_path($customPath).
     * それが無ければ getMigrationDirectory() を用いる。
     */
    protected function determineMigrationDirectory(?string $customPath, bool $realpath): string
    {
        if ($customPath) {
            return $realpath
                ? $customPath
                : base_path($customPath);
        }
        // subDirs扱いしたいなら getMigrationDirectory([]) + ...
        return $this->getMigrationDirectory();
    }

    /**
     * create => migration.create.stub
     * table  => migration.update.stub
     * その他 => migration.stub
     */
    protected function decideStubFile(?string $create, ?string $table): string
    {
        if ($create) {
            return 'migration.create.stub';
        } elseif ($table) {
            return 'migration.update.stub';
        } else {
            return 'migration.stub';
        }
    }

    /**
     * (B)パターン: getDirectory/getNamespace が必要だが、
     * マイグレーションは通常 namespace を使わない or ほぼ空。
     * ここでは getDirectory() を getMigrationDirectory() にラップし、
     * getNamespace() は不要なら空実装。
     */
    protected function getDirectory(array $subDirs): string
    {
        // ここでは subDirs は無視して getMigrationDirectory() を呼ぶだけ
        return $this->getMigrationDirectory();
    }

    // マイグレーションに通常 namespace はほぼ不要だが、
    // base placeholders に '{{ namespace }}' があるので、
    // "Database\\Migrations" にしておくなどの例。
    protected function getNamespace(array $subDirs): string
    {
        return $this->getMigrationNamespace();
    }

    /**
     * サブクラスで実装: getMigrationDirectory(), getMigrationNamespace()
     */
    abstract protected function getMigrationDirectory(): string;
    abstract protected function getMigrationNamespace(): string;
}
